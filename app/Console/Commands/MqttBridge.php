<?php

namespace App\Console\Commands;

use App\Services\MachineMessageHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use PhpMqtt\Client\Facades\MQTT;
use Throwable;

/**
 * Listens to EMQX and hands every message to MachineMessageHandler.
 *
 * F8 changes:
 *  - No per-message Log::info (was ~11.5M lines/day at 8,000 devices).
 *    Set MQTT_BRIDGE_DEBUG=true to log messages while troubleshooting.
 *  - Optional EMQX shared subscription (MQTT_SHARE_GROUP=lbpay): run 2-3
 *    bridge containers and EMQX spreads telemetry/status across them.
 *    Presence is subscribed normally by every bridge, because retained
 *    presence (current online/offline of every device) is only delivered
 *    to normal subscriptions, and handling it twice is harmless.
 *  - Writes a liveness timestamp to Redis so `app:bridge-status` / a
 *    healthcheck can tell a stuck bridge from a working one.
 *  - A handler error is logged and the bridge keeps running (one bad
 *    message must not stop ingest for the whole fleet).
 */
class MqttBridge extends Command
{
    protected $signature = 'app:mqtt-bridge';

    protected $description = 'Listen to EMQX and process device messages';

    public function handle(MachineMessageHandler $handler): int
    {
        $mqtt  = MQTT::connection();
        $group = config('lbpay.mqtt_share_group');
        $share = fn (string $filter) => $group ? "\$share/{$group}/{$filter}" : $filter;

        $callback = function (string $topic, string $message, bool $retained = false) use ($handler) {
            try {
                $handler->handle($topic, $message, $retained);
            } catch (Throwable $e) {
                Log::error("MQTT handler error on {$topic}: " . $e->getMessage());
            }
        };

        $mqtt->subscribe($share('machines/+/telemetry'), $callback, 0);
        $mqtt->subscribe($share('machines/+/status'), $callback, 0);
        $mqtt->subscribe('machines/+/presence', $callback, 0);

        $this->info('MQTT bridge listening' . ($group ? " (shared group '{$group}')" : '') . '.');

        // loopOnce() (not loop()): loop() never returns while subscriptions
        // exist, so nothing else could run. loopOnce() reads everything
        // available, sends keep-alive pings, and sleeps 50 ms only when idle.
        $startedAt = microtime(true);
        $lastAlive = 0;
        while (true) {
            $mqtt->loopOnce($startedAt, true, 50000);

            if (time() - $lastAlive >= 10) {
                $lastAlive = time();
                Redis::set(MachineMessageHandler::BRIDGE_ALIVE, $lastAlive);
            }
        }
    }
}
