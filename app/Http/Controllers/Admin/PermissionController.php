<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//add 29-12-25
use Spatie\Permission\Models\Permission;
//end

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //add 29-12-25
        $permissions = Permission::all(); 
        return view('admin.permissions.index', compact('permissions'));
        //end
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //add 29-12-25
        return view('admin.permissions.create');
        //end
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //add 29-12-25
        $request->validate(['name' => 'required|unique:permissions']); 
        Permission::create(['name' => $request->name]); 
        return redirect()->route('permissions.index')->with('success', 'Permission created successfully');
        //end
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
    public function edit(Permission $permission)
    {
        //add 29-12-25
        return view('admin.permissions.edit', compact('permission'));
        //end
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Permission $permission)
    {
        //add 29-12-25
        $request->validate(['name' => 'required|unique:permissions,name,' . $permission->id]); 
        $permission->update(['name' => $request->name]); 
        return redirect()->route('permissions.index')->with('success', 'Permission updated successfully');
        //end
    }

    /**
     * Remove the specified resource from storage.
     */
    //public function destroy(string $id)
    public function destroy(Permission $permission)
    {
        //add 29-12-25
        $permission->delete(); 
        return redirect()->route('permissions.index')->with('success', 'Permission deleted successfully');
        //end
    }
}
