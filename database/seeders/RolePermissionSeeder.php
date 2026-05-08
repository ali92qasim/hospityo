<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Permissions
        $permissions = [
            // Patient Management
            'view patients',
            'create patients',
            'edit patients',
            'delete patients',
            
            // Doctor Management
            'view doctors',
            'create doctors',
            'edit doctors',
            'delete doctors',
            
            // Department Management
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            
            // Visit Management
            'view visits',
            'create visits',
            'edit visits',
            'delete visits',
            
            // Appointment Management
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            
            // Medical Records
            'view medical records',
            'create medical records',
            'edit medical records',
            'delete medical records',
            'sign medical records',
            
            // Billing Management
            'view bills',
            'create bills',
            'edit bills',
            'delete bills',
            'create payments',
            'view services',
            'create services',
            'edit services',
            'delete services',
            'manage doctor shares',
            
            // RBAC Management
            'view roles',
            'create roles',
            'edit roles',
            'delete roles',
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions',
            'manage user roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and assign permissions
        
        // Super Admin - Full access
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Hospital Administrator
        $admin = Role::firstOrCreate(['name' => 'Hospital Administrator']);
        $admin->givePermissionTo([
            'view patients', 'create patients', 'edit patients',
            'view doctors', 'create doctors', 'edit doctors',
            'view departments', 'create departments', 'edit departments',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills', 'create bills', 'edit bills', 'create payments',
            'view services', 'create services', 'edit services',
            'manage doctor shares',
            'manage user roles'
        ]);

        // Doctor
        $doctor = Role::firstOrCreate(['name' => 'Doctor']);
        $doctor->givePermissionTo([
            'view patients', 'edit patients',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records', 'sign medical records',
            'view bills', 'create bills'
        ]);

        // Nurse
        $nurse = Role::firstOrCreate(['name' => 'Nurse']);
        $nurse->givePermissionTo([
            'view patients', 'edit patients',
            'view visits', 'edit visits',
            'view appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills'
        ]);

        // Receptionist
        $receptionist = Role::firstOrCreate(['name' => 'Receptionist']);
        $receptionist->givePermissionTo([
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view visits', 'create visits',
            'view bills', 'create bills', 'create payments'
        ]);

        // Medical Records Clerk
        $clerk = Role::firstOrCreate(['name' => 'Medical Records Clerk']);
        $clerk->givePermissionTo([
            'view patients',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills'
        ]);
    }
}