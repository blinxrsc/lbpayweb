<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Manager;

class ManagerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $managers = Manager::paginate(10);
        return view('managers.index', compact('managers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('managers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:managers',
            'phone' => 'nullable|string|max:20',
            'ssm' => 'nullable|string|max:20',
        ]);
        Manager::create($validated);
        return redirect()->route('managers.index')->with('success', 'Manager created successfully.');
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
    public function edit(Manager $manager)
    {
        return view('managers.edit', compact('manager'));
    }

    /**
     * Update the specified resource in storage.
     */
    //public function update(Request $request, string $id)
    public function update(Request $request, Manager $manager)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:managers,email,' . $manager->id,
            'phone' => 'nullable|string|max:20',
            'ssm' => 'nullable|string|max:20',
        ]);
        $manager->update($validated);
        return redirect()->route('managers.index')->with('success', 'Manager updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    //public function destroy(string $id)
    public function destroy(Manager $manager)
    {
        $manager->delete();
        return redirect()->route('managers.index')->with('success', 'Manager deleted successfully.');
    }
}
