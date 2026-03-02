<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EwalletAccount; 
use App\Models\Customer;
use App\Models\EwalletTransaction;
use App\Services\EwalletService;
use App\Models\User; 

class EwalletAdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // eager load ewallet accounts
        $customers = Customer::with('ewalletAccount')
            ->paginate(20);
        // preserve query string for pagination links and performance
        $customers->withQueryString();

        return view('admin.ewallet.index', compact('customers'));
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

    public function adjust(Customer $customer)
    {
        // Ensure wallet account exists
        $account = $customer->ewalletAccount;

        if (!$account) {
            $account = EwalletAccount::create([
                'user_id'        => $customer->id,
                'credit_balance' => 0,
                'bonus_balance'  => 0,
                'currency'       => 'MYR', // or your default
            ]);
        }

        return view('admin.ewallet.adjust', compact('account'));
    }

    public function storeAdjust(Request $r, EwalletAccount $account, EwalletService $svc) {
        $r->validate([
            'amount'        => 'required|numeric',
            'type'          => 'required|in:credit_adjust,debit_adjust',
            'bonus_amount'  => 'required|numeric',
            'bonus_type'    => 'required|in:credit_bonus,debit_bonus',
            'reason'        => 'required|string|max:255',
        ]);
        
        // Call service with adminId
        $svc->adminAdjust(
            $account->user_id, 
            $r->amount, 
            $r->type, 
            $r->bonus_amount, 
            $r->bonus_type, 
            auth()->id(), 
            auth()->user()->email, 
            $r->reason
        );

        return redirect()->route('admin.ewallet.adjust', $account->user_id)
            ->with('success', "Adjustment applied to {$account->customer->name}'s wallet");
    }

    public function ledger(Customer $customer)
    {
        $transactions = EwalletTransaction::where('user_id', $customer->id)
            ->latest()
            ->paginate(20);

        return view('admin.ewallet.ledger', compact('customer','transactions'));
    }

}
