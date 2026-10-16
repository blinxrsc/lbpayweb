<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use \App\Services\EwalletService;
use App\Models\PaymentGatewayTransaction;
use App\Models\PaymentGatewaySetting;
use App\Models\EwalletAccount;
use App\Services\FiuuSignature;

class GatewayController extends Controller
{
    /**
     * Fiuu server-to-server callback for e-wallet TOP-UPS.
     *
     * Old bugs fixed here:
     *  - An invalid signature only set $status = -1 and carried on, so a
     *    forged POST could still flip a pending top-up to 'failed'. Now an
     *    invalid signature is rejected with 400 and nothing is touched.
     *  - The row was set back to 'initiated' BEFORE crediting, which
     *    defeated the duplicate check, so each repeated "00" credited the
     *    wallet again. The pre-update is gone; EwalletService::settleTopup()
     *    locks the row and does nothing if it is already 'paid'.
     */
    public function callback(Request $r, EwalletService $svc)
    {
        Log::channel('fiuu')->info('Fiuu top-up CALLBACK received', $r->except(['skey']));

        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();

        if (!FiuuSignature::verify($r->all(), $gw)) {
            Log::channel('fiuu')->warning('Invalid signature on top-up callback', ['order_id' => $r->input('orderid')]);
            return response('Invalid signature', 400);
        }

        [$result] = $svc->settleTopup($r->all(), 'callback');

        if ($result === EwalletService::TOPUP_NOT_FOUND) {
            return response('Order not found', 404);
        }

        return (int) $r->input('nbcb') === 1
            ? response('CBTOKEN:MPSTATOK', 200)
            : response('OK', 200);
    }

    /**
     * Browser return after a top-up.
     *
     * SECURITY: used to call Auth::guard('customer')->loginUsingId() for
     * whoever presented an order id (signature checked only afterwards),
     * which allowed logging in as any customer. Now:
     *  - nobody is logged in here;
     *  - a valid signature also settles the top-up (idempotent), in case
     *    the browser arrives before the server callback;
     *  - the customer lands on their dashboard. The session cookie
     *    (SameSite=Lax) is sent on this top-level GET redirect, so a
     *    customer who was logged in stays logged in; anyone else is sent
     *    to the login page by the auth middleware.
     */
    public function return(Request $request, EwalletService $svc)
    {
        Log::channel('fiuu')->info('Fiuu top-up RETURN', $request->except(['skey']));

        $orderId = (string) $request->input('orderid');
        $gw = PaymentGatewaySetting::where('status', 'active')->first();
        $valid = $gw && FiuuSignature::verify($request->all(), $gw);

        if ($valid) {
            $svc->settleTopup($request->all(), 'return');
        }

        $transaction = $orderId !== '' ? PaymentGatewayTransaction::where('order_id', $orderId)->first() : null;
        $amount = $transaction ? number_format((float) $transaction->amount, 2) : null;

        if ($valid && $transaction && $transaction->status === PaymentGatewayTransaction::STATUS_PAID) {
            return redirect()->route('customer.dashboard')
                ->with('success', "Top-up successful! Order {$orderId}, Amount RM {$amount}");
        }

        if ($valid && $transaction && $transaction->status === PaymentGatewayTransaction::STATUS_INITIATED) {
            return redirect()->route('customer.dashboard')
                ->with('success', "Payment is being confirmed for order {$orderId}. Your balance will update shortly.");
        }

        return redirect()->route('customer.dashboard')
            ->with('error', 'Top-up was not completed. If you were charged, please contact support with your bank reference.');
    }
}
