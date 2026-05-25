<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;

class RoleManagementController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions', 'users')->get();
        $permissions = Permission::all()->groupBy('module');
        
        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function create()
    {
        $permissions = Permission::all()->groupBy('module');
        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|unique:roles|max:255|regex:/^[a-z_]+$/',
            'display_name' => 'required|max:255',
            'description' => 'nullable',
            'level' => 'integer|min:1|max:5',
            'permissions' => 'array'
        ]);

        $role = Role::create($validated);
        
        if ($request->has('permissions')) {
            $role->permissions()->attach($request->permissions);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully!');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all()->groupBy('module');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'display_name' => 'required|max:255',
            'description' => 'nullable',
            'level' => 'integer|min:1|max:5',
            'permissions' => 'array'
        ]);

        $role->update($validated);
        $role->permissions()->sync($request->permissions ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully!');
    }

    public function destroy(Role $role)
    {
        // Prevent deleting admin role
        if ($role->name === 'admin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete the Admin role!');
        }
        
        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully!');
    }

    public function users(Role $role)
    {
        $users = $role->users;
        $allUsers = User::all();
        
        return view('admin.roles.users', compact('role', 'users', 'allUsers'));
    }

    public function assignUser(Request $request, Role $role)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);
        
        $user = User::find($validated['user_id']);
        $user->roles()->attach($role);
        
        return redirect()->route('admin.roles.users', $role)
            ->with('success', 'User assigned to role successfully!');
    }

    public function removeUser(Role $role, User $user)
    {
        $user->roles()->detach($role);
        
        return redirect()->route('admin.roles.users', $role)
            ->with('success', 'User removed from role successfully!');
    }
}
