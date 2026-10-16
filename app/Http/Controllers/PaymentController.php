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
use App\Services\FiuuSignature;
use App\Services\MachineStartService;
use App\Services\DevicePaymentSettlement;
use App\Support\OrderId;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
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

        // Refuse BEFORE taking money if the controller can't accept this
        // many pulses in one start (e.g. firmware limit) — otherwise the
        // customer pays and the machine gets nothing.
        $device = $deviceOutlet->device;
        abort_if(
            !$device || (float) $device->pulse_price <= 0 || $device->pulsesFor($price) > \App\Models\Device::maxPulsesPerStart(),
            422,
            'This machine is not configured correctly for that price. Please choose another machine or contact the outlet.'
        );

        return $price;
    }

    /**
     * Signed links to the post-payment "start machine" pages.
     *
     * These replace the old Auth::guard('customer')->loginUsingId() on the
     * return URL. The signature proves the link came from us (after a
     * verified payment or a wallet charge), so the page can show the
     * order without logging anyone in. Relative signatures are used so the
     * link stays valid behind Nginx Proxy Manager regardless of http/https.
     */
    public static function signedStartUrl(DeviceTransaction $transaction, bool $guest = false): string
    {
        return URL::temporarySignedRoute(
            $guest ? 'guest.devices.start' : 'customer.devices.start',
            now()->addHours(2),
            ['transaction' => $transaction->id],
            absolute: false
        );
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
        $orderId = OrderId::make(OrderId::DEVICE_GATEWAY);
        $amountFormatted = number_format($amount, 2, '.', '');
        // Fetch active gateway settings
        try {
            $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Handle the error: Log, redirect, or return a default value
            return redirect()->back()->with('error', 'Active payment gateway not found.');
        }
        // Build signature vcode=amount+merchantid+orderid+verify key
        $signature = FiuuSignature::vcode($amountFormatted, $gw, $orderId);
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

    public function payWithEwallet(Request $request, EwalletService $wallet, MachineStartService $starter)
    {
        $request->validate([
            'device_outlet_id' => 'required|exists:device_has_outlet,id',
            'mode' => ['required', Rule::in(array_keys(self::MODE_PRICE_COLUMNS))],
        ]);

        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            // The guest QR page also posts here; a wallet needs an account.
            return redirect()->route('customer.login')->with('error', 'Please log in to pay with your e-wallet.');
        }

        $deviceOutlet = DeviceOutlet::where('id', $request->device_outlet_id)->firstOrFail();
        if (!$deviceOutlet->is_online) {
            return back()->with('error', 'This machine is currently offline and unable to accept payments. Please try another machine.');
        }

        $amount = $this->resolveModeAndPrice($deviceOutlet, $request->mode);

        // Everything below runs in ONE database transaction:
        //   lock wallet -> check balance -> deduct -> ledger row ->
        //   device_transactions row -> queue machine start (sent after commit).
        $result = DB::transaction(function () use ($customer, $deviceOutlet, $amount, $request, $wallet, $starter) {
            // Lock the wallet row first so two parallel taps queue up here.
            // Then the double-tap guard: same customer, same machine,
            // wallet-paid in the last 15 s -> treat as the same purchase.
            \App\Models\EwalletAccount::where('user_id', $customer->id)->lockForUpdate()->first();

            $recent = DeviceTransaction::where('customer_id', $customer->id)
                ->where('device_outlet_id', $deviceOutlet->id)
                ->where('provider', 'ewallet')
                ->where('created_at', '>=', now()->subSeconds(15))
                ->latest('id')
                ->first();
            if ($recent) {
                return ['duplicate', $recent];
            }

            $transaction = $wallet->spendForDevice(
                $customer->id, $deviceOutlet, $amount, $request->mode, OrderId::make(OrderId::DEVICE_WALLET)
            );
            if (!$transaction) {
                return ['insufficient', null];
            }

            $starter->dispatchForTransaction($transaction);

            return ['ok', $transaction];
        });

        [$outcome, $transaction] = $result;

        if ($outcome === 'insufficient') {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        return redirect(self::signedStartUrl($transaction))
            ->with('success', $outcome === 'duplicate'
                ? 'This payment was already made a moment ago — no extra charge.'
                : 'Payment successful via E-Wallet!');
    }

    /**
     * Fiuu server-to-server callback for machine payments (guest + customer).
     * Signature first, then hand off to the idempotent settlement.
     */
    public function callback(Request $r, DevicePaymentSettlement $settlement)
    {
        Log::channel('fiuu')->info('Fiuu device CALLBACK received', $r->except(['skey']));

        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();

        if (!FiuuSignature::verify($r->all(), $gw)) {
            Log::channel('fiuu')->warning('Invalid signature on device callback', ['order_id' => $r->input('orderid')]);
            return response('Invalid signature', 400);
        }

        [$result] = $settlement->settle($r->all(), 'callback');

        if ($result === DevicePaymentSettlement::NOT_FOUND) {
            return response('Transaction not found', 404);
        }

        // Always acknowledge, including duplicates, so Fiuu stops retrying.
        return (int) $r->input('nbcb') === 1
            ? response('CBTOKEN:MPSTATOK', 200)
            : response('Callback processed', 200);
    }

    /**
     * Browser return for logged-in customers (after the Fiuu page).
     *
     * SECURITY: this used to call Auth::guard('customer')->loginUsingId()
     * for whoever presented an order id — before checking the signature.
     * It no longer logs anyone in. If the signature is valid we also
     * settle here (idempotently) in case the browser beats the callback;
     * then we redirect to a signed result page.
     */
    public function return(Request $request, DevicePaymentSettlement $settlement)
    {
        return $this->handleBrowserReturn($request, $settlement, guest: false);
    }

    private function handleBrowserReturn(Request $request, DevicePaymentSettlement $settlement, bool $guest)
    {
        Log::channel('fiuu')->info('Fiuu device RETURN', $request->except(['skey']));

        $orderId = (string) $request->input('orderid');
        $gw = PaymentGatewaySetting::where('status', 'active')->first();

        if ($gw && FiuuSignature::verify($request->all(), $gw)) {
            $settlement->settle($request->all(), 'return');
        } else {
            Log::channel('fiuu')->warning('Return with invalid/missing signature (display only, nothing settled)', ['order_id' => $orderId]);
        }

        $transaction = $orderId !== '' ? DeviceTransaction::where('order_id', $orderId)->first() : null;

        if (!$transaction) {
            $msg = 'We could not find that payment. If you were charged, please contact the outlet with your bank reference.';
            abort_if($guest, 404, $msg);
            return redirect()->route('customer.dashboard')->with('error', $msg);
        }

        // The result page shows the REAL stored status (polled), never the
        // unverified query-string status, so a forged return can't fake success.
        return redirect(self::signedStartUrl($transaction, $guest));
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

        $orderId = OrderId::make(OrderId::DEVICE_GATEWAY);
        $amountFormatted = number_format($amount, 2, '.', '');
        // Fetch active gateway settings
        try {
            $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        } catch (ModelNotFoundException $e) {
            // Handle the error: Log, redirect, or return a default value
            return redirect()->back()->with('error', 'Active payment gateway not found.');
        }
        // Build signature vcode=amount+merchantid+orderid+verify key
        $signature = FiuuSignature::vcode($amountFormatted, $gw, $orderId);
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

    public function returnQRPayment(Request $request, DevicePaymentSettlement $settlement)
    {
        // Signature check restored (it was commented out) and the same
        // idempotent settlement as the logged-in flow.
        return $this->handleBrowserReturn($request, $settlement, guest: true);
    }
}
