<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        if (!Gate::allows('view_users')) {
            abort(403);
        }

        $search = $request->get('search');
        $roleFilter = $request->get('role');

        $users = User::with('roles')->when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('wso2_username', 'like', "%{$search}%");
        })->when($roleFilter, function ($query, $roleFilter) {
            return $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('role_id', $roleFilter);
            });
        })->paginate(15);

        $roles = Role::all();
        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'inactive' => User::where('is_active', false)->count(),
            'wso2_users' => User::whereNotNull('wso2_id')->count()
        ];

        return view('admin.users.index', compact('users', 'roles', 'stats', 'search', 'roleFilter'));
    }

    public function edit(User $user)
    {
        if (!Gate::allows('edit_users')) {
            abort(403);
        }

        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();

        return view('admin.users.edit', compact('user', 'roles', 'userRoles'));
    }

    public function update(Request $request, User $user)
    {
        if (!Gate::allows('edit_users')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'is_active' => 'boolean'
        ]);

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function assignRoles(Request $request, User $user)
    {
        if (!Gate::allows('assign_roles')) {
            abort(403);
        }

        $validated = $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $user->roles()->sync($validated['roles'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Roles assigned successfully!'
        ]);
    }

    public function toggleStatus(User $user)
    {
        if (!Gate::allows('edit_users')) {
            abort(403);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'success' => true,
            'is_active' => $user->is_active,
            'message' => 'User status updated!'
        ]);
    }

    public function destroy(User $user)
    {
        if (!Gate::allows('delete_users')) {
            abort(403);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    public function syncFromWSO2()
    {
        if (!Gate::allows('create_users')) {
            abort(403);
        }

        // This would call WSO2 SCIM API to fetch users
        // Implement based on your WSO2 SCIM endpoint
        
        return redirect()->route('admin.users.index')
            ->with('success', 'Users synced from WSO2!');
    }
}