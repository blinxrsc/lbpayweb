<?php

namespace App\Services;

use App\Models\DeviceOutlet;

class DeviceActivationService
{
    public function activate(DeviceOutlet $deviceOutlet, string $mode, int $duration)
    {
        // Example: publish MQTT message to AWS IoT Core
        $payload = [
            'device_outlet_id' => $deviceOutlet->id,
            'mode'             => $mode,
            'duration'         => $duration,
            'timestamp'        => now()->toISOString(),
        ];

        // Use your IoT client (AWS IoT, MQTT, etc.)
        // IoTClient::publish("devices/{$deviceOutlet->id}/activate", json_encode($payload));

        // Log activation for audit
        \Log::info("Device {$deviceOutlet->id} activated via {$mode} for {$duration} minutes", $payload);

        return true;
    }
}
