<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

/**
 * Single source of truth for all tenant permissions and role assignments.
 *
 * Rules:
 *  - Add new permissions here ONLY — never hardcode them elsewhere.
 *  - Uses syncPermissions() so re-running this seeder on an existing tenant
 *    is safe and idempotent: new permissions are added, removed ones are dropped.
 *  - Super Admin always gets every permission via syncPermissions(Permission::all()).
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * All permissions that exist in the system, grouped by domain.
     * Add new permissions here when a new feature/module is introduced.
     */
    private const PERMISSIONS = [
        // ── Patient Management ────────────────────────────────────────────────
        'view patients',
        'create patients',
        'edit patients',
        'delete patients',

        // ── Doctor Management ─────────────────────────────────────────────────
        'view doctors',
        'create doctors',
        'edit doctors',
        'delete doctors',

        // ── Department Management ─────────────────────────────────────────────
        'view departments',
        'create departments',
        'edit departments',
        'delete departments',

        // ── Visit Management ──────────────────────────────────────────────────
        'view visits',
        'create visits',
        'edit visits',
        'delete visits',

        // ── Appointment Management ────────────────────────────────────────────
        'view appointments',
        'create appointments',
        'edit appointments',
        'delete appointments',

        // ── Medical Records ───────────────────────────────────────────────────
        'view medical records',
        'create medical records',
        'edit medical records',
        'delete medical records',
        'sign medical records',

        // ── Billing ───────────────────────────────────────────────────────────
        'view bills',
        'create bills',
        'edit bills',
        'delete bills',
        'create payments',
        'view services',
        'create services',
        'edit services',
        'delete services',

        // ── Doctor Share ──────────────────────────────────────────────────────
        'manage doctor shares',

        // ── RBAC ──────────────────────────────────────────────────────────────
        'view roles',
        'create roles',
        'edit roles',
        'delete roles',
        'view permissions',
        'create permissions',
        'edit permissions',
        'delete permissions',
        'manage user roles',

        // ── Pharmacy ──────────────────────────────────────────────────────────
        'view inventory',
        'manage inventory',
    ];

    /**
     * Default permissions per role (excluding Super Admin which gets everything).
     * Use syncPermissions so this is always idempotent.
     */
    private const ROLE_PERMISSIONS = [
        'Hospital Administrator' => [
            'view patients', 'create patients', 'edit patients',
            'view doctors', 'create doctors', 'edit doctors',
            'view departments', 'create departments', 'edit departments',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills', 'create bills', 'edit bills', 'create payments',
            'view services', 'create services', 'edit services',
            'manage doctor shares',
            'manage user roles',
        ],
        'Doctor' => [
            'view patients', 'edit patients',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view medical records', 'create medical records', 'edit medical records', 'sign medical records',
            'view bills', 'create bills',
        ],
        'Nurse' => [
            'view patients', 'edit patients',
            'view visits', 'edit visits',
            'view appointments',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills',
        ],
        'Receptionist' => [
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view visits', 'create visits',
            'view bills', 'create bills', 'create payments',
        ],
        'Medical Records Clerk' => [
            'view patients',
            'view medical records', 'create medical records', 'edit medical records',
            'view bills',
        ],

        // ── Pharmacist ────────────────────────────────────────────────────────
        'Pharmacist' => [
            'view patients',
            'view visits',
            'view bills',
            'create bills',
            'create payments',
            'view services',
            'view inventory',
            'manage inventory',
        ],
    ];

    public function run(): void
    {
        // 1. Ensure every permission exists (idempotent)
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }

        // 2. Super Admin — always gets every permission
        $superAdmin = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // 3. All other roles — sync so additions/removals stay current
        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($permissions);
        }
    }
}
