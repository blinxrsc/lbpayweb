<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outlet;

class CustomerOutletController extends Controller
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

    public function setOutlet(Request $request) { //When customer selects outlet
        $request->validate(['outlet_id' => 'required|exists:outlets,id']);
        session(['outlet_id' => $request->outlet_id]);

        return redirect()->route('customer.devices.index');
    }

    public function detect(Request $request) {
        $lat = $request->latitude;
        $lng = $request->longitude;

        if (!$lat || !$lng) {
            // GPS denied or unavailable → fallback
            return redirect()->route('customer.outlet.select')
                ->with('warning','Location not available. Please select your outlet manually.');
        }

        $nearestOutlet = Outlet::selectRaw(
            "id, name, latitude, longitude,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) 
            * cos(radians(longitude) - radians(?)) + sin(radians(?)) 
            * sin(radians(latitude)))) AS distance",
            [$lat, $lng, $lat]
        )
        ->orderBy('distance')
        ->first();

        if ($nearestOutlet) {
            session(['outlet_id' => $nearestOutlet->id]);
            //return redirect()->route('customer.devices.index');
            return view('customer.devices.index');
        }

        return redirect()->route('customer.outlet.select')
            ->with('error','No nearby outlet found. Please select manually.');
    }

    public function nearby() {
        return view('customer.outlets.nearby');
    }
    
    public function select()
    {
        // Load all outlets for manual selection fallback
        $outlets = Outlet::all();

        return view('customer.outlets.select', compact('outlets'));
    }

}
