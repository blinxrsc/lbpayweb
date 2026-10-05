<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendMqttCommand;
use App\Models\Device;
use App\Models\DeviceAuditLog;
use App\Models\DeviceOutlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/**
 * Device-level API for the LB Admin mobile app (quick start, config
 * parameter, config network). Every action is scoped twice:
 *   1. by outlet — the user must be allowed to access the device's outlet
 *      (User::canAccessOutlet(), the same outlet_user scoping the web
 *      admin uses), and
 *   2. by permission — starting a machine needs devices_outlet.manage,
 *      changing parameters needs devices_outlet.edit (matching the web).
 */
class TechnicianDeviceController extends Controller
{
    private const BOOLEAN_PARAMS = ['pulse_pull_up', 'coin_signal_idle_high', 'pulse_active_low'];

    /**
     * Look up a device by serial number (from the QR code) and return
     * everything the app needs: outlet, live status, and every editable
     * pulse/price parameter.
     */
    public function show(Request $request, string $serial)
    {
        $device = Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->with('outlet')->first();

        $this->authorizeDevice($request, $deviceOutlet);

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
     * Save the device's pulse/price parameters (every change is written to
     * the same audit trail the web admin uses) and push them to the ESP32
     * over a retained MQTT CONFIG message.
     */
    public function updateParameters(Request $request, string $serial)
    {
        abort_unless($request->user()->can('devices_outlet.edit'), 403, 'You do not have permission to change device parameters.');

        $device = Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->first();
        $this->authorizeDevice($request, $deviceOutlet);

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
            'max_vend_price'          => 'sometimes|nullable|numeric|min:0',
            'pulse_pull_up'           => 'sometimes|boolean',
            'coin_signal_idle_high'   => 'sometimes|boolean',
            'coin_signal_sensitivity' => 'sometimes|integer|min:1',
            'pulse_active_low'        => 'sometimes|boolean',
        ]);

        foreach (self::BOOLEAN_PARAMS as $key) {
            if (array_key_exists($key, $validated)) {
                $validated[$key] = (int) filter_var($validated[$key], FILTER_VALIDATE_BOOLEAN);
            }
        }

        if (array_key_exists('pulse_price', $validated)) {
            $cyclePrices = array_filter([
                $validated['washer_cold_price'] ?? $device->washer_cold_price,
                $validated['washer_warm_price'] ?? $device->washer_warm_price,
                $validated['washer_hot_price']  ?? $device->washer_hot_price,
                $validated['dryer_low_price']   ?? $device->dryer_low_price,
                $validated['dryer_med_price']   ?? $device->dryer_med_price,
                $validated['dryer_hi_price']    ?? $device->dryer_hi_price,
            ], fn ($p) => $p > 0);

            if ($cyclePrices && $validated['pulse_price'] >= min($cyclePrices)) {
                return response()->json([
                    'message' => 'Pulse price (RM ' . number_format($validated['pulse_price'], 2) .
                        ') must be lower than the cheapest cycle price (RM ' . number_format(min($cyclePrices), 2) . ').',
                ], 422);
            }
        }

        foreach ($validated as $field => $newValue) {
            $oldValue = $device->$field;
            if ($oldValue != $newValue) {
                DeviceAuditLog::create([
                    'device_id' => $device->id,
                    'user_id'   => $request->user()->id,
                    'field'     => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        }

        $device->update($validated);

        // Fire-and-forget push. CONFIG is published retained, so a device
        // that's offline right now applies it the moment it reconnects.
        SendMqttCommand::dispatch($serial, 'CONFIG', [], $request->user()->id);

        return response()->json([
            'message' => 'Parameters saved and sent to the device.',
            'parameters' => $this->parametersFor($device->fresh()),
        ]);
    }

    /**
     * Re-push the currently saved parameters without changing anything.
     */
    public function sendConfig(Request $request, string $serial)
    {
        abort_unless($request->user()->can('devices_outlet.edit'), 403, 'You do not have permission to change device parameters.');

        Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->first();
        $this->authorizeDevice($request, $deviceOutlet);

        SendMqttCommand::dispatch($serial, 'CONFIG', [], $request->user()->id);

        return response()->json(['message' => 'Settings sent to the device.']);
    }

    /**
     * Send a coin-credit pulse for a given price. The device is pulse-
     * controlled: this credits the machine exactly like inserting a coin —
     * whoever is at the machine still presses its own Start button.
     * Pulses are computed here from the device's own pulse_price; the app
     * only ever sends a price, never a pulse count.
     */
    public function start(Request $request, string $serial)
    {
        abort_unless($request->user()->can('devices_outlet.manage'), 403, 'You do not have permission to start machines.');

        $device = Device::where('serial_number', $serial)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $serial)->first();
        $this->authorizeDevice($request, $deviceOutlet);

        $validated = $request->validate([
            'type' => 'required|string|max:50', // e.g. washer_hot, dryer_low, custom — for logging only
            'price' => 'required|numeric|min:0.01',
        ]);

        if (!$deviceOutlet?->is_online) {
            return response()->json(['message' => 'Machine is currently offline.'], 409);
        }

        if ((float) $device->pulse_price <= 0) {
            return response()->json(['message' => 'This device has no pulse_price configured yet.'], 422);
        }

        $max = (float) $device->max_vend_price;
        if ($max > 0 && (float) $validated['price'] > $max) {
            return response()->json([
                'message' => 'Amount exceeds this machine\'s max vend price (RM ' . number_format($max, 2) . ').',
            ], 422);
        }

        $pulses = (int) ceil($validated['price'] / $device->pulse_price);
        $opId = (string) Str::uuid();

        SendMqttCommand::dispatch($serial, 'REMOTE_START', [
            'op_id'  => $opId,
            'type'   => $validated['type'],
            'price'  => (float) $validated['price'],
            'pulses' => $pulses,
        ], $request->user()->id);

        return response()->json([
            'message' => "Credit pulse queued ({$pulses} pulses).",
            'pulses' => $pulses,
            'op_id' => $opId,
        ]);
    }

    /**
     * The firmware's ack for a REMOTE_START (received → completed / busy /
     * rejected …), captured into Redis by MqttBridge. The op_id is an
     * unguessable UUID and the response is just a status word.
     */
    public function startAck(Request $request, string $opId)
    {
        abort_unless(Str::isUuid($opId), 404);

        $json = Redis::get("start_ack:{$opId}");
        if (!$json) {
            return response()->json(['status' => 'awaiting_device']);
        }

        $ack = json_decode($json, true);
        return response()->json(['status' => $ack['status'] ?? 'unknown']);
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
            'max_vend_price'          => $device->max_vend_price,
            'pulse_pull_up'           => (bool) $device->pulse_pull_up,
            'coin_signal_idle_high'   => (bool) $device->coin_signal_idle_high,
            'coin_signal_sensitivity' => $device->coin_signal_sensitivity,
            'pulse_active_low'        => (bool) $device->pulse_active_low,
        ];
    }

    /**
     * Outlet scoping, same rules as the web admin (User::canAccessOutlet()).
     * A device that isn't assigned to any outlet yet can only be touched by
     * users who can see every outlet — otherwise an outlet-scoped user could
     * reach into devices that don't belong to them. Assign the device to
     * their outlet from the web admin first.
     */
    private function authorizeDevice(Request $request, ?DeviceOutlet $deviceOutlet): void
    {
        $user = $request->user();

        if ($deviceOutlet) {
            abort_unless($user->canAccessOutlet($deviceOutlet->outlet_id), 403, 'You do not have access to this outlet.');
            return;
        }

        abort_unless($user->canAccessAllOutlets(), 403, 'This device is not assigned to an outlet you can access.');
    }
}
