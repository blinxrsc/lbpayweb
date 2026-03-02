<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\DeviceTransaction;
use App\Services\DeviceActivationService;

class DeviceTransactionController extends Controller
{
    public function initiate(Request $request)
    {
        $transaction = DeviceTransaction::create([
            'customer_id'     => Auth::guard('customer')->id(),
            'device_outlet_id'=> $request->device_outlet_id,
            'amount'          => $request->amount,
            'mode'            => $request->mode,
            'duration'        => $request->duration,
            'method'          => $request->method, // fiuu or ewallet
            'status'          => 'initiated',
        ]);


        // If method = fiuu → redirect to Fiuu API
        // If method = ewallet → debit balance immediately
    }


    public function markPaid(DeviceTransaction $transaction, $gatewayRef = null)
    {
        $transaction->update([
            'status' => 'paid',
            'gateway_ref' => $gatewayRef,
        ]);
    }


    public function activate(DeviceTransaction $transaction, DeviceActivationService $activationService)
    {
        $activationService->activate($transaction->deviceOutlet, $transaction->mode, $transaction->duration);
        $transaction->update(['status' => 'activated']);
    }


    public function complete(DeviceTransaction $transaction)
    {
        $transaction->update(['status' => 'completed']);
    }


    public function fail(DeviceTransaction $transaction)
    {
        $transaction->update(['status' => 'failed']);
    }


    public function refund(DeviceTransaction $transaction)
    {
        $transaction->update(['status' => 'refunded']);
        // Add wallet credit back if method = ewallet
    }

}
