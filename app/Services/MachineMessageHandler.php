<?php

namespace App\Services;

use App\Jobs\ProcessMachineMessage;
use App\Jobs\RecordStartAck;
use App\Models\Device;
use App\Models\DeviceOutlet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Handles every MQTT message from the fleet (called by `app:mqtt-bridge`).
 *
 * F8 — built for 8,000 devices (~133 heartbeats/s at a 60 s interval):
 *  - A plain heartbeat is handled entirely in Redis (one pipeline, no
 *    queue job, no DB query, no log line). Before, EVERY heartbeat became
 *    a Horizon job + a MySQL SELECT + a log line.
 *  - A job is queued only when something changed: coins were inserted,
 *    the device came online / went offline, or it rebooted.
 *  - Retained telemetry is ignored: firmware <= v1.5.5 publishes its
 *    offline LWT *retained* on the telemetry topic, so every bridge
 *    restart replayed a stale "offline" for every device that ever dropped.
 *
 * Topics (per device serial):
 *   machines/{sn}/telemetry  heartbeat {"status":"online","coins":0}, coin {"coins":1}, old LWT {"status":"offline"}
 *   machines/{sn}/presence   firmware v1.5.6+: retained {"status":"online"|"offline"} (LWT = offline)
 *   machines/{sn}/status     start_ack / config_ack / boot / OTA progress
 *
 * Returns a short label of what was done (used by tests and debug logging).
 */
class MachineMessageHandler
{
    public const HEARTBEATS = 'machine_heartbeats';     // hash  serial => unix ts (kept for existing UI code)
    public const LAST_SEEN  = 'machine_last_seen';      // zset  serial scored by unix ts (watchdog)
    public const BRIDGE_ALIVE = 'mqtt_bridge:alive';    // unix ts of the bridge's last loop

    public function handle(string $topic, string $message, bool $retained = false): string
    {
        $parts = explode('/', $topic);
        if (count($parts) < 3 || $parts[0] !== 'machines' || $parts[1] === '') {
            return 'ignored_topic';
        }
        [, $serial, $sub] = $parts;

        // Empty payload = a retained message being deleted (firmware v1.5.6
        // clears the old retained LWT on telemetry when it connects).
        if (trim($message) === '') {
            return 'ignored_empty';
        }

        $payload = json_decode($message, true);
        if (!is_array($payload)) {
            Log::warning("MQTT: invalid JSON from {$serial} on {$sub}");
            return 'invalid_json';
        }

        if (config('lbpay.mqtt_bridge_debug')) {
            Log::debug("MQTT {$topic}" . ($retained ? ' (retained)' : '') . ": {$message}");
        }

        return match ($sub) {
            'telemetry' => $retained ? 'ignored_retained' : $this->telemetry($serial, $payload),
            'presence'  => $this->presence($serial, $payload),
            'status'    => $this->status($serial, $payload, $message),
            default     => 'ignored_topic',
        };
    }

    // ───────────────────────── telemetry ─────────────────────────

    private function telemetry(string $serial, array $payload): string
    {
        $status = strtolower((string) ($payload['status'] ?? 'online'));

        if ($status === DeviceOutlet::STATUS_OFFLINE) {
            // Live (non-retained) LWT from firmware <= v1.5.5.
            return $this->markOffline($serial);
        }

        $coins = max(0, (int) ($payload['coins'] ?? 0));
        $cameOnline = $this->markSeen($serial, $payload);

        if ($coins > 0 || $cameOnline) {
            ProcessMachineMessage::dispatch($serial, ['status' => 'online', 'coins' => $coins]);
            return $coins > 0 ? 'coins' : 'came_online';
        }

        return 'heartbeat';
    }

    // ───────────────────────── presence (v1.5.6+) ─────────────────────────

    private function presence(string $serial, array $payload): string
    {
        $status = strtolower((string) ($payload['status'] ?? ''));

        if ($status === DeviceOutlet::STATUS_OFFLINE) {
            return $this->markOffline($serial);
        }
        if ($status === DeviceOutlet::STATUS_ONLINE) {
            if ($this->markSeen($serial, $payload)) {
                ProcessMachineMessage::dispatch($serial, ['status' => 'online', 'coins' => 0]);
                return 'came_online';
            }
            return 'heartbeat';
        }
        return 'ignored_presence';
    }

    // ───────────────────────── status topic ─────────────────────────

    private function status(string $serial, array $payload, string $raw): string
    {
        $event = $payload['event'] ?? null;

        if ($event === 'start_ack') {
            $opId = (string) ($payload['op_id'] ?? '');
            if ($opId === '') {
                return 'ignored_ack';
            }
            // Read by the customer page / technician app poll (was 120 s,
            // too short for long pulse trains — 1000 pulses can take minutes).
            Redis::setex("start_ack:{$opId}", 600, json_encode($payload));

            // Paid customer starts are tracked on their transaction (F6).
            if ($txnId = Redis::get("start_op:{$opId}")) {
                RecordStartAck::dispatch((int) $txnId, $payload);
            }
            return 'start_ack';
        }

        if ($event === 'config_ack') {
            return 'config_ack';
        }

        if ($event === 'boot') {
            // Firmware v1.5.2+ announces itself after connecting. Records the
            // reboot time and the firmware version (useful to track an OTA
            // rollout: "which devices are still below 1.5.6?").
            $this->markSeen($serial, $payload);
            ProcessMachineMessage::dispatch($serial, [
                'status'  => 'online',
                'coins'   => 0,
                'event'   => 'boot',
                'version' => $payload['version'] ?? null,
            ]);
            return 'boot';
        }

        if (array_key_exists('ota_status', $payload)) {
            Device::where('serial_number', $serial)->update([
                'ota_status'   => $payload['ota_status'],
                'ota_error'    => $payload['error'] ?? null,
                'last_payload' => $raw,
            ]);
            return 'ota';
        }

        // Anything else on /status used to fall through to the OTA branch
        // and wipe ota_status to NULL. Now it is simply ignored.
        return 'ignored_status';
    }

    // ───────────────────────── helpers ─────────────────────────

    /**
     * Refresh presence in one Redis round-trip. Returns true when the device
     * was NOT considered online before (new, came back, key had expired).
     */
    private function markSeen(string $serial, array $payload): bool
    {
        $key = DeviceOutlet::presenceKey($serial);
        $now = time();
        $ttl = (int) config('lbpay.presence_ttl_seconds', 150);

        $results = Redis::pipeline(function ($pipe) use ($key, $serial, $now, $ttl, $payload) {
            $pipe->get($key);
            $pipe->setex($key, $ttl, DeviceOutlet::STATUS_ONLINE);
            $pipe->hset(self::HEARTBEATS, $serial, $now);
            $pipe->zadd(self::LAST_SEEN, $now, $serial);
            $pipe->setex("machine:last_payload:{$serial}", 86400, json_encode($payload));
        });

        return ($results[0] ?? null) !== DeviceOutlet::STATUS_ONLINE;
    }

    private function markOffline(string $serial): string
    {
        $wasOnline = Redis::get(DeviceOutlet::presenceKey($serial)) === DeviceOutlet::STATUS_ONLINE;
        Redis::del(DeviceOutlet::presenceKey($serial));

        // Always let the job reconcile the DB row — offline messages are rare.
        ProcessMachineMessage::dispatch($serial, ['status' => 'offline', 'coins' => 0]);

        return $wasOnline ? 'went_offline' : 'offline';
    }
}
