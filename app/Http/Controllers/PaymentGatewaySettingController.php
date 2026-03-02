<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentGatewaySetting;

class PaymentGatewaySettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $settings = PaymentGatewaySetting::paginate(10);
        return view('payment_gateway.index', compact('settings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payment_gateway.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|string|max:255',
            'terminal_id' => 'nullable|string|max:255',
            'app_id'      => 'nullable|string|max:255',
            'client_id'   => 'nullable|string|max:255',
            'secret_key'  => 'nullable|string',
            'public_key'  => 'nullable|string',
            'private_key' => 'nullable|string',
            'api_key'     => 'nullable|string|max:255',
            'status'      => 'required|in:active,disable',
            'sandbox'     => 'required|in:on,off',
        ]);

        PaymentGatewaySetting::create($validated);
        return redirect()->route('payment_gateway.index')->with('success','Payment Gateway added successfully.');
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
    public function edit(PaymentGatewaySetting $paymentGateway)
    {
        return view('payment_gateway.edit', compact('paymentGateway'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PaymentGatewaySetting $paymentGateway)
    {
        $validated = $request->validate([
            'merchant_id' => 'required|string|max:255',
            'terminal_id' => 'nullable|string|max:255',
            'app_id'      => 'nullable|string|max:255',
            'client_id'   => 'nullable|string|max:255',
            'secret_key'  => 'nullable|string',
            'public_key'  => 'nullable|string',
            'private_key' => 'nullable|string',
            'api_key'     => 'nullable|string|max:255',
            'status'      => 'required|in:active,disable',
            'sandbox'     => 'required|in:on,off',
        ]);

        $paymentGateway->update($validated);
        return redirect()->route('payment_gateway.index')->with('success','Payment Gateway updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentGatewaySetting $paymentGateway)
    {
        $paymentGateway->delete();
        return redirect()->route('payment_gateway.index')->with('success','Payment Gateway deleted successfully.');
    }

}
