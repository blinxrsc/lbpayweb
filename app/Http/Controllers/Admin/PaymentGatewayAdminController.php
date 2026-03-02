<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DeviceOutlet;
use App\Models\PaymentGatewayTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PaymentGatewayAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PaymentGatewayTransaction::with(['customer']);
        // Filter by transaction time period
        if ($request->filled('from')) {
            $query->where('updated_at', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->filled('to')) {
            // End of day ensures you get transactions at 11:59:59 PM
            $query->where('updated_at', '<=', Carbon::parse($request->to)->endOfDay());
        }
        // Filter by outlet
        if ($request->filled('outlet_name')) 
        {
            $query->where('outlet_name', $request->outlet_name);
        }
        // Filter by customer name
        if ($request->filled('customer')) 
        {
            $query->whereHas('customer', fn($q) => $q->where('name','like','%'.$request->customer.'%')
                                                ->orWhere('email','like','%'.$request->customer.'%'));
        }
        // Filter by device serial number
        if ($request->filled('device_serial_number')) 
        {
            $query->where('device_serial_number', $request->machine_serialnumber);
        }
        // Filter by status
        if ($request->filled('status')) 
        {
            $query->where('status', $request->status);
        }
        // Check if the user clicked Export
        if ($request->input('export') === 'excel') {
            return $this->exportToExcel($query->get());
        }
        $transactions = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();
        $summary = [
            'total_amount' => $query->sum('amount'),
            //'total_bonus'  => $query->sum('bonus_amount'),
            //'total_debit'  => $query->whereIn('transaction_type', ['deduction'])->sum('amount'),
            //'net_balance'  => $query->sum(DB::raw('amount + bonus_amount')),
        ];

        return view('admin.paymentgateway.index', compact('transactions', 'summary'));
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

        $columns = [
            'Date', 
            'Status', 
            'Customer Email', 
            'Customer Phone', 
            //'Outlet', 
            //'Machine ID', 
            //'Device SN', 
            'Provider', 
            'Transaction ID', 
            'Order ID', 
            'amount', 
            'currency'];

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $txn) {
                fputcsv($file, [
                    $txn->updated_at,
                    $txn->status,
                    $txn->customer->email,
                    $txn->customer->phone_country_code.$txn->customer->phone_number,
                    //$txn->deviceOutlet->outlet->outlet_name,
                    //$txn->deviceOutlet->machine_type.' '.$txn->deviceOutlet->machine_num,
                    //$txn->deviceOutlet->device_serial_number,
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
    public function show(PaymentGatewayTransaction $transaction)
    {
        return view('admin.paymentgateway.show', compact('transaction'));
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

    public function refund(PaymentGatewayTransaction $transaction)
    {
        // Refund logic (wallet credit back if method = ewallet, mark refunded if fiuu)
        $transaction->update(['status' => 'refunded']);
        return back()->with('success','Transaction refunded.');
    }
}
