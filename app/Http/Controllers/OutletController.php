<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Outlet;
use App\Models\Brand;
use App\Models\Manager;
use App\Models\TypeOutlet;
use App\Models\TypeStatus;

class OutletController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $r)
    {
        // Start query with relationships
        $query = Outlet::with(['brand', 'manager','status','type']);

        // Filter by status if provided
        if ($r->filled('status')) { // 'filled' ensures it's not null or empty
            $query->where('status', $r->status);
        }
        // Example: search by outlet name
        if ($r->filled('outlet')) {
            $query->where('outlet_name', 'like', '%' . $r->outlet . '%');
        }
        // Example: search by state name
        if ($r->filled('state')) {
            $query->where('province', 'like', '%' . $r->state . '%');
        }
        // Example: search by city name
        if ($r->filled('city')) {
            $query->where('city', 'like', '%' . $r->city . '%');
        }
        // Paginate results
        $transactions = $query->paginate(20)->withQueryString();

        return view('outlets.index', compact('transactions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $brands = Brand::all();
        $managers = Manager::all();
        $statuses = TypeStatus::all();
        $types = TypeOutlet::all();
        return view('outlets.create', compact('brands', 'managers','statuses','types'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'outlet_name' => 'required|string|max:255',
            'machine_number' => 'nullable|string|max:255',
            'business_hours' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'phone' => 'required|string|max:20',
            'brand_id' => 'required|exists:brands,id',
            'status_id' => 'required|exists:type_statuses,id',
            'type_id'   => 'required|exists:type_outlets,id',
            'manager_id' => 'required|exists:managers,id',
        ]);

        Outlet::create($validated);
        return redirect()->route('outlets.index')->with('success', 'Outlet created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Outlet $outlet)
    {
        return view('outlets.show', compact('outlet'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Outlet $outlet)
    {
        $brands = Brand::all();
        $managers = Manager::all();
        $statuses = TypeStatus::all();
        $types = TypeOutlet::all();
        return view('outlets.edit', compact('outlet', 'brands', 'managers','statuses','types'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Outlet $outlet)
    {
        $validated = $request->validate([
            'outlet_name' => 'required|string|max:255',
            'machine_number' => 'nullable|string|max:255',
            'business_hours' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'address' => 'required|string',
            'longitude' => 'required|numeric',
            'latitude' => 'required|numeric',
            'phone' => 'required|string|max:20',
            'brand_id' => 'required|exists:brands,id',
            'status_id' => 'required|exists:type_statuses,id',
            'type_id'   => 'required|exists:type_outlets,id',
            'manager_id' => 'required|exists:managers,id',
        ]);

        $outlet->update($validated);

        return redirect()
            ->route('outlets.index')
            ->with('success', 'Outlet updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    //public function destroy(string $id)
    public function destroy(Outlet $outlet)
    {
        $outlet->delete();
        return redirect()->route('outlets.index')->with('success', 'Outlet deleted successfully.');
    }
}
