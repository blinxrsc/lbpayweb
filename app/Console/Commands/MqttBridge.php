<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;
use App\Jobs\ProcessMachineMessage;
use App\Models\Device;

class MqttBridge extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:mqtt-bridge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to EMQX and dispatch jobs to Horizon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mqtt = MQTT::connection();

        // Subscribe to all machine topics using a wildcard (+)
        // machines/NYJ001/telemetry, machines/NYJ002/telemetry, etc.
        $mqtt->subscribe('machines/#', function (string $topic, string $message) {
            \Log::info("Message received on topic: $topic content: $message"); // Add this
            $this->info("[" . now()->format('H:i:s') . "] Incoming: $topic");
            
            // Extract the serial number from the topic
            $parts = explode('/', $topic);
            if (count($parts) < 3) return; // Ignore malformed topics

            $serial = $parts[1];
            $subTopic = $parts[2]; // 'telemetry' or 'status'

            $payload = json_decode($message, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Invalid JSON received from $serial: $message");
                \Log::error("JSON Decode Error for serial $serial: " . json_last_error_msg());
                return;
            }
            
            // 2. Handle OTA / Command Status Updates
            if ($subTopic === 'status') {
                Device::where('serial_number', $serial)->update([
                    'ota_status' => $payload['ota_status'] ?? null,
                    'ota_error'  => $payload['error'] ?? null,
                    'last_payload' => $message, // This is the raw JSON string
                ]);
                $this->info("✓ OTA Status updated for $serial");
            }

            // 3. Handle Telemetry (Heartbeat/Coins) via Horizon
            if ($subTopic === 'telemetry') {
                // Continue with your existing Job dispatching...
                ProcessMachineMessage::dispatch($serial, $payload);
                $this->info("⚡ Telemetry job queued for $serial");
            }
        }, 0);
        // This loop(true) is what keeps the terminal "alive"
        // If it gets stuck, try adding a small sleep or check connection status
        while (true) {
            $mqtt->loop(true);
            usleep(100000); // 0.1s sleep to prevent CPU spiking
        }
    }
}
