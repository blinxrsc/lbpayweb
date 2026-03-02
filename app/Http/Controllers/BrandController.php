<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; //for manage logo storage
use App\Models\Brand;

class BrandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $brands = Brand::paginate(10);
        return view('brands.index', compact('brands'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('brands.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands',
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('logos', 'public');

            Brand::updateOrCreate(
                ['name' => $request->name],
                ['logo' => $path]
            );
        }

        return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
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
    //public function edit(string $id)
    public function edit(Brand $brand)
    {
        return view('brands.edit', compact('brand'));
    }

    /**
     * Update the specified resource in storage.
     */
    //public function update(Request $request, string $id)
    public function update(Request $request, Brand $brand)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:brands,name,' . $brand->id,
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        // Update name
        $brand->name = $request->name;

        if ($request->hasFile('logo')) {
            // 1. Delete the old logo if it exists
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }      
            // 2. Store the new logo                                 
            $path = $request->file('logo')->store('logos', 'public');
            $brand->logo = $path;
        }
        $brand->save();
        return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    //public function destroy(string $id)
    public function destroy(Brand $brand)
    {
        // Delete the file from physical storage
        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }
        // Delete the record from database
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
    }

}
