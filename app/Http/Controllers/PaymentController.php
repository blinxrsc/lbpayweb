<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\PaymentGatewayTransaction;
use App\Services\EwalletService;
use App\Services\DeviceActivationService;
use App\Models\PaymentGatewaySetting;
use App\Models\DeviceOutlet;
use App\Models\DeviceTransaction;
use App\Models\Customer;

class PaymentController extends Controller
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
    public function confirm(DeviceOutlet $deviceOutlet)
    {
        return view('customer.payment.confirm', compact('deviceOutlet'));
    }
    public function confirmQR(DeviceOutlet $deviceOutlet)
    {
        return view('customer.payment.qr-confirm', compact('deviceOutlet'));
    }

    public function initiateDevicePayment(Request $r) 
    {
        $r->validate(['amount' => 'required|numeric|min:1']);
        $customer = auth('customer')->user(); //$customerId = auth('customer')->id();
        $orderId = 'ORD-M-' . now()->format('YmdHis') . '-' . $customer->id;
        $amount = number_format($r->amount, 2, '.', '');
        // Fetch active gateway settings
        try {
            $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Handle the error: Log, redirect, or return a default value
            return redirect()->back()->with('error', 'Active payment gateway not found.');
        }
        // Build signature vcode=amount+merchantid+orderid+verify key
        $signature = md5($amount . $gw->merchant_id . $orderId . $gw->private_key);
        // Save transaction record
        $txn = DeviceTransaction::create([
            'customer_id'  => $customer->id,
            'device_outlet_id' => $r->device_outlet_id,
            'amount'   => $r->amount,
            'currency' => 'MYR',
            'status'   => DeviceTransaction::STATUS_INITIATED,
            'provider' => DeviceTransaction::PROVIDER_FIUU,
            'order_id' => $orderId,
            'request_payload'  => [
                'merchant_id' => $gw->merchant_id,
                'amount'      => $r->amount,
                'order_id'    => $orderId,
                'signature'   => $signature,
            ],
            
            'meta'        => [
                'type' => 'device',
                'device_outlet_id' => $r->device_outlet_id,
            ],

        ]);
        // Render topup form with gateway values
        return view('customer.payment.device-form', [
            'amount'    => $amount,
            'orderId'   => $orderId,
            'merchantId'=> $gw->merchant_id,
            'signature' => $signature,
        ]);       
    }

    public function payWithEwallet(Request $request)
    {
        $customer = Auth::guard('customer')->user();
        $amount = $request->amount;

        if ($customer->ewalletAccount->credit_balance >= $amount) {
            // Deduct balance
            $customer->ewalletAccount->decrement('credit_balance', $amount);

            // Log transaction
            Payment::create([
                'customer_id' => $customer->id,
                'device_outlet_id' => $request->device_outlet_id,
                'amount' => $amount,
                'mode' => $request->mode,
                'duration' => $request->duration,
                'status' => 'paid',
                'method' => 'ewallet',
            ]);

            // Trigger device activation flow (MQTT/IoT)
            // ...

            return redirect()->route('customer.dashboard')->with('success', 'Payment successful via E‑Wallet!');
        }

        return back()->with('error', 'Insufficient wallet balance.');
    }

    public function callback(Request $r, DeviceActivationService $activationService)
    {
        // Log the raw payload
        Log::channel('fiuu')->info('Fiuu CALLBACK received', $r->all());
        //receive data from Fiuu
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
        $meta     = $r->input('meta', []);

        // 1. Verify return signature
        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        $secKey = $gw->secret_key;
        $key0 = md5($tranID.$orderId.$status.$domain.$amount.$currency);
        $key1 = md5($paydate.$domain.$key0.$appcode.$secKey);
        if ($skey !== $key1) {
            $status = -1;
            Log::channel('fiuu')->warning("Invalid signature for order {$orderId}", [
                'expected' => $key1,
                'received' => $skey,
            ]);
            return response("Invalid signature", 400);
        }

        // 2. Find transaction     
        $pg = DeviceTransaction::where('order_id', $orderId)->firstOrFail();
        // Don’t set to paid yet — leave as initiated/failed
        $pg->update([
            'provider_txn_id'  => $tranID,
            'response_payload' => $r->all(),
            'status'           => $status === '00' ? 'initiated' : 'failed',
        ]);

        $transaction = DeviceTransaction::where('order_id', $orderId)->first();

        if (!$transaction) {
            Log::channel('fiuu')->error("Transaction not found", ['order_id' => $orderId]);
            return response("Transaction not found", 404);
        }

        // 3. Update transaction status
        if ($status === '00') {
            $transaction->update(['status' => 'paid']);
            Log::channel('fiuu')->info("Transaction marked paid", ['id' => $transaction->id]);

            // 4. Trigger IoT activation if type == device
            //if (isset($meta['type']) && $meta['type'] === 'device') {
            //    $activationService->activate($transaction->deviceOutlet, $transaction->mode, $transaction->duration);
            //    $transaction->update(['status' => 'activated']);
            //    Log::channel('fiuu')->info("Device activated", ['id' => $transaction->id]);
            //}
        } else  {
            $transaction->update(['status' => 'failed']);
            Log::channel('fiuu')->warning("Transaction failed", ['id' => $transaction->id]);
        } 
        // Respond to Fiuu
        if ($nbcb == 1) {
            return response("CBTOKEN:MPSTATOK", 200);
        }
        return response("Callback processed", 200);
    }


    public function return(Request $request)
    {
        // Show success/failure message to customer
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
        $transaction = DeviceTransaction::where('order_id', $orderId)->first();

        // Decide outcome
        if ($transaction) {
            // Auto-login the customer linked to this transaction
            Auth::guard('customer')->loginUsingId($transaction->customer_id);

            // Decide outcome
            $isPaid = $transaction->status === 'paid';
            $showSuccess = $validSignature && ($status === "00" || $isPaid);

            if ($showSuccess) {
                Log::channel('fiuu')->info('Fiuu RETURN called', ['id' => $transaction->id]);
                return redirect()->route('customer.devices.start', $transaction->id)
                    ->with('success', "Payment completed! Order {$orderId}, Amount RM {$amount}");
            }
        }
        Log::channel('fiuu')->warning("Payment failed", ['id' => $transaction->id]);
        return redirect()->route('customer.devices.start', $transaction->id)
            ->with('error', "Payment failed or session expired for Order {$orderId}, Amount RM {$amount}");
    }

    public function initiateQRPayment(Request $r) 
    {
        $r->validate(['amount' => 'required|numeric|min:1']);
        $customer = 0;
        $orderId = 'ORD-M-' . now()->format('YmdHis') . '-' . $customer;
        $amount = number_format($r->amount, 2, '.', '');
        // Fetch active gateway settings
        try {
            $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Handle the error: Log, redirect, or return a default value
            return redirect()->back()->with('error', 'Active payment gateway not found.');
        }
        // Build signature vcode=amount+merchantid+orderid+verify key
        $signature = md5($amount . $gw->merchant_id . $orderId . $gw->private_key);
        // Save transaction record
        $txn = DeviceTransaction::create([
            'customer_id'  => $customer,
            'device_outlet_id' => $r->device_outlet_id,
            'amount'   => $r->amount,
            'currency' => 'MYR',
            'status'   => DeviceTransaction::STATUS_INITIATED,
            'provider' => DeviceTransaction::PROVIDER_FIUU,
            'order_id' => $orderId,
            'request_payload'  => [
                'merchant_id' => $gw->merchant_id,
                'amount'      => $r->amount,
                'order_id'    => $orderId,
                'signature'   => $signature,
            ],
            
            'meta'        => [
                'type' => 'device',
                'device_outlet_id' => $r->device_outlet_id,
            ],

        ]);
        // Render topup form with gateway values
        $customer = Customer::where('id', $customer)->firstOrFail();
        $deviceOutlet = DeviceOutlet::where('id', $r->device_outlet_id)->firstOrFail();
        return view('customer.payment.qr-device-form', [
            'amount'    => $amount,
            'orderId'   => $orderId,
            'merchantId'=> $gw->merchant_id,
            'signature' => $signature,
            'customer'  => $customer,
            'deviceOutlet' => $deviceOutlet,
        ]);       
    }

    public function returnQRPayment(Request $request)
    {
        // Show success/failure message to customer
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

        //$gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        //$secKey = $gw->secret_key;

        // Verify return signature
        //$key0 = md5($tranID.$orderId.$status.$domain.$amount.$currency);
        //$key1 = md5($paydate.$domain.$key0.$appcode.$secKey);
        //$validSignature = ($skey === $key1);

        // Look up transaction in DB
        $transaction = DeviceTransaction::where('order_id', $orderId)->first();

        // Decide outcome
        if ($transaction) {
            // Auto-login the customer linked to this transaction
            //Auth::guard('customer')->loginUsingId($transaction->customer_id);

            // Decide outcome
            $isPaid = $transaction->status === 'paid';
            //$showSuccess = $validSignature && ($status === "00" || $isPaid);
            $showSuccess = $status === "00" || $isPaid;

            if ($showSuccess) {
                Log::channel('fiuu')->info('Fiuu RETURN called', ['id' => $transaction->id]);
                return redirect()->route('guest.devices.start', $transaction->id);
                    //->with('success', "Payment completed! Order {$orderId}, Amount RM {$amount}");
            }
        }
        Log::channel('fiuu')->warning("Payment failed", ['id' => $transaction->id]);
        return redirect()->route('guest.devices.start', $transaction->id);
            //->with('error', "Payment failed or session expired for Order {$orderId}, Amount RM {$amount}");
    }
}
