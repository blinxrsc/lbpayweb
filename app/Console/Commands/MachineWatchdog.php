<?php

namespace App\Console\Commands;

use App\Models\DeviceOutlet;
use App\Services\MachineMessageHandler;
use App\Support\Telegram;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

/**
 * F5: marks machines offline when their heartbeats stop, and alerts ONCE
 * per transition.
 *
 * The old watchdog compared the DB column last_seen_at — which heartbeats
 * never update (on purpose, to save RDS writes). So 15 minutes after any
 * machine came online it was marked offline + alerted, the next heartbeat
 * flipped it back, and the cycle repeated every ~16 minutes for EVERY
 * healthy machine (≈750,000 false transitions/day at 8,000 devices).
 *
 * Now the last-seen time comes from Redis (written by every heartbeat):
 *   machine_last_seen (sorted set)  → machine_heartbeats (hash, older data)
 *   → DB last_seen_at (only if Redis has never seen the device).
 * A machine is alerted only when it goes from online → offline here; it is
 * not alerted again until it has come back and dropped again.
 */
class MachineWatchdog extends Command
{
    protected $signature = 'app:machine-watchdog {--dry-run : Report only, change nothing}';

    protected $description = 'Mark machines offline when heartbeats stop, alert once per transition';

    public function handle(): int
    {
        $threshold = now()->subSeconds((int) config('lbpay.offline_after_seconds', 180))->timestamp;

        $online = DeviceOutlet::with('outlet:id,outlet_name')
            ->whereIn('status', [DeviceOutlet::STATUS_ONLINE, 'Online'])
            ->get(['id', 'outlet_id', 'device_serial_number', 'machine_num', 'last_seen_at', 'status']);

        if ($online->isEmpty()) {
            return self::SUCCESS;
        }

        $serials = $online->pluck('device_serial_number')->all();

        // One round-trip for all last-seen scores and legacy hash values.
        $scores = Redis::pipeline(function ($pipe) use ($serials) {
            foreach ($serials as $sn) {
                $pipe->zscore(MachineMessageHandler::LAST_SEEN, $sn);
            }
        });
        $hash = Redis::hmget(MachineMessageHandler::HEARTBEATS, $serials);

        $stale = $online->filter(function ($m, $i) use ($scores, $hash, $threshold) {
            $lastSeen = $scores[$i] ?? null;
            if ($lastSeen === null || $lastSeen === false) {
                $lastSeen = $hash[$i] ?? null;
            }
            if ($lastSeen === null || $lastSeen === false) {
                $lastSeen = $m->getRawOriginal('last_seen_at') ? strtotime($m->getRawOriginal('last_seen_at')) : 0;
            }
            return (int) $lastSeen < $threshold;
        })->values();

        if ($stale->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("Watchdog: {$stale->count()} machine(s) went offline.");
        if ($this->option('dry-run')) {
            $stale->each(fn ($m) => $this->line(" - {$m->device_serial_number}"));
            return self::SUCCESS;
        }

        DeviceOutlet::whereIn('id', $stale->pluck('id'))->update([
            'status' => DeviceOutlet::STATUS_OFFLINE,
            'availability' => 0,
        ]);
        Redis::pipeline(function ($pipe) use ($stale) {
            foreach ($stale as $m) {
                $pipe->del(DeviceOutlet::presenceKey($m->device_serial_number));
            }
        });

        Telegram::send($this->digest($stale));

        return self::SUCCESS;
    }

    /** One message per run, grouped by outlet (was one message per outlet per minute). */
    private function digest($stale): string
    {
        $lines = ["🚨 *Machines went OFFLINE* ({$stale->count()})", ''];
        $shown = 0;
        foreach ($stale->groupBy('outlet_id') as $machines) {
            if ($shown >= 40) {
                break;
            }
            $outlet = $machines->first()->outlet->outlet_name ?? 'Unknown outlet';
            $ids = $machines->take(10)->map(fn ($m) => '`' . $m->device_serial_number . '`')->implode(', ');
            $more = $machines->count() > 10 ? ' +' . ($machines->count() - 10) . ' more' : '';
            $lines[] = "📍 *{$outlet}* — {$machines->count()}: {$ids}{$more}";
            $shown++;
        }
        $remaining = $stale->groupBy('outlet_id')->count() - $shown;
        if ($remaining > 0) {
            $lines[] = "…and {$remaining} more outlet(s).";
        }
        $lines[] = '';
        $lines[] = '⏰ ' . now()->format('Y-m-d H:i');
        return implode("\n", $lines);
    }
}
