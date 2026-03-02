<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Outlet;
use App\Models\DeviceTransaction;

class CustomerDeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //Filter devices by outlet stored in session
        $outletId = session('outlet_id');

        if (!$outletId) {
            return redirect()->route('customer.outlet.select')
                ->with('error','Please select an outlet first.');
        }

        //$outlet = Outlet::findOrFail($outletId);
        $outlet = Outlet::with('deviceOutlets.device')->findOrFail($outletId);
        // Option 1: get DeviceOutlet records
        $deviceOutlets = $outlet->deviceOutlets;

        // Option 2: get actual Device models directly
        $devices = $outlet->devices;

        return view('customer.devices.index', compact('devices','outlet','deviceOutlets'));
    }

    public function start(DeviceTransaction $transaction)
    {
        return view('customer.devices.start', compact('transaction'));
    }

    public function startQRDevice(DeviceTransaction $transaction)
    {
        return view('customer.devices.qr-start', compact('transaction'));
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


}
