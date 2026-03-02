<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceOutlet;
use App\Events\DeviceStatusUpdated;

class DeviceStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateStatus(Request $request)
    {
        $validated = $request->validate([
            'device_serial_number' => 'required|exists:devices,serial_number',
            'outlet_id' => 'required|exists:outlets,id',
            'status'    => 'required|in:online,offline',
        ]);
        // Find the device by serial number
        //$device = Device::where('serial_number', $validated['device_serial_number'])->first();
        // Find the device_outlet record by device_id + outlet_id
        $deviceOutlet = DeviceOutlet::where('device_serial_number', $validated['device_serial_number'])
            ->where('outlet_id', $validated['outlet_id'])
            ->first();

        if (!$deviceOutlet) {
            return response()->json([
                'error' => 'DeviceOutlet not found for this device and outlet'
            ], 404);
        }

        $deviceOutlet->update([
            'status' => $validated['status'],
            'last_seen_at' => now(),
        ]);

        // Trigger broadcast event here
        event(new DeviceStatusUpdated($deviceOutlet));

        return response()->json([
            'message' => 'Device status updated successfully',
            'data' => [
                'device' => $deviceOutlet->device->serial_number,
                'outlet' => $deviceOutlet->outlet->name,
                'status' => $deviceOutlet->status,
                'last_seen_at' => $deviceOutlet->last_seen_at,
            ]
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'updates' => 'required|array',
            'updates.*.device_serial_number' => 'required|exists::devices,serial_number',
            'updates.*.outlet_id' => 'required|exists:outlets,id',
            'updates.*.status' => 'required|in:online,offline',
        ]);

        foreach ($validated['updates'] as $update) {
            DeviceOutlet::where('device_serial_number', $update['device_serial_number'])
                ->where('outlet_id', $update['outlet_id'])
                ->update([
                    'status' => $update['status'],
                    'last_seen_at' => now(),
                ]);
        }

        return response()->json(['message' => 'Bulk device statuses updated']);
    }
}
