<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Permission::truncate();
        Role::truncate();
        DB::table('permission_role')->truncate();
        DB::table('role_user')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Create Permissions
        $permissions = $this->getPermissions();
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        // Create Roles
        $roles = $this->getRoles();
        foreach ($roles as $role) {
            Role::create($role);
        }

        // Assign Permissions to Roles
        $this->assignPermissionsToRoles();
    }

    private function getPermissions(): array
    {
        return [
            // Registration Module Permissions
            ['name' => 'registration.view', 'display_name' => 'View Registrations', 'module' => 'registration'],
            ['name' => 'registration.approve', 'display_name' => 'Approve Registrations', 'module' => 'registration'],
            ['name' => 'registration.reject', 'display_name' => 'Reject Registrations', 'module' => 'registration'],
            ['name' => 'registration.dashboard', 'display_name' => 'Access Registration Dashboard', 'module' => 'registration'],
            
            // Filing Module Permissions
            ['name' => 'filing.view', 'display_name' => 'View Filings', 'module' => 'filing'],
            ['name' => 'filing.download', 'display_name' => 'Download Attachments', 'module' => 'filing'],
            ['name' => 'filing.approve', 'display_name' => 'Approve Filings', 'module' => 'filing'],
            ['name' => 'filing.reject', 'display_name' => 'Reject Filings', 'module' => 'filing'],
            ['name' => 'filing.dashboard', 'display_name' => 'Access Filing Dashboard', 'module' => 'filing'],
            
            // User Management Permissions
            ['name' => 'users.view', 'display_name' => 'View Users', 'module' => 'user_management'],
            ['name' => 'users.create', 'display_name' => 'Create Users', 'module' => 'user_management'],
            ['name' => 'users.edit', 'display_name' => 'Edit Users', 'module' => 'user_management'],
            ['name' => 'users.delete', 'display_name' => 'Delete Users', 'module' => 'user_management'],
            ['name' => 'users.assign_roles', 'display_name' => 'Assign Roles to Users', 'module' => 'user_management'],
            
            // Role Management Permissions
            ['name' => 'roles.view', 'display_name' => 'View Roles', 'module' => 'role_management'],
            ['name' => 'roles.create', 'display_name' => 'Create Roles', 'module' => 'role_management'],
            ['name' => 'roles.edit', 'display_name' => 'Edit Roles', 'module' => 'role_management'],
            ['name' => 'roles.delete', 'display_name' => 'Delete Roles', 'module' => 'role_management'],
            
            // Dashboard Access
            ['name' => 'dashboard.view', 'display_name' => 'View Main Dashboard', 'module' => 'dashboard'],
        ];
    }

    private function getRoles(): array
    {
        return [
            [
                'name' => 'digital_services',
                'display_name' => 'Digital Services',
                'description' => 'Full access to registration module (view, approve, reject) and filing module (view, download attachments)',
                'level' => 2
            ],
            [
                'name' => 'debt_management',
                'display_name' => 'Debt Management',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'audit',
                'display_name' => 'Audit',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'records_management',
                'display_name' => 'Records Management',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'rpu',
                'display_name' => 'RPU (Revenue Protection Unit)',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'refunds',
                'display_name' => 'Refunds',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'pr',
                'display_name' => 'PR (Public Relations)',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'account_reps',
                'display_name' => 'Account Representatives',
                'description' => 'Access to filing module only (view, download attachments)',
                'level' => 3
            ],
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full access to all modules including user and role management',
                'level' => 1
            ],
        ];
    }

    private function assignPermissionsToRoles(): void
    {
        // Digital Services Permissions (Registration + Filing modules)
        $digitalServices = Role::where('name', 'digital_services')->first();
        $digitalServicesPerms = Permission::whereIn('module', ['registration', 'filing'])->get();
        $digitalServices->permissions()->attach($digitalServicesPerms);

        // Filing-only roles (Debt Management, Audit, Records Management, RPU, Refunds, PR, Account Reps)
        $filingOnlyRoles = ['debt_management', 'audit', 'records_management', 'rpu', 'refunds', 'pr', 'account_reps'];
        $filingPermissions = Permission::whereIn('name', ['filing.view', 'filing.download', 'filing.dashboard'])->get();
        
        foreach ($filingOnlyRoles as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $role->permissions()->attach($filingPermissions);
        }

        // Admin gets all permissions
        $admin = Role::where('name', 'admin')->first();
        $admin->permissions()->attach(Permission::all());

        // Dashboard access for all roles except maybe some
        $dashboardPerm = Permission::where('name', 'dashboard.view')->first();
        foreach (Role::all() as $role) {
            if (!$role->permissions->contains($dashboardPerm)) {
                $role->permissions()->attach($dashboardPerm);
            }
        }
    }
}