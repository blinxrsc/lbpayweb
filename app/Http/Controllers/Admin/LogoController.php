<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class LogoController extends Controller
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
    public function edit()
    {
        $logo = Setting::where('key', 'site_logo')->first();
        return view('admin.logo.edit', compact('logo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:2048',
        ]);

        $path = $request->file('logo')->store('logos', 'public');

        Setting::updateOrCreate(
            ['key' => 'site_logo'],
            ['value' => $path]
        );

        return redirect()->back()->with('success', 'Logo updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
    public function updateFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|image|mimes:png,jpg,jpeg,ico|max:1024',
        ]);

        $path = $request->file('favicon')->store('favicons', 'public');

        Setting::updateOrCreate(
            ['key' => 'site_favicon'],
            ['value' => $path]
        );

        return redirect()->back()->with('success', 'Favicon updated successfully!');
    }

}
