<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use \App\Services\EwalletService;
use App\Models\PaymentGatewayTransaction;
use App\Models\PaymentGatewaySetting;
use App\Models\EwalletAccount;

class GatewayController extends Controller
{
    public function callback(Request $r, EwalletService $svc)
    {
        // Log the entire incoming payload
        Log::channel('fiuu')->info('Fiuu CALLBACK called', $r->all());

        $tranID   = $r->input('tranID');
        $orderId  = $r->input('orderid');
        $status   = $r->input('status');
        $domain   = $r->input('domain');
        $amount   = $r->input('amount');
        $currency = $r->input('currency');
        $appcode  = $r->input('appcode');
        $paydate  = $r->input('paydate');
        $skey     = $r->input('skey');
        $nbcb     = $r->input('nbcb');

        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        $secKey = $gw->secret_key;

        // Verify return signature
        $key0 = md5($tranID.$orderId.$status.$domain.$amount.$currency);
        $key1 = md5($paydate.$domain.$key0.$appcode.$secKey);

        if ($skey !== $key1) {
            $status = -1;
            Log::channel('fiuu')->warning("Invalid signature for order {$orderId}", [
                'expected' => $key1,
                'received' => $skey,
            ]);
        }

        Log::channel('fiuu')->info('Signature check', [
            'expected' => $key1,
            'received' => $skey
        ]);

        $pg = PaymentGatewayTransaction::where('order_id', $orderId)->firstOrFail();
        // Don’t set to paid yet — leave as initiated/failed
        $pg->update([
            'provider_txn_id'  => $tranID,
            'response_payload' => $r->all(),
            'status'           => $status === '00' ? 'initiated' : 'failed',
        ]);

        //Log::channel('fiuu')->info("Transaction {$orderId} updated to {$pg->status}");
        Log::channel('fiuu')->info("Transaction {$orderId} initiated to {$pg->status}");

        if ($status === '00') {
            $svc->creditTopupWithPackage(
                $pg->user_id,
                (float)$amount,
                $tranID,
                $orderId,
                'fiuu',
                ['response_payload' => $r->all()]
            );
            //Log::channel('fiuu')->info("Wallet credited for order {$orderId}");
            //$pg->update(['status' => 'paid']);
        } else {
            Log::channel('fiuu')->error("Wallet credit failed {$orderId} for user {$pg->user_id}");
            $pg->update(['status' => 'failed']);
        }
        // Respond to Fiuu
        if ($nbcb == 1) {
            return response("CBTOKEN:MPSTATOK", 200);
        }
        return response("OK", 200);
    }
 
    //return method from API fiuu
    public function return(Request $request)
    {
        Log::channel('fiuu')->info('Fiuu RETURN called', $request->all());

        $tranID   = $request->input('tranID');
        $orderId  = $request->input('orderid');
        $status   = $request->input('status');
        $domain   = $request->input('domain');
        $amount   = $request->input('amount');
        $currency = $request->input('currency');
        $appcode  = $request->input('appcode');
        $paydate  = $request->input('paydate');
        $skey     = $request->input('skey');

        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        $secKey = $gw->secret_key;

        // Verify return signature
        $key0 = md5($tranID.$orderId.$status.$domain.$amount.$currency);
        $key1 = md5($paydate.$domain.$key0.$appcode.$secKey);
        $validSignature = ($skey === $key1);

        // Look up transaction in DB
        $transaction = PaymentGatewayTransaction::where('order_id', $orderId)->first();

        // Decide outcome
        if ($transaction) {
            // Auto-login the customer linked to this transaction
            Auth::guard('customer')->loginUsingId($transaction->user_id);

            // Decide outcome
            $isPaid = $transaction->status === 'paid';
            $showSuccess = $validSignature && ($status === "00" || $isPaid);

            if ($showSuccess) {
                return redirect()->route('customer.dashboard')
                    ->with('success', "Top-up successful! Order {$orderId}, Amount RM {$amount}");
            }
        }
        return redirect()->route('customer.dashboard')
            ->with('error', "Payment failed or session expired for Order {$orderId}, Amount RM {$amount}");
/**
        $isPaid = $transaction && $transaction->status === 'paid';
        $showSuccess = $validSignature && ($status === "00" || $isPaid);

        if ($showSuccess) {
            return view('ewallet.success', [
                'orderId' => $transaction->order_id ?? $orderId,
                'amount'  => $transaction->amount ?? $amount,
                'date'    => $transaction->updated_at ?? now(),
            ]);
        }

        return view('ewallet.failed', [
            'orderId' => $transaction->order_id ?? $orderId,
            'amount'  => $transaction->amount ?? $amount,
            'date'    => $transaction->updated_at ?? now(),
        ]);
        */
    }
}
