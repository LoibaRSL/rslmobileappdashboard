<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // User Management
            ['name' => 'view_users', 'display_name' => 'View Users', 'module' => 'user_management'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'module' => 'user_management'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'module' => 'user_management'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'module' => 'user_management'],
            ['name' => 'assign_roles', 'display_name' => 'Assign Roles', 'module' => 'user_management'],
            
            // Role Management
            ['name' => 'view_roles', 'display_name' => 'View Roles', 'module' => 'role_management'],
            ['name' => 'create_roles', 'display_name' => 'Create Roles', 'module' => 'role_management'],
            ['name' => 'edit_roles', 'display_name' => 'Edit Roles', 'module' => 'role_management'],
            ['name' => 'delete_roles', 'display_name' => 'Delete Roles', 'module' => 'role_management'],
            
            // Dashboard
            ['name' => 'view_dashboard', 'display_name' => 'View Dashboard', 'module' => 'dashboard'],
            
            // Reports
            ['name' => 'view_reports', 'display_name' => 'View Reports', 'module' => 'reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create roles
        $adminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Has full access to all features',
            'level' => 1
        ]);

        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Can manage users and view reports',
            'level' => 2
        ]);

        $viewerRole = Role::create([
            'name' => 'viewer',
            'display_name' => 'Viewer',
            'description' => 'Can only view dashboard and reports',
            'level' => 3
        ]);

        // Assign all permissions to Super Admin
        $adminRole->permissions()->attach(Permission::all());

        // Assign limited permissions to Manager
        $managerPermissions = Permission::whereIn('name', [
            'view_users', 'edit_users', 'assign_roles', 'view_roles', 'view_dashboard', 'view_reports'
        ])->get();
        $managerRole->permissions()->attach($managerPermissions);

        // Assign view permissions to Viewer
        $viewerPermissions = Permission::whereIn('name', [
            'view_dashboard', 'view_reports'
        ])->get();
        $viewerRole->permissions()->attach($viewerPermissions);
    }
}