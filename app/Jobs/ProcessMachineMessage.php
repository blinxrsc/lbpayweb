<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use App\Models\DeviceOutlet;
use Illuminate\Support\Facades\Log; // Added for debugging
use Exception;

class ProcessMachineMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $serial;
    protected $payload;

    // Set a timeout for the job
    public $timeout = 30;
    // Maximum attempts
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct($serial, array $payload)
    {
        $this->serial = $serial;
        $this->payload = $payload;
    }
    
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();
        // 1. Get the status from the payload, default to 'online' if not specified
        $statusFromPayload = $this->payload['status'] ?? 'online';
        $coinsToAdd = (int)($this->payload['coins'] ?? 0); // Cast to int for safety
        

        // 1. FAST REDIS UPDATES (Always do this first)
        // Cache status, last seen, and the raw payload for the "JSON tooltip"
        Redis::setex("machine:status:{$this->serial}", 600, $statusFromPayload);
        Redis::hset('machine_heartbeats', $this->serial, $now->timestamp);
        Redis::set("machine:last_payload:{$this->serial}", json_encode($this->payload));

        // 2. DETECT REBOOT
        // If your ESP32 sends {"event": "boot"} or similar
        if (isset($this->payload['event']) && $this->payload['event'] === 'boot') {
            Redis::hset('machine_last_reboot', $this->serial, $now->timestamp);
        }

        // 3. DATABASE UPDATES (Throttled)
        // Only hit the DB if there are coins OR if the status changed.
        // We avoid updating 'last_seen_at' in the DB every 5 seconds to save RDS IOPS.
        if ($coinsToAdd > 0 || $statusFromPayload === 'offline') {
            // Using a transaction to ensure atomic updates if multiple fields change
            DB::transaction(function () use ($statusFromPayload, $coinsToAdd, $now) {
                // 2. Find the machine
                $machine = DeviceOutlet::where('device_serial_number', $this->serial)->first();

                if (!$machine) {
                    Log::warning("Machine not found: {$this->serial}");
                    return;
                }

                // 2. Atomic Update to prevent race conditions
                $updateData = [
                    'status' => $statusFromPayload, // Use the dynamic value!
                    'availability' => ($statusFromPayload === 'online') ? 1 : 0, // Or whatever logic you use for idle
                    'last_seen_at' => $now,
                ];

                // Only add coins if the status is NOT offline
                if ($coinsToAdd > 0) {
                    $updateData['current_coins'] = DB::raw("current_coins + {$coinsToAdd}");
                    $updateData['lifetime_coins'] = DB::raw("lifetime_coins + {$coinsToAdd}");
                    
                    // Trigger revenue logic if enabled
                    // $this->processRevenue($coins, $machine->outlet_id);
                }

                // Check if we logged a reboot in Redis, move it to DB
                if ($rebootTime = Redis::hget('machine_last_reboot', $this->serial)) {
                    $updateData['last_reboot_at'] = date('Y-m-d H:i:s', $rebootTime);
                    $updateData['status'] = 'online'; // Assume online after boot
                    Redis::hdel('machine_last_reboot', $this->serial); // Clear from Redis
                }

                $machine->update($updateData);
            });
        }
    }
    // Handle job failure
    public function failed(Exception $exception): void
    {
        Log::error("Job failed for machine {$this->serial}: " . $exception->getMessage());
    }
}
