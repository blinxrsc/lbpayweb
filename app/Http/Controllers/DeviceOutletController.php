<?php

namespace App\Http\Controllers;

use App\Models\DeviceOutlet;
use App\Models\Outlet;
use App\Models\Device;
use App\Models\Brand;
use App\Models\TypeStatus;
use App\Models\TypeOutlet;
use App\Models\DeviceMovementLog;
use App\Models\DeviceAuditLog;
use App\Jobs\SendMqttCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceOutletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DeviceOutlet::with(['outlet.brand','device']);

        // Scope to outlets this user is allowed to see (null = unrestricted/admin).
        // Note: accessibleOutletIds() can return an empty array for a user with
        // no assignments yet, which must still filter to "show nothing" — so
        // check against null explicitly rather than truthiness.
        $ids = $request->user()->accessibleOutletIds();
        if ($ids !== null) {
            $query->whereIn('outlet_id', $ids);
        }

        if ($request->filled('brand_id')) {
            $query->whereHas('outlet', function($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }
        
        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
        }

        // Metric cards: respect the outlet-scoping applied above, but not
        // the ephemeral brand/status/outlet_type/outlet_name filters below —
        // these should read as "your whole scoped fleet", stable regardless
        // of whatever filter is currently applied to the table.
        $scopedCountQuery = DeviceOutlet::query();
        if ($ids !== null) {
            $scopedCountQuery->whereIn('outlet_id', $ids);
        }
        $totalCount = $scopedCountQuery->count();
        $onlineCount = (clone $scopedCountQuery)->where('status', 'online')->count();
        $faultyCount = (clone $scopedCountQuery)->where('status', 'faulty')->count();

        if ($request->filled('outlet_type')) {
            $query->whereHas('outlet', function($q) use ($request) {
                $q->where('type', $request->outlet_type);
            });
        }
        if ($request->filled('outlet_name')) {
            $query->whereHas('outlet', function($q) use ($request) {
                $q->where('outlet_name', $request->outlet_name);
            });
        }
        $transaction = $query->paginate(20)->withQueryString();
        $brands = Brand::all();
        $statuses = TypeStatus::all();
        $types = TypeOutlet::all();
        return view('device_outlets.index', compact('transaction','brands','statuses','types','totalCount','onlineCount','faultyCount'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $outlets = Outlet::accessibleBy(auth()->user())->get();
        //$devices = Device::all();
        // Filter only devices that are unassigned
        $devices = Device::where('status', 'unassigned')->get();
        return view('device_outlets.create', compact('outlets','devices'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_id'            => 'required|exists:outlets,id',
            'machine_num'          => 'required|string|max:255',
            'machine_name'         => 'required|string|max:255',
            'machine_type'         => 'required|in:Washer,Dryer,Combo,Token Changer,Vending',
            'device_serial_number' => 'required|exists:devices,serial_number',
            'status'               => 'required|in:online,offline',
            'availability'         => 'boolean',
        ]);

        abort_unless($request->user()->canAccessOutlet($validated['outlet_id']), 403, 'You do not have access to this outlet.');

        // Use a transaction to ensure both updates succeed or both fail
        DB::transaction(function () use ($validated) {
            // 1. Create the DeviceOutlet mapping
            DeviceOutlet::create($validated);

            // 2. Update the Device status to 'Assigned' 
            // and optionally set the outlet_id on the device table
            Device::where('serial_number', $validated['device_serial_number'])
                ->update([
                    'status' => 'assigned',
                    'outlet_id' => $validated['outlet_id']
                ]);
            
            // 3. Create Audit Trail
            DeviceMovementLog::create([
                'device_serial_number' => $validated['device_serial_number'],
                'action' => 'Assigned to Outlet',
                'outlet_id' => $validated['outlet_id'],
                'user_id' => auth()->id(),
                'from_status' => 'unassigned',
                'to_status' => 'assigned',
                'notes' => 'Initial assignment to ' . $validated['machine_name'],
            ]);
        });

        return redirect()->route('device_outlets.index')->with('success','Mapping device and outlet created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DeviceOutlet $deviceOutlet)
    {
        abort_unless(auth()->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);

        $outlets = Outlet::accessibleBy(auth()->user())->get();
        $devices = Device::all();
        return view('device_outlets.show', compact('deviceOutlet','outlets','devices'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeviceOutlet $deviceOutlet)
    {
        abort_unless(auth()->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);

        $outlets = Outlet::accessibleBy(auth()->user())->get();
        // Get unassigned devices OR the device currently assigned to this record
        $devices = Device::where('status', 'unassigned')
            ->orWhere('serial_number', $deviceOutlet->device_serial_number)
            ->get();
        return view('device_outlets.edit', compact('deviceOutlet','outlets','devices'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DeviceOutlet $deviceOutlet)
    {
        $validated = $request->validate([
            'outlet_id'            => 'required|exists:outlets,id',
            'machine_num'          => 'required|string|max:255',
            'machine_name'         => 'required|string|max:255',
            'machine_type'         => 'required|in:Washer,Dryer,Combo,Token Changer,Vending',
            'device_serial_number' => 'required|exists:devices,serial_number',
            'status'               => 'required|in:Online,Offline',
            'availability'         => 'boolean',
        ]);

        abort_unless($request->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);
        abort_unless($request->user()->canAccessOutlet($validated['outlet_id']), 403, 'You do not have access to this outlet.');

        DB::transaction(function () use ($validated, $deviceOutlet) 
        {
            $oldSerialNumber = $deviceOutlet->device_serial_number;
            $newSerialNumber = $validated['device_serial_number'];

            // 1. If the device has changed, release the old one
            if ($oldSerialNumber !== $newSerialNumber) {
                Device::where('serial_number', $oldSerialNumber)
                    ->update([
                        'status' => 'unassigned',
                        'outlet_id' => null
                    ]);
                
                // Create Audit Trail
                DeviceMovementLog::create([
                    'device_serial_number' => $oldSerialNumber,
                    'action' => 'Unassigned from Outlet',
                    'outlet_id' => $deviceOutlet->outlet_id,
                    'user_id' => auth()->id(),
                    'from_status' => 'assigned',
                    'to_status' => 'unassigned',
                    'notes' => 'Device replaced by ' . $newSerialNumber,
                ]);
            }

            // 2. Update the DeviceOutlet record
            $deviceOutlet->update($validated);

            // 3. Ensure the current (new) device is marked as Assigned
            Device::where('serial_number', $newSerialNumber)
                ->update([
                    'status' => 'assigned',
                    'outlet_id' => $validated['outlet_id']
                ]);
        });

        return redirect()->route('device_outlets.index')->with('success','Mapping updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DeviceOutlet $deviceOutlet)
    {
        abort_unless(auth()->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);

        DB::transaction(function () use ($deviceOutlet) 
        {
            // Capture the serial number before deleting the record
            $serialNumber = $deviceOutlet->device_serial_number;

            // 1. Delete the mapping
            $deviceOutlet->delete();

            // 2. Set the device back to 'Unassigned'
            Device::where('serial_number', $serialNumber)
                ->update([
                    'status' => 'unassigned',
                    'outlet_id' => null
                ]);
        });
        return redirect()->route('device_outlets.index')->with('success','Mapping device and outlet deleted successfully.');
    }
    
    public function CSV(Request $request)
    {
        // Handle Export (Better to grab all data for export, not just paginated)
        if ($request->has('export')) {
            // Example: Export all devices, not just the 10 on the current page
            $allDeviceOutlets = DeviceOutlet::with(['outlet.brand','device'])->get();
            if ($allDeviceOutlets->isEmpty()) {
                return redirect()->back()->with('error', 'No transaction found to export.');
            }
            return $this->export($allDeviceOutlets);
        }
    }
  
    public function export($data)
    {
        $fileName = "DeviceOutlet_" . now()->format('YmdHi') . ".csv";

        $headers = [
            'Content-Type'        => 'text/csv',
            "Content-Disposition" => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $columns = [
            'Outlet Name','Brand','Outlet Status','Outlet Type',
            'Machine #','Machine Name','Machine Type',
            'Device Serial Number','Device Model','Mapping Status','Availability'
        ];

        $callback = function() use ($data, $columns) {
            // Use $file as the consistent file handle variable
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

             // Iterate over the passed $data collection, not an undefined $deviceOutlets
            foreach ($data as $map) {
                fputcsv($file, [
                    optional($map->outlet)->outlet_name,
                    optional($map->outlet->brand)->name,
                    optional($map->outlet)->status,
                    optional($map->outlet)->type,
                    $map->machine_num,
                    $map->machine_name,
                    $map->machine_type,
                    $map->device_serial_number,
                    optional($map->device)->model,
                    $map->status,
                    $map->availability ? 'Available' : 'Unavailable',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Device Parameters + Audit Trail for the device currently assigned to
     * this outlet slot. Relocated here (from devices.edit) so it's reached
     * alongside the outlet/machine context it actually affects, instead of
     * a separate "Manage Device" page that's now purchase/assignment only.
     */
    public function parameters(DeviceOutlet $deviceOutlet)
    {
        abort_unless(auth()->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);

        $device = Device::where('serial_number', $deviceOutlet->device_serial_number)->firstOrFail();
        $auditLogs = $device->auditLogs()->with('user')->latest()->get();

        return view('device_outlets.parameters', compact('deviceOutlet', 'device', 'auditLogs'));
    }

    public function updateParameters(Request $request, DeviceOutlet $deviceOutlet)
    {
        abort_unless(auth()->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);

        $device = Device::where('serial_number', $deviceOutlet->device_serial_number)->firstOrFail();

        $validated = $request->validate([
            'washer_cold_price' => 'required|numeric|min:0',
            'washer_warm_price' => 'required|numeric|min:0',
            'washer_hot_price'  => 'required|numeric|min:0',
            'dryer_low_price'   => 'required|numeric|min:0',
            'dryer_med_price'   => 'required|numeric|min:0',
            'dryer_hi_price'    => 'required|numeric|min:0',
            'pulse_price'       => 'required|numeric|min:0.01',
            'pulse_add_min'     => 'required|integer|min:0',
            'pulse_width'       => 'required|integer|min:1',
            'pulse_delay'       => 'required|integer|min:1',
            'coin_signal_width' => 'required|integer|min:1',
            // New fields:
            'max_vend_price'           => 'nullable|numeric|min:0',
            'pulse_pull_up'             => 'required|boolean',
            'coin_signal_idle_high'     => 'required|boolean',
            'coin_signal_sensitivity'   => 'required|integer|min:1',
        ]);

        foreach ($validated as $field => $newValue) {
            $oldValue = $device->$field;
            if ($oldValue != $newValue) {
                DeviceAuditLog::create([
                    'device_id' => $device->id,
                    'user_id'   => auth()->id(),
                    'field'     => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ]);
            }
        }

        $device->update($validated);

        // Push the pulse/coin-signal subset straight to the ESP32. max_vend_price
        // is NOT included — it's a backend price cap (enforced in
        // TechnicianDeviceController::start() and the dashboard's remoteStart()),
        // the firmware has no use for it.
        SendMqttCommand::dispatch($deviceOutlet->device_serial_number, 'CONFIG', [], auth()->id());

        return redirect()->route('device_outlets.parameters', $deviceOutlet)
            ->with('success', 'Parameters saved and pushed to the device.');
    }

    /**
     * Re-push the currently-saved parameters without changing anything —
     * useful after a device reboot/replacement, or if it was offline the
     * last time parameters were saved (CONFIG is retained, but an explicit
     * resend still gives the technician a clear "I just did this" action).
     */
    public function sendConfig(DeviceOutlet $deviceOutlet)
    {
        abort_unless(auth()->user()->canAccessOutlet($deviceOutlet->outlet_id), 403);

        SendMqttCommand::dispatch($deviceOutlet->device_serial_number, 'CONFIG', [], auth()->id());

        return redirect()->route('device_outlets.parameters', $deviceOutlet)
            ->with('success', 'Settings sent to the device.');
    }

}
