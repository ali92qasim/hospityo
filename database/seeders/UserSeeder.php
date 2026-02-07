<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@hospityo.com',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        // Get or create Super Admin role
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Assign all permissions to Super Admin role
        $superAdminRole->syncPermissions(Permission::all());

        // Assign Super Admin role to admin user
        $admin->assignRole($superAdminRole);
    }
}