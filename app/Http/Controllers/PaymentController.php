<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\PaymentGatewayTransaction;
use App\Services\EwalletService;
use App\Services\DeviceActivationService;
use App\Models\PaymentGatewaySetting;
use App\Models\DeviceOutlet;
use App\Models\DeviceTransaction;
use App\Models\Customer;
use App\Jobs\SendMqttCommand;

class PaymentController extends Controller
{
    /**
     * Maps a customer-facing "mode" key to the Device column that holds its
     * price. The server always computes the charged amount from this table
     * — never from a client-supplied amount — so nobody can pay less than
     * the real price for a mode by editing the hidden form field.
     */
    private const MODE_PRICE_COLUMNS = [
        'washer_cold' => 'washer_cold_price',
        'washer_warm' => 'washer_warm_price',
        'washer_hot'  => 'washer_hot_price',
        'dryer_low'   => 'dryer_low_price',
        'dryer_med'   => 'dryer_med_price',
        'dryer_hi'    => 'dryer_hi_price',
    ];

    /**
     * Resolves the mode key against the device's own pricing, ignoring
     * whatever the client sent as "amount". Aborts with a clear error if
     * the mode doesn't match this machine's type (e.g. a dryer mode on a
     * washer) or if the device has no price configured for it.
     */
    private function resolveModeAndPrice(DeviceOutlet $deviceOutlet, string $mode): float
    {
        $column = self::MODE_PRICE_COLUMNS[$mode] ?? null;

        $validForType = $deviceOutlet->machine_type === 'Washer'
            ? str_starts_with($mode, 'washer_')
            : str_starts_with($mode, 'dryer_');

        abort_unless($column && $validForType, 422, 'Invalid mode selected for this machine.');

        $price = (float) $deviceOutlet->device->{$column};
        abort_if($price <= 0, 422, 'This machine has no price configured for that mode yet.');

        return $price;
    }

