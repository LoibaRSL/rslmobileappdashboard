<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = [
            ['name' => 'registration.view', 'display_name' => 'View Registrations', 'module' => 'registration'],
            ['name' => 'registration.approve', 'display_name' => 'Approve Registrations', 'module' => 'registration'],
            ['name' => 'registration.reject', 'display_name' => 'Reject Registrations', 'module' => 'registration'],
            ['name' => 'registration.assign', 'display_name' => 'Assign Registrations', 'module' => 'registration'],
            ['name' => 'registration.reassign', 'display_name' => 'Reassign Registrations', 'module' => 'registration'],
            ['name' => 'registration.dashboard', 'display_name' => 'Access Registration Dashboard', 'module' => 'registration'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        $roleIds = DB::table('roles')
            ->whereIn('name', ['digital_services', 'admin'])
            ->pluck('id');

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_column($permissions, 'name'))
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('name', ['registration.assign', 'registration.reassign'])
            ->pluck('id');

        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
