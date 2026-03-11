<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PhpMqtt\Client\Facades\MQTT;
use Illuminate\Support\Facades\Log;
use App\Models\RemoteStartLog;
use App\Models\Device;
use App\Models\DeviceOutlet;
use Exception;
use Illuminate\Support\Facades\Http;
use App\Models\User;


class SendMqttCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // If the MQTT broker is busy, retry 3 times before failing
    public $tries = 3; // Retry if MQTT is down
    public $backoff = 10; // Wait 10 seconds between retries

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $serial,
        public string $action, // 'REBOOT', 'REMOTE_START', UPDATE.
        public array $payload = [],
        public int $userId
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 1. Prepare the MQTT Message
        if ($this->action === 'REBOOT') {
            $message = 'REBOOT';

            // Mark the machine as "Rebooting..." in the DB
            DeviceOutlet::where('device_serial_number', $this->serial)
                ->update(['status' => 'rebooting']);

        } elseif ($this->action === 'REMOTE_START') {
            // Fetch device settings for hardware-specific pulse timing
            $device = Device::where('serial_number', $this->serial)->first();
            
            // Safety check for device
            if (!$device) {
                Log::error("Device not found: {$this->serial}");
                return; // Or throw an exception to retry
            }

            $fullPayload = array_merge([
                'action' => $this->action,
                'width'  => $device->pulse_width ?? 100,
                'delay'  => $device->pulse_delay ?? 100,
            ], $this->payload);

            $message = json_encode($fullPayload);
        } elseif ($this->action === 'UPDATE') {
            $message = "UPDATE:{$this->payload['url']}";
        }

        // 2. Publish to MQTT with error handling
        try {
            // QoS 0 is default, OK for non-critical commands
            MQTT::publish("machines/{$this->serial}/cmd", $message);
            Log::info("MQTT [{$this->action}] sent to {$this->serial}");
        } catch (Exception $e) {
            Log::error("MQTT Send failed for {$this->serial}: " . $e->getMessage());
            // Release back to queue to retry based on $backoff
            $this->release($this->backoff); 
            return;
        }

        // 3. Conditional Audit Logging (Only for Starts, not Reboots)
        if ($this->action === 'REMOTE_START') {
            RemoteStartLog::create([
                'user_id' => $this->userId,
                'device_serial_number' => $this->serial,
                'cycle_type' => $this->payload['type'] ?? 'unknown',
                'equivalent_price' => $this->payload['price'] ?? 0,
            ]);
            
            // Send Telegram here if needed
            $user = User::find($this->userId);
            $message = "🚀 **Remote Start Triggered**\n"
             . "--------------------------\n"
             . "👤 **Admin:** {$user->name}\n"
             . "🤖 **Device:** {$this->serial}\n"
             . "🧺 **Cycle:** {$this->payload['type']}\n"
             . "💰 **Value:** \${$this->payload['price']}\n"
             . "📅 **Time:** " . now()->format('Y-m-d H:i:s');
            
            Http::post("https://api.telegram.org/bot" . config('services.telegram.bot_token') . "/sendMessage", [
                'chat_id' => config('services.telegram.admin_chat_id'),
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        }
    }
}
