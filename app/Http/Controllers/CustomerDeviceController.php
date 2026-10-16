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

    /**
     * Post-payment page for logged-in customers.
     *
     * Previously anyone could open /customer/device/{id}/start for ANY id
     * (sequential ids -> every order's amount, machine and outlet). Now the
     * page opens only for: a signed link we generated after payment, or the
     * customer who owns the transaction.
     */
    public function start(Request $request, DeviceTransaction $transaction)
    {
        if (!$this->canView($request, $transaction)) {
            if (!auth('customer')->check()) {
                return redirect()->guest(route('customer.login'));
            }
            abort(403);
        }

        return view('customer.devices.start', compact('transaction'));
    }

    /** Post-payment page for guest QR checkouts (same access rule). */
    public function startQRDevice(Request $request, DeviceTransaction $transaction)
    {
        abort_unless($this->canView($request, $transaction), 403, 'This payment link has expired. Please scan the machine QR again.');

        return view('customer.devices.qr-start', compact('transaction'));
    }

    private function canView(Request $request, DeviceTransaction $transaction): bool
    {
        if ($request->hasValidSignature(absolute: false)) {
            return true;
        }

        $customerId = auth('customer')->id();
        return $customerId !== null && (int) $transaction->customer_id === (int) $customerId;
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
