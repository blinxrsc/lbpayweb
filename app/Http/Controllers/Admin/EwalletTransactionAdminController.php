<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EwalletTransaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EwalletTransactionAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = EwalletTransaction::query()
            ->with(['customer', 'ewalletAccount']);

        // Filter by transaction time period
        if ($request->filled('from')) {
            $query->where('transaction_time', '>=', Carbon::parse($request->from)->startOfDay());
        }
        if ($request->filled('to')) {
            // End of day ensures you get transactions at 11:59:59 PM
            $query->where('transaction_time', '<=', Carbon::parse($request->to)->endOfDay());
        }
        // Filter by outlet
        if ($request->filled('outlet_name')) 
        {
            $query->where('outlet_name', $request->outlet_name);
        }
        // Filter by customer name
        if ($request->filled('customer_name')) 
        {
            $query->whereHas('customer', function ($q) use ($request) {
                $q->where('name', $request->customer_name);
            });
        }
        // Filter by device serial number
        if ($request->filled('device_serial_number')) 
        {
            $query->where('device_serial_number', $request->machine_serialnumber);
        }
        // Filter by admin email
        if ($request->filled('admin_email')) 
        {
            $query->where('admin_email', $request->admin_email);
        }
        // Filter by transaction type
        if ($request->filled('transaction_type')) 
        {
            $query->where('transaction_type', $request->transaction_type);
        }
        // Check if the user clicked Export
        if ($request->input('export') === 'excel') {
            return $this->exportToExcel($query->get());
        }
        $transactions = $query->orderByDesc('transaction_time')->paginate(20);
        $summary = [
            'total_amount' => $query->sum('amount'),
            'total_bonus'  => $query->sum('bonus_amount'),
            'total_debit'  => $query->whereIn('transaction_type', ['deduction'])->sum('amount'),
            'net_balance'  => $query->sum(DB::raw('amount + bonus_amount')),
        ];

        return view('admin.ewallet.transactions.index', compact('transactions', 'summary'));
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

        $columns = ['Date', 'Type', 'Amount', 'Bonus', 'Remaining Balance', 'Reference', 'Admin'];

        $callback = function() use($data, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $txn) {
                fputcsv($file, [
                    $txn->transaction_time,
                    $txn->transaction_type,
                    $txn->amount,
                    $txn->bonus_amount,
                    $txn->remaining_balance,
                    $txn->reference,
                    $txn->admin_email
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
}