    /**
     * Single place that actually tells the ESP32 to start the machine.
     * Called once a transaction is confirmed paid — from the gateway
     * callback (server-to-server, the reliable source of truth) and from
     * the e-wallet path (which pays instantly, no external callback).
     * Pulses are computed here from the device's own pulse_price, same as
     * the technician app and admin dashboard — never trusted from
     * anywhere else.
     */
    private function dispatchMachineStart(DeviceTransaction $transaction): void
    {
        $deviceOutlet = $transaction->deviceOutlet;
        $device = $deviceOutlet?->device;

        if (!$device || (float) $device->pulse_price <= 0) {
            Log::error('Cannot start machine: no pulse_price configured', ['transaction_id' => $transaction->id]);
            return;
        }

        $pulses = (int) ceil($transaction->amount / $device->pulse_price);
        $mode = $transaction->meta['mode'] ?? null;

        // Generated here (not left to SendMqttCommand's internal default)
        // so we have it on hand to poll for the firmware's ack afterward.
        // SendMqttCommand's REMOTE_START branch merges $payload over its
        // own auto-generated op_id, so passing one here overrides it.
        $opId = (string) Str::uuid();

        SendMqttCommand::dispatch($device->serial_number, 'REMOTE_START', [
            'op_id'  => $opId,
            'type'   => $mode ?? 'device',
            'price'  => (float) $transaction->amount,
            'pulses' => $pulses,
        ], userId: null, customerId: $transaction->customer_id);

        $transaction->update([
            'status' => DeviceTransaction::STATUS_ACTIVATED,
            'meta' => array_merge($transaction->meta ?? [], ['op_id' => $opId]),
        ]);
    }

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
        $preselectedMode = session()->pull('pay.mode');
        return view('customer.payment.confirm', compact('deviceOutlet', 'preselectedMode'));
    }

    /**
     * Polled by the post-payment pages to show the real-time REMOTE_START
     * ack progression instead of an optimistic "it's probably starting"
     * message. Deliberately returns only a status string + mode — nothing
     * sensitive — since this endpoint doesn't require the caller to be the
     * transaction's own customer (a guest checkout has no account to check
     * against anyway).
     */
    public function ackStatus(DeviceTransaction $transaction)
    {
        $opId = $transaction->meta['op_id'] ?? null;

        if (!$opId) {
            // No op_id yet means the payment hasn't triggered a start
            // attempt at all (still processing, or payment failed).
            return response()->json(['status' => $transaction->status]);
        }

        $ackJson = Redis::get("start_ack:{$opId}");

        if (!$ackJson) {
            // Command was sent but no ack has arrived yet — could be a
            // slow/offline device, or the ack simply hasn't been polled
            // and stored yet.
            return response()->json(['status' => 'awaiting_device']);
        }

        $ack = json_decode($ackJson, true);
        return response()->json(['status' => $ack['status'] ?? 'unknown']);
    }
    public function confirmQR(DeviceOutlet $deviceOutlet)
    {
        return view('customer.payment.qr-confirm', compact('deviceOutlet'));
    }

    public function initiateDevicePayment(Request $r) 
    {
        $r->validate([
            'device_outlet_id' => 'required|exists:device_has_outlet,id',
            'mode' => ['required', Rule::in(array_keys(self::MODE_PRICE_COLUMNS))],
        ]);

        $deviceOutlet = DeviceOutlet::where('id', $r->device_outlet_id)->firstOrFail();
        if (!$deviceOutlet->is_online) {
            return redirect()->back()->with('error', 'This machine is currently offline and unable to accept payments. Please try another machine.');
        }

        $amount = $this->resolveModeAndPrice($deviceOutlet, $r->mode);

        $customer = auth('customer')->user(); //$customerId = auth('customer')->id();
        $orderId = 'ORD-M-' . now()->format('YmdHis') . '-' . $customer->id;
        $amountFormatted = number_format($amount, 2, '.', '');
        // Fetch active gateway settings
        try {
            $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Handle the error: Log, redirect, or return a default value
            return redirect()->back()->with('error', 'Active payment gateway not found.');
        }
        // Build signature vcode=amount+merchantid+orderid+verify key
        $signature = md5($amountFormatted . $gw->merchant_id . $orderId . $gw->private_key);
        // Save transaction record
        $txn = DeviceTransaction::create([
            'customer_id'  => $customer->id,
            'device_outlet_id' => $r->device_outlet_id,
            'amount'   => $amount,
            'currency' => 'MYR',
            'status'   => DeviceTransaction::STATUS_INITIATED,
            'provider' => DeviceTransaction::PROVIDER_FIUU,
            'order_id' => $orderId,
            'request_payload'  => [
                'merchant_id' => $gw->merchant_id,
                'amount'      => $amount,
                'order_id'    => $orderId,
                'signature'   => $signature,
            ],
            
            'meta'        => [
                'type' => 'device',
                'mode' => $r->mode,
                'device_outlet_id' => $r->device_outlet_id,
            ],

        ]);
        // Render topup form with gateway values
        return view('customer.payment.device-form', [
            'amount'    => $amountFormatted,
            'orderId'   => $orderId,
            'merchantId'=> $gw->merchant_id,
            'signature' => $signature,
            'deviceOutlet' => $deviceOutlet,
        ]);       
    }

    public function payWithEwallet(Request $request)
    {
        $request->validate([
            'device_outlet_id' => 'required|exists:device_has_outlet,id',
            'mode' => ['required', Rule::in(array_keys(self::MODE_PRICE_COLUMNS))],
        ]);

        $customer = Auth::guard('customer')->user();

        $deviceOutlet = DeviceOutlet::where('id', $request->device_outlet_id)->firstOrFail();
        if (!$deviceOutlet->is_online) {
            return back()->with('error', 'This machine is currently offline and unable to accept payments. Please try another machine.');
        }

        $amount = $this->resolveModeAndPrice($deviceOutlet, $request->mode);

        if ($customer->ewalletAccount->credit_balance < $amount) {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        // Deduct balance
        $customer->ewalletAccount->decrement('credit_balance', $amount);

        // Log transaction. NOTE: this previously referenced a `Payment`
        // model that doesn't exist anywhere in this app (guaranteed fatal
        // error on every wallet payment) — every other payment path uses
        // DeviceTransaction, so this now does too.
        $orderId = 'ORD-W-' . now()->format('YmdHis') . '-' . $customer->id;
        $transaction = DeviceTransaction::create([
            'customer_id' => $customer->id,
            'device_outlet_id' => $request->device_outlet_id,
            'amount' => $amount,
            'currency' => 'MYR',
            'status' => DeviceTransaction::STATUS_PAID,
            'provider' => 'ewallet',
            'order_id' => $orderId,
            'meta' => [
                'type' => 'device',
                'mode' => $request->mode,
                'device_outlet_id' => $request->device_outlet_id,
            ],
        ]);

        // Actually start the machine — this step was entirely missing
        // before, so a wallet payment deducted balance but never signaled
        // the ESP32 at all.
        $this->dispatchMachineStart($transaction);

        return redirect()->route('customer.devices.start', $transaction->id)
            ->with('success', 'Payment successful via E-Wallet!');
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
        $transaction = DeviceTransaction::where('order_id', $orderId)->first();

        if (!$transaction) {
            Log::channel('fiuu')->error("Transaction not found", ['order_id' => $orderId]);
            return response("Transaction not found", 404);
        }

        $transaction->update([
            'provider_txn_id'  => $tranID,
            'response_payload' => $r->all(),
        ]);

        // 3. Update transaction status and start the machine
        if ($status === '00') {
            $transaction->update(['status' => 'paid']);
            Log::channel('fiuu')->info("Transaction marked paid", ['id' => $transaction->id]);

            // Tell the ESP32 to start. This was previously commented out
            // entirely, so a successful gateway payment never actually
            // started the machine.
            $this->dispatchMachineStart($transaction);
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
        $isLoggedIn = auth('customer')->check();

        $r->validate([
            'device_outlet_id' => 'required|exists:device_has_outlet,id',
            'mode' => ['required', Rule::in(array_keys(self::MODE_PRICE_COLUMNS))],
            // Only required for a genuine guest — an already-logged-in
            // customer's own contact details are used instead.
            'guest_name'  => $isLoggedIn ? 'nullable|string' : 'required|string|max:255',
            'guest_email' => $isLoggedIn ? 'nullable|email' : 'required|email|max:255',
            'guest_phone' => $isLoggedIn ? 'nullable|string' : 'required|string|max:20',
        ]);

        $deviceOutlet = DeviceOutlet::where('id', $r->device_outlet_id)->firstOrFail();
        if (!$deviceOutlet->is_online) {
            return redirect()->back()->with('error', 'This machine is currently offline and unable to accept payments. Please try another machine.');
        }

        $amount = $this->resolveModeAndPrice($deviceOutlet, $r->mode);

        // Support the (uncommon but possible) case where someone is
        // already logged in when they land on this guest QR page — use
        // their real account instead of asking for contact details again.
        $customer = $isLoggedIn ? auth('customer')->user() : null;

        $billName  = $customer->name          ?? $r->guest_name;
        $billEmail = $customer->email         ?? $r->guest_email;
        $billPhone = $customer->phone_number  ?? $r->guest_phone;

        $orderId = 'ORD-M-' . now()->format('YmdHis') . '-' . ($customer->id ?? 'guest');
        $amountFormatted = number_format($amount, 2, '.', '');
        // Fetch active gateway settings
        try {
            $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Handle the error: Log, redirect, or return a default value
            return redirect()->back()->with('error', 'Active payment gateway not found.');
        }
        // Build signature vcode=amount+merchantid+orderid+verify key
        $signature = md5($amountFormatted . $gw->merchant_id . $orderId . $gw->private_key);
        // Save transaction record. customer_id is nullable specifically so
        // a genuine guest checkout (no account) can still be recorded —
        // this used to hard-code customer_id => 0, which doesn't exist as
        // a customer and would fail the (then-required) foreign key on
        // every single guest payment attempt.
        $txn = DeviceTransaction::create([
            'customer_id'  => $customer->id ?? null,
            'device_outlet_id' => $r->device_outlet_id,
            'amount'   => $amount,
            'currency' => 'MYR',
            'status'   => DeviceTransaction::STATUS_INITIATED,
            'provider' => DeviceTransaction::PROVIDER_FIUU,
            'order_id' => $orderId,
            'request_payload'  => [
                'merchant_id' => $gw->merchant_id,
                'amount'      => $amount,
                'order_id'    => $orderId,
                'signature'   => $signature,
            ],
            
            'meta'        => [
                'type' => 'device',
                'mode' => $r->mode,
                'device_outlet_id' => $r->device_outlet_id,
                'guest_name'  => $customer ? null : $billName,
                'guest_email' => $customer ? null : $billEmail,
                'guest_phone' => $customer ? null : $billPhone,
            ],

        ]);
        // Render topup form with gateway values
        return view('customer.payment.qr-device-form', [
            'amount'    => $amountFormatted,
            'orderId'   => $orderId,
            'merchantId'=> $gw->merchant_id,
            'signature' => $signature,
            'billName'  => $billName,
            'billEmail' => $billEmail,
            'billPhone' => $billPhone,
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
