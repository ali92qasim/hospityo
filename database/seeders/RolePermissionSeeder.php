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

        // ── IPD (Wards & Beds) ────────────────────────────────────────────────
        'view wards',
        'create wards',
        'edit wards',
        'delete wards',
        'view beds',
        'create beds',
        'edit beds',
        'delete beds',

        // ── Pharmacy / Inventory ──────────────────────────────────────────────
        'view pharmacy',
        'manage pharmacy',
        'view inventory',
        'manage inventory',

        // ── Laboratory / Diagnostics ──────────────────────────────────────────
        'view investigations',
        'create investigations',
        'edit investigations',
        'delete investigations',
        'view investigation orders',
        'create investigation orders',
        'edit investigation orders',
        'delete investigation orders',
        'view lab results',
        'create lab results',
        'edit lab results',
        'delete lab results',
        'view lab orders',
        'create lab orders',
        'edit lab orders',
        'delete lab orders',
        'view radiology results',
        'create radiology results',
        'edit radiology results',
        'delete radiology results',

        // ── Accounting ────────────────────────────────────────────────────────
        'view accounting',

        // ── Reports ───────────────────────────────────────────────────────────
        'view reports',

        // ── HR ────────────────────────────────────────────────────────────────
        'view hr',

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
            'view bills', 'create bills', 'edit bills', 'create payments',
            'view services', 'create services', 'edit services',
            'view wards', 'create wards', 'edit wards',
            'view beds', 'create beds', 'edit beds',
            'view pharmacy', 'manage pharmacy',
            'view inventory', 'manage inventory',
            'view investigations', 'create investigations', 'edit investigations',
            'view investigation orders', 'create investigation orders', 'edit investigation orders',
            'view lab results', 'create lab results', 'edit lab results',
            'view lab orders', 'create lab orders', 'edit lab orders',
            'view radiology results', 'create radiology results', 'edit radiology results',
            'view accounting',
            'view reports',
            'view hr',
            'manage doctor shares',
            'manage user roles',
        ],
        'Doctor' => [
            'view patients', 'edit patients',
            'view visits', 'create visits', 'edit visits',
            'view appointments', 'create appointments', 'edit appointments',
            'view bills', 'create bills',
            'view investigations',
            'view investigation orders', 'create investigation orders',
            'view lab results',
        ],
        'Nurse' => [
            'view patients', 'edit patients',
            'view visits', 'edit visits',
            'view appointments',
            'view bills',
            'view investigations',
            'view investigation orders',
            'view lab results',
            'view wards', 'view beds',
        ],
        'Receptionist' => [
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view visits', 'create visits',
            'view bills', 'create bills', 'create payments',
        ],
        'Lab Technician' => [
            'view patients',
            'view investigations',
            'view investigation orders', 'edit investigation orders',
            'view lab orders', 'edit lab orders',
            'view lab results', 'create lab results', 'edit lab results',
            'view radiology results', 'create radiology results', 'edit radiology results',
        ],
        'Pharmacist' => [
            'view patients',
            'view visits',
            'view bills', 'create bills', 'create payments',
            'view services',
            'view pharmacy', 'manage pharmacy',
            'view inventory', 'manage inventory',
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
