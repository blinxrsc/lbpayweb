<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceOutlet;
use App\Models\DeviceTransaction;
use App\Jobs\SendMqttCommand;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeviceTransactionAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DeviceTransaction::with(['customer','deviceOutlet.outlet','refund']);
        // Filter by transaction time period
        if ($request->filled('from')) {
            $query->where('updated_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->filled('to')) {
            // End of day ensures you get transactions at 11:59:59 PM
            $query->where('updated_at', '<=', Carbon::parse($request->to)->endOfDay());
        }
        // Filter by atatua
        if ($request->status) {
            $query->where('status', $request->status);
        }
        // Filter by customer name
        if ($request->customer) {
            $query->whereHas('customer', fn($q) => $q->where('name','like','%'.$request->customer.'%')
                                                ->orWhere('email','like','%'.$request->customer.'%'));
        }
        // Filter by outlet
        if ($request->outlet) {
            $query->whereHas('deviceOutlet.outlet', fn($q) => $q->where('outlets.outlet_name','like','%'.$request->outlet.'%'));
        }
        // Filter by device serial number
        if ($request->filled('device_serial_number')) 
        {
            $query->where('device_serial_number', $request->machine_serialnumber);
        }
        // Check if the user clicked Export
        if ($request->input('export') === 'excel') {
            return $this->exportToExcel($query->get());
        }
        $transactions = $query->paginate(20)->withQueryString();


        return view('admin.device-transactions.index', compact('transactions'));
    }

    protected function exportToExcel($data)
    {
        $fileName = "transactions_" . now()->format('YmdHi') . ".csv";
        
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['Date', 'Status', 'Customer Email', 'Customer Phone', 'Outlet', 'Machine ID', 'Device SN', 'Provider', 'Transaction ID', 'Order ID', 'amount', 'currency'];

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $txn) {
                fputcsv($file, [
                    $txn->updated_at,
                    $txn->status,
                    $txn->customer ? $txn->customer->email : ($txn->meta['guest_email'] ?? 'Guest'),
                    $txn->customer ? $txn->customer->phone_country_code.$txn->customer->phone_number : ($txn->meta['guest_phone'] ?? ''),
                    $txn->deviceOutlet->outlet->outlet_name,
                    $txn->deviceOutlet->machine_type.' '.$txn->deviceOutlet->machine_num,
                    $txn->deviceOutlet->device_serial_number,
                    $txn->provider,
                    $txn->provider_txn_id,
                    $txn->order_id,
                    $txn->amount,
                    $txn->currency
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
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
    public function show(DeviceTransaction $transaction)
    {
        return view('admin.device-transactions.show', compact('transaction'));
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

    public function activate(DeviceTransaction $transaction)
    {
        // Was calling DeviceActivationService::activate(), which only wrote
        // a log line and returned true — it never actually published
        // anything to MQTT. This button has likely been a complete no-op
        // since it was built, which may explain some of the "paid but no
        // pulse" cases — retrying via this button wouldn't have done
        // anything. Now uses the same dispatch path as every other remote
        // start (technician app, dashboard, customer payment).
        $deviceOutlet = $transaction->deviceOutlet;
        $device = $deviceOutlet?->device;

        if (!$device || (float) $device->pulse_price <= 0) {
            return back()->with('error', 'This device has no pulse_price configured — cannot compute pulses.');
        }

        $pulses = (int) ceil($transaction->amount / $device->pulse_price);

        SendMqttCommand::dispatch($deviceOutlet->device_serial_number, 'REMOTE_START', [
            'op_id'  => (string) Str::uuid(),
            'type'   => $transaction->meta['mode'] ?? 'device',
            'price'  => (float) $transaction->amount,
            'pulses' => $pulses,
        ], userId: auth()->id());

        $transaction->update(['status' => DeviceTransaction::STATUS_ACTIVATED]);
        return back()->with('success', 'Pulse re-sent to the machine.');
    }

}
