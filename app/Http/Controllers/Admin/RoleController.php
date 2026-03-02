<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //add 29-12-25
        $roles = Role::with('permissions')->get(); 
        return view('admin.roles.index', compact('roles'));
        //end
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get all permissions and group them by the prefix before the first dot
        $groupedPermissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0]; 
        });
        return view('admin.roles.create', compact('groupedPermissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'desc' => $request->desc
        ]);
        
        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully');
        //add 29-12-25
        //$request->validate(['name' => 'required|unique:roles']); 
        //$role = Role::create(['name' => $request->name]); 
        //$role->syncPermissions($request->permissions ?? []); 
        //return redirect()->route('roles.index')->with('success', 'Role created successfully');
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
    public function edit(Role $role)
    {
        $groupedPermissions = Permission::all()->groupBy(function($permission) {
            return explode('.', $permission->name)[0]; 
        });
        
        return view('admin.roles.edit', compact('role', 'groupedPermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array'
        ]);

        $role->update(['name' => $request->name]);
        $role->update(['desc' => $request->desc]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        //add 29-12-25
        $role->delete(); 
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully');
        //end
    }
}
