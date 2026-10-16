<?php

namespace App\Jobs;

use App\Models\Device;
use App\Models\DeviceOutlet;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Database side of telemetry. Since F8 this is only queued when something
 * CHANGED (coins inserted, online/offline transition, reboot) — plain
 * heartbeats are handled in Redis by MachineMessageHandler and never get
 * here. Redis presence is also written there, not here.
 *
 * Payload: ['status' => 'online'|'offline', 'coins' => int, 'event' => 'boot'?, 'version' => '1.5.6'?]
 */
class ProcessMachineMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 30;
    public $tries = 3;

    public function __construct(public string $serial, public array $payload) {}

    public function handle(): void
    {
        $now    = now();
        $status = strtolower((string) ($this->payload['status'] ?? 'online'));
        $coins  = max(0, (int) ($this->payload['coins'] ?? 0));
        $isBoot = ($this->payload['event'] ?? null) === 'boot';

        if ($isBoot && !empty($this->payload['version'])) {
            Device::where('serial_number', $this->serial)->update(['version' => (string) $this->payload['version']]);
        }

        $machine = DeviceOutlet::where('device_serial_number', $this->serial)->first();
        if (!$machine) {
            Log::warning("Telemetry for unassigned device {$this->serial}");
            return;
        }

        $current = strtolower((string) $machine->status);

        // Only presence statuses follow telemetry. A manual flag such as
        // 'faulty' stays until staff clear it (a heartbeat used to overwrite it).
        $statusChanged = in_array($current, DeviceOutlet::PRESENCE_STATUSES, true) || $current === ''
            ? $current !== $status
            : false;

        if ($coins === 0 && !$statusChanged && !$isBoot) {
            return;
        }

        DB::transaction(function () use ($machine, $status, $coins, $now, $statusChanged, $isBoot) {
            $update = ['last_seen_at' => $now];

            if ($statusChanged) {
                $update['status'] = $status;
                $update['availability'] = $status === DeviceOutlet::STATUS_ONLINE ? 1 : 0;
            }
            if ($coins > 0) {
                $update['current_coins']  = DB::raw('current_coins + ' . $coins);
                $update['lifetime_coins'] = DB::raw('lifetime_coins + ' . $coins);
            }
            if ($isBoot) {
                $update['last_reboot_at'] = $now;
            }

            DeviceOutlet::whereKey($machine->id)->update($update);
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error("ProcessMachineMessage failed for {$this->serial}: " . $exception->getMessage());
    }
}
