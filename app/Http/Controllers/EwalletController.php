<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\EwalletAccount;
use App\Models\TopupPackage;
use App\Models\PaymentGatewayTransaction;
use App\Services\EwalletService;
use App\Models\PaymentGatewaySetting;

class EwalletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function dashboard(Request $request) {
        $customer = Auth::guard('customer')->user(); 
        if (!$customer) {   // If not logged in, redirect to customer login
            return redirect()->route('customer.login')->with('error', 'Please log in to access your dashboard.');
        }

        // Optionally, check transaction status if orderid is passed
        if ($request->has('orderid')) {
            $transaction = PaymentGatewayTransaction::where('order_id', $request->orderid)->first();
            if ($transaction && $transaction->status === 'paid') {
                // Show success message
                session()->flash('success', 'Top-up successful!');
            }
        }

        $account = EwalletAccount::firstOrCreate(
            ['user_id' => $customer->id],
            ['credit_balance' => 0, 'bonus_balance' => 0]
        );
        $credit  = $account->credit_balance ?? 0;
        $bonus   = $account->bonus_balance ?? 0;

        return view('customer.dashboard', compact('account'));
    }
    
    public function initiateTopup(Request $r) {
        $r->validate(['amount' => 'required|numeric|min:1']);
        $customer = auth('customer')->user(); //$customerId = auth('customer')->id();
        $orderId = 'ORD-' . now()->format('YmdHis') . '-' . $customer->id;
        $amount = number_format($r->amount, 2, '.', '');
        // Fetch active gateway settings
        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        // Build signature vcode=amount+merchantid+orderid+verify key
        //$signature = md5($gw->merchant_id . $orderId . number_format($r->amount, 2, '.', '') . $gw->secret_key);
        $signature = md5($amount . $gw->merchant_id . $orderId . $gw->private_key);
        // Save transaction record
        $txn = PaymentGatewayTransaction::create([
            //'user_id'  => $customerId,
            'user_id'  => $customer->id,
            'amount'   => $r->amount,
            'currency' => 'MYR',
            'status'   => PaymentGatewayTransaction::STATUS_INITIATED,
            'provider' => PaymentGatewayTransaction::PROVIDER_FIUU,
            'order_id' => $orderId,
            'request_payload'  => [
                'merchant_id' => $gw->merchant_id,
                'amount'      => $r->amount,
                'order_id'    => $orderId,
                'signature'   => $signature,
            ],
        ]);
        // Render topup form with gateway values
        return view('ewallet.topup-form', [
            'amount'    => $amount,
            'orderId'   => $orderId,
            'merchantId'=> $gw->merchant_id,
            'signature' => $signature,
            //'sandbox'   => $gw->sandbox,
        ]);
    }
    
    /**
     * Display a listing of the transactions.
     */
    public function transactionList(Request $request) {
        
        return view('customer.transaction');
    }
    public function viewTopupPackage() {
        
        $packages = TopupPackage::where('is_active', true)->orderBy('topup_amount')->get();
        return view('customer.topuplist', compact('packages'));
    }

}
