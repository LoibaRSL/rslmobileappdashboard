<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $user = User::updateOrCreate([
            'email' => 'demo@user.com',
        ], [
            'name' => 'User',
            'username' => 'demo',
            'department' => 'Administration',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'is_active' => 1,
        ]);

        $adminRole = \App\Models\Role::where('name', 'admin')->first();
        if ($adminRole) {
            $user->roles()->sync([$adminRole->id]);
        }
    }
}
