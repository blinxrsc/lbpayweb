<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\Device;
use App\Models\Supplier;
use App\Models\DeviceAuditLog;
use App\Models\DeviceMovementLog;
use App\Models\DeviceOutlet;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Default: Display Paginated Data
        $devices = Device::with('supplier')->paginate(10);
        return view('devices.index', compact('devices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $suppliers = Supplier::all();
        return view('devices.create', compact('suppliers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:devices',
            'model'         => 'required|string|max:255',
            'version'       => 'nullable|string|max:255',
            'order_number'  => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'supplier_id'   => 'required|exists:suppliers,id',
            'purchase_cost' => 'nullable|numeric|min:0.00',
        ]);

        // Convert empty string to null for nullable decimal fields
        if (empty($validated['purchase_cost'])) {
            $validated['purchase_cost'] = null;
        }

        Device::create($validated);
        return redirect()->route('devices.index')->with('success', 'Device created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Device $device)
    {
        // Load the movement logs with the user and outlet relationships
        $movementLogs = DeviceMovementLog::with(['user', 'outlet'])
            ->where('device_serial_number', $device->serial_number)
            ->latest()
            ->get();

        return view('devices.show', compact('device', 'movementLogs'));
    }

    public function CSV(Request $request)
    {
        // Handle Export (Better to grab all data for export, not just paginated)
        if ($request->has('export')) {
            // Example: Export all devices, not just the 10 on the current page
            return $this->export(Device::with('supplier')->get());
        }
        // Handle Import
        if ($request->has('import')) {
            $request->validate([
                'csv_file' => 'required|file|mimes:csv,txt',
            ]);

            $path = $request->file('csv_file')->getRealPath();
            $rows = array_map('str_getcsv', file($path));
            $header = array_map('trim', $rows[0]);
            unset($rows[0]);

            $previewData = [];
            foreach ($rows as $row) {
                if (count($header) === count($row)) {
                    $previewData[] = array_combine($header, $row);
                }
            }

            // Store in session for the next request
            session(['import_preview' => $previewData]);

            return view('devices.import-preview', compact('previewData'));
            // Pass the WHOLE $request here
            //return $this->import($request);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    //public function edit(string $id)
    public function edit(Device $device)
    {
        $suppliers = Supplier::all();
        return view('devices.edit', compact('device', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    //public function update(Request $request, string $id)
    public function update(Request $request, Device $device)
    {
        $validated = $request->validate([
            'serial_number' => 'required|string|max:255|unique:devices,serial_number,' . $device->id,
            'model'         => 'required|string|max:255',
            'version'       => 'nullable|string|max:255',
            'order_number'  => 'nullable|string|max:255',
            'purchase_date' => 'nullable|date',
            'supplier_id'   => 'required|exists:suppliers,id',
            //'outlet_id'     => 'nullable|exists:outlets,id',
            'purchase_cost' => 'nullable|numeric|min:0.00',
            'washer_cold_price' => 'required|numeric|min:0',
            'washer_warm_price' => 'required|numeric|min:0',
            'washer_hot_price'  => 'required|numeric|min:0',
            'dryer_low_price'   => 'required|numeric|min:0',
            'dryer_med_price'   => 'required|numeric|min:0',
            'dryer_hi_price'    => 'required|numeric|min:0',
            'pulse_price'       => 'required|numeric|min:0',
            'pulse_add_min'     => 'required|integer|min:0',
            'pulse_width'       => 'required|integer|min:0',
            'pulse_delay'       => 'required|integer|min:0',
            'coin_signal_width' => 'required|integer|min:0',
            //'status'          => 'required|in:assigned,unassigned',
        ]);
        // Determine new status
        //$validated['status'] = $validated['outlet_id'] ? 'assigned' : 'unassigned';

        // Convert empty string to null for nullable decimal fields
        if (empty($validated['purchase_cost'])) {
            $validated['purchase_cost'] = null;
        }

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
        return redirect()->route('devices.index')->with('success', 'Device updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    //public function destroy(string $id)
    public function destroy(Device $device)
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Device deleted successfully.');
    }

    public function confirmImport(Request $request)
    {
        $dataToImport = session('import_preview');

        if (!$dataToImport) {
            return redirect()->route('devices.index')->with('error', 'No data to import.');
        }

        foreach ($dataToImport as $data) {
            // 1. Convert empty strings to null for database compatibility
            $sanitized = [
                'model'         => $data['model'],
                'version'       => !empty($data['version']) ? $data['version'] : null,
                'order_number'  => !empty($data['order_number']) ? $data['order_number'] : null,
                'purchase_date' => !empty($data['purchase_date']) ? $data['purchase_date'] : null,
                'supplier_id'   => $data['supplier_id'],
                'purchase_cost' => (isset($data['purchase_cost']) && $data['purchase_cost'] !== '') ? $data['purchase_cost'] : null,
            ];

            // 2. Use the sanitized array
            Device::updateOrCreate(
                ['serial_number' => $data['serial_number']],
                $sanitized
            );
        }

        // Clear session after success
        session()->forget('import_preview');

        return redirect()->route('devices.index')->with('success', 'Devices imported successfully.');
    }

    public function import(Request $request)
    {
        // Now $request->validate() and $request->file() will work perfectly
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', $rows[0]);
        unset($rows[0]);

        foreach ($rows as $row) {
            //check for the empty line
            if (count($header) !== count($row)) {
                continue; // Skip rows that don't match the header count
            }
            $data = array_combine($header, $row);

            // Validate each row
            $validator = Validator::make($data, [
                'serial_number' => 'required|string|max:255',
                'model'         => 'required|string|max:255',
                'version'       => 'nullable|string|max:255',
                'order_number'  => 'nullable|string|max:255',
                'purchase_date' => 'nullable|date',
                'supplier_id'   => 'required|exists:suppliers,id',
                'outlet_id'     => 'nullable|exists:outlets,id',
                'purchase_cost' => 'nullable|numeric|min:0.00',
            ]);

            if ($validator->fails()) {
                continue; // skip invalid rows
            }
            // Update or create by serial_number
            Device::updateOrCreate(
                ['serial_number' => $data['serial_number']],
                [
                    'model'         => $data['model'],
                    'version'       => $data['version'] ?? null,
                    'order_number'  => $data['order_number'] ?? null,
                    'purchase_date' => $data['purchase_date'] ?? null,
                    'supplier_id'   => $data['supplier_id'],
                    'purchase_cost' => $data['purchase_cost'] ?? null,
                ]
            );
        }

        return redirect()->route('devices.index')->with('success', 'Devices imported successfully.');
    }

    protected function export($data)
    {
        $fileName = "devices_" . now()->format('YmdHi') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['serial_number', 'model', 'version', 'order_number', 'purchase_date', 'supplier_id', 'supplier_name', 'purchase_cost'];

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $txn) {
                fputcsv($file, [
                    $txn->serial_number,
                    $txn->model,
                    $txn->version,
                    $txn->order_number,
                    $txn->purchase_date,
                    $txn->supplier_id,
                    // 2. Safely handle null relationship
                    $txn->supplier->supplier_name ?? 'N/A',
                    $txn->purchase_cost
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function rollback(DeviceAuditLog $log) {
        $device = $log->device;
        //$this->authorize('update', $device);

        // Restore the old value
        $device->update([
            $log->field => $log->old_value
        ]);

        // Log the rollback itself
        DeviceAuditLog::create([
            'device_id' => $device->id,
            'user_id'   => auth()->id(),
            'field'     => $log->field,
            'old_value' => $log->new_value,
            'new_value' => $log->old_value,
        ]);

        return redirect()->route('devices.show', $device)
            ->with('success', "Rolled back {$log->field} to previous value.");
    }
    
    public function generateQr(Device $device)
    {
        // Embed unique link with serial number
        $url = route('device.scan', ['serial' => $device->serial_number]);

        // Generate QR as PNG
        $qr = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($url);

        // Download response
        return response($qr)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="device-'.$device->serial_number.'.png"');
    }
    
    public function generateQrInline(Device $device)
    {
        $url = route('device.scan', ['serial' => $device->serial_number]);

        $qr = QrCode::size(250)->generate($url);

        return response()->json([
            'serial' => $device->serial_number,
            'qr'     => $qr,
            'download_url' => route('devices.qrcode', $device),
        ]);
    }

    public function scan($serial)
    {
        // Find device by serial number
        $device = Device::where('serial_number', $serial)->firstOrFail();
        // You can preload pricing or other info here
        return view('customer.payment.qr-confirm', compact('device'));
    }

    public function markFaulty(Request $request, Device $device)
    {
        $request->validate([
            'serial_number' => 'required|string|max:255|unique:devices,serial_number,' . $device->serial_number,
            'notes' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($device, $request) {
            $oldStatus = $device->status;
            $currentOutlet = $device->deviceOutlets->outlet_id ?? null;

            // 1. If device is currently assigned, remove it from the mapping table
            DeviceOutlet::where('device_serial_number', $device->serial_number)->delete();

            // 2. Update Device status
            $device->update([
                'status' => 'faulty', // or 'In Repair'
                'outlet_id' => null
            ]);

            // 3. Create the Movement Log with User ID
            DeviceMovementLog::create([
                'device_serial_number' => $device->serial_number,
                'user_id' => auth()->id(), // Track the performer
                'outlet_id' => $currentOutlet,
                'action' => 'Marked Faulty',
                'from_status' => $oldStatus,
                'to_status' => 'faulty',
                'notes' => $request->notes ?? 'Device reported faulty by user.',
            ]);
        });

        return back()->with('success', 'Device marked as faulty and unassigned from outlet.');
    }

    public function repairCompleted(Request $request, Device $device)
    {
        $request->validate([
            'repair_notes' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($device, $request) {
            $oldStatus = $device->status;

            // 1. Update Device status to Unassigned (Ready for use)
            $device->update([
                'status' => 'unassigned',
                'outlet_id' => null
            ]);

            // 2. Create the Movement Log
            DeviceMovementLog::create([
                'device_serial_number' => $device->serial_number,
                'user_id' => auth()->id(),
                'action' => 'Repair Completed',
                'from_status' => $oldStatus,
                'to_status' => 'unassigned',
                'notes' => 'Repair Details: ' . $request->repair_notes,
            ]);
        });

        return back()->with('success', 'Device repaired and moved to Unassigned stock.');
    }
}
