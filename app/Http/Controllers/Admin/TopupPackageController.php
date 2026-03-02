<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TopupPackage;

class TopupPackageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $packages = TopupPackage::orderBy('topup_amount')->get();
        return view('admin.ewallet.packages', compact('packages'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.ewallet.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([ 
            'topup_amount' => 'required|numeric|min:1|unique:topup_packages,topup_amount', 
            'bonus_amount' => 'nullable|numeric|min:0', 
        ]); 
        
        TopupPackage::create($request->only('topup_amount', 'bonus_amount')); 
        return back()->with('success', 'Package created.');
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
    public function update(Request $request, TopupPackage $package){
        $request->validate([ 
            'topup_amount' => 'required|numeric|min:1', 
            'bonus_amount' => 'nullable|numeric|min:0', 
        ]); 
        
        $package->update($request->only('topup_amount', 'bonus_amount')); 
        return back()->with('success', 'Package updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function toggle(TopupPackage $package) 
    { 
        $package->is_active = !$package->is_active; 
        $package->save(); 
        return back()->with('success', 'Package status updated.'); 
    }
}
