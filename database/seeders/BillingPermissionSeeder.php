<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class BillingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create billing permissions
        $billingPermissions = [
            'view bills',
            'create bills',
            'edit bills',
            'delete bills',
            'create payments',
            'view services',
            'create services',
            'edit services',
            'delete services',
        ];

        foreach ($billingPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Add billing permissions to existing roles
        $superAdmin = Role::findByName('Super Admin');
        $superAdmin->givePermissionTo($billingPermissions);

        $admin = Role::findByName('Hospital Administrator');
        $admin->givePermissionTo([
            'view bills', 'create bills', 'edit bills', 'create payments',
            'view services', 'create services', 'edit services'
        ]);

        $doctor = Role::findByName('Doctor');
        $doctor->givePermissionTo(['view bills', 'create bills']);

        $nurse = Role::findByName('Nurse');
        $nurse->givePermissionTo(['view bills']);

        $receptionist = Role::findByName('Receptionist');
        $receptionist->givePermissionTo(['view bills', 'create bills', 'create payments']);

        $clerk = Role::findByName('Medical Records Clerk');
        $clerk->givePermissionTo(['view bills']);
    }
}