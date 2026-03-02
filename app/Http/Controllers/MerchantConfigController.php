<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MerchantConfig;
use App\Models\DeviceTransaction;

class MerchantConfigController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'company_name'  => 'sometimes|string|max:255',
            'email'         => 'sometimes|email',
            'logo'          => 'sometimes|image|mimes:jpg,png,jpeg|max:2048',
            'reg_no'        => 'sometimes|string|max:20', 
            'logo_path'     => 'sometimes|string|max:255', 
            'address'       => 'sometimes|string',
            //'city'          => 'required|string|max:255',
            //'state'         => 'required|string|max:255',
            //'country'       => 'required|string|max:255',
            'website'       => 'sometimes|string|max:255',
            'support_number'=> 'sometimes|string|max:20',
            'toll_free'     => 'sometimes|string|max:20',
        ]);

        $config = MerchantConfig::firstOrCreate(['id' => 1]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');
            $config->logo_path = $path;
        }

        $config->update($request->except('logo'));
        
        return back()->with('success', 'Merchant information updated successfully!');
    }
    
    public function edit(Request $r)
    {
        // 1. Get the Merchant Info we saved earlier
        $merchant = MerchantConfig::first();

        // 2. Get the specific payment details
        //$transaction = \App\Models\Transaction::findOrFail($m);

        return view('admin.merchant.setting', compact('merchant'));
    }

    public function showReceipt()
    {
        // 1. Get the Merchant Info we saved earlier
        $merchant = MerchantConfig::first();

        // 2. Get the specific payment details
        //$transaction = \App\Models\Transaction::findOrFail($transactionId);
        $transaction = DeviceTransaction::first();

        return view('admin.merchant.invoice', compact('merchant','transaction'));
    }
}
