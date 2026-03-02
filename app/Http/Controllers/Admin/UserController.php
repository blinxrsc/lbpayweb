<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User; 
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Activitylog\Models\Activity;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $r)
    {
        // 1. Start query builder (do not call ->get() yet)
        $query = User::with('roles'); 
        $roles = Role::all(); 

        // 2. Add filters
        if ($r->filled('email')) {
            $query->where('email','like','%' . $r->email . '%');
        }
        // Corrected logic for filtering by roles
        if ($r->filled('roles')) {
            $roleIds = $r->roles; // Assuming the request parameter is now 'roles' (array)

            $query->whereHas('roles', function ($q) use ($roleIds) {
                $q->whereIn('roles.id', $roleIds);
            });
        }

        // 3. Paginate (this executes the query)
        $users = $query->paginate(10)->withQueryString();

        return view('admin.users.index', compact('users','roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //add 29-12-25
        $roles = Role::all(); 
        $permissions = Permission::all();
        return view('admin.users.create', compact('roles', 'permissions'));
        //end
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //add 29-12-25
        $request->validate([ 
            'name' => 'required', 
            'email' => 'required|email|unique:users', 
            'password' => 'required|min:6', 
            'role' => 'required' 
        ]); 
        
        $user = User::create([ 
            'name' => $request->name, 
            'email' => $request->email, 
            'password' => bcrypt($request->password), 
        ]); 
        
        $user->assignRole($request->role); 
        if ($request->permissions) 
        { 
            $user->syncPermissions($request->permissions); 
        }
        return redirect()->route('users.index')->with('success', 'User created successfully');
        //end
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $roles = Role::all(); 
        return view('admin.users.show', compact('user', 'roles'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //add 29-12-25
        $roles = Role::all(); 
        $permissions = Permission::all(); 
        return view('admin.users.edit', compact('user', 'roles', 'permissions'));
        //end
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
       $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id, // Correct unique validation for updates
            'role' => 'required', // Validate against the 'roles' table ID field for consistency
        ]);

        $user->update($request->only('name', 'email')); // Mass assignment requires 'name' and 'email' to be in the User model's $fillable array
        
        // Get the single role name and sync it (syncRoles accepts a single string or array)
        $roleName = Role::where('id', $request->role)->value('name');
        $user->syncRoles($roleName); // Passing a single string works to set that as the ONLY role

        // IMPORTANT: Clear the permission cache so the change is immediate
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        return redirect()->route('users.index')->with('success', 'User updated successfully');;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //add 29-12-25
        $user->delete(); 
        return redirect()->route('users.index')->with('success', 'User deleted successfully');
    }

    public function userLogs()
    {
        // Fetch the latest 50 activities with the user who performed them
        $activities = Activity::with('causer')->latest()->paginate(50);
        return view('admin.logs.user', compact('activities'));
    }
}
