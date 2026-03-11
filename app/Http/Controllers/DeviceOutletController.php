<?php

namespace App\Http\Controllers;

use App\Models\DeviceOutlet;
use App\Models\Outlet;
use App\Models\Device;
use App\Models\Brand;
use App\Models\TypeStatus;
use App\Models\TypeOutlet;
use App\Models\DeviceMovementLog;
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

        if ($request->filled('brand_id')) {
            $query->whereHas('outlet', function($q) use ($request) {
                $q->where('brand_id', $request->brand_id);
            });
        }
        
        if ($request->filled('status')) {
            $query->whereRaw('LOWER(status) = ?', [strtolower($request->status)]);
        }

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
        return view('device_outlets.index', compact('transaction','brands','statuses','types'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $outlets = Outlet::all();
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
        $outlets = Outlet::all();
        $devices = Device::all();
        return view('device_outlets.show', compact('deviceOutlet','outlets','devices'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DeviceOutlet $deviceOutlet)
    {
        $outlets = Outlet::all();
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

}
