<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendMqttCommand;
use App\Models\Device;
use App\Models\DeviceOutlet;
use Illuminate\Http\Request;

class TechnicianDeviceController extends Controller
{
    /**
     * Look up a device by serial number (from the QR code) and return
     * everything the technician app needs: outlet, live status, and the
     * editable pulse/price parameters.
     */
    public function show(Request $request, string $serial)
    {
        $device = Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->with('outlet')->first();

        $this->authorizeOutlet($request, $deviceOutlet);

        return response()->json([
            'serial_number' => $device->serial_number,
            'model' => $device->model,
            'ota_status' => $device->ota_status,
            'outlet' => $deviceOutlet?->outlet ? [
                'id' => $deviceOutlet->outlet->id,
                'name' => $deviceOutlet->outlet->outlet_name,
            ] : null,
            'machine' => $deviceOutlet ? [
                'type' => $deviceOutlet->machine_type,
                'num' => $deviceOutlet->machine_num,
                'name' => $deviceOutlet->machine_name,
                'is_online' => (bool) $deviceOutlet->is_online,
                'availability' => (bool) $deviceOutlet->availability,
            ] : null,
            'parameters' => $this->parametersFor($device),
        ]);
    }

    /**
     * Update the device's pulse/price parameters. The pulse-timing subset
     * (width/delay/coin signal width) is immediately pushed to the ESP32
     * over a retained MQTT CONFIG message, so the running device doesn't
     * need a reboot to pick up the change.
     */
    public function updateParameters(Request $request, string $serial)
    {
        $device = Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->first();
        $this->authorizeOutlet($request, $deviceOutlet);

        $validated = $request->validate([
            'washer_cold_price' => 'sometimes|numeric|min:0',
            'washer_warm_price' => 'sometimes|numeric|min:0',
            'washer_hot_price'  => 'sometimes|numeric|min:0',
            'dryer_low_price'   => 'sometimes|numeric|min:0',
            'dryer_med_price'   => 'sometimes|numeric|min:0',
            'dryer_hi_price'    => 'sometimes|numeric|min:0',
            'pulse_price'       => 'sometimes|numeric|min:0.01',
            'pulse_add_min'     => 'sometimes|integer|min:0',
            'pulse_width'       => 'sometimes|integer|min:1',
            'pulse_delay'       => 'sometimes|integer|min:1',
            'coin_signal_width' => 'sometimes|integer|min:1',
        ]);

        $device->update($validated);

        // Fire-and-forget push to the device. If it's offline right now, the
        // retained flag means it'll pick this up the moment it reconnects.
        SendMqttCommand::dispatch($serial, 'CONFIG', [], $request->user()->id);

        return response()->json([
            'message' => 'Parameters updated.',
            'parameters' => $this->parametersFor($device->fresh()),
        ]);
    }

    /**
     * Trigger a remote start for a given price. Pulses are always computed
     * here from the device's own pulse_price — the app only ever sends a
     * price, never a pulse count — so this can't be spoofed into starting a
     * cheaper cycle than intended.
     */
    public function start(Request $request, string $serial)
    {
        $device = Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->first();
        $this->authorizeOutlet($request, $deviceOutlet);

        $validated = $request->validate([
            'type' => 'required|string', // e.g. washer_hot, dryer_low — for logging/telemetry only
            'price' => 'required|numeric|min:0.01',
        ]);

        if (!$deviceOutlet?->is_online) {
            return response()->json(['message' => 'Machine is currently offline.'], 409);
        }

        if ((float) $device->pulse_price <= 0) {
            return response()->json(['message' => 'This device has no pulse_price configured yet.'], 422);
        }

        $pulses = (int) ceil($validated['price'] / $device->pulse_price);

        SendMqttCommand::dispatch($serial, 'REMOTE_START', [
            'type'   => $validated['type'],
            'price'  => $validated['price'],
            'pulses' => $pulses,
        ], $request->user()->id);

        return response()->json([
            'message' => "Start command queued ({$pulses} pulses).",
            'pulses' => $pulses,
        ]);
    }

    private function parametersFor(Device $device): array
    {
        return [
            'washer_cold_price' => $device->washer_cold_price,
            'washer_warm_price' => $device->washer_warm_price,
            'washer_hot_price'  => $device->washer_hot_price,
            'dryer_low_price'   => $device->dryer_low_price,
            'dryer_med_price'   => $device->dryer_med_price,
            'dryer_hi_price'    => $device->dryer_hi_price,
            'pulse_price'       => $device->pulse_price,
            'pulse_add_min'     => $device->pulse_add_min,
            'pulse_width'       => $device->pulse_width,
            'pulse_delay'       => $device->pulse_delay,
            'coin_signal_width' => $device->coin_signal_width,
        ];
    }

    /**
     * Reuses the same outlet-scoping you already have for the admin panel
     * (User::canAccessOutlet()) so a technician only sees/edits/starts
     * devices at the outlets they're assigned to. Users with
     * 'outlets.view-all' (admins) bypass this, same as elsewhere.
     */
    private function authorizeOutlet(Request $request, ?DeviceOutlet $deviceOutlet): void
    {
        $user = $request->user();
        if ($deviceOutlet && !$user->canAccessOutlet($deviceOutlet->outlet_id)) {
            abort(403, 'You do not have access to this outlet.');
        }
    }
}
