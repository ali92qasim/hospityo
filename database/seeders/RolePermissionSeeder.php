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
        'edit payments',
        'delete payments',
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
        'view pharmacy', // DEPRECATED: use granular pharmacy permissions below
        'manage pharmacy', // kept for bulk import operations
        'dispense pharmacy',
        'view inventory', // DEPRECATED: use granular inventory permissions below
        'manage inventory', // DEPRECATED: backward compatibility
        'view medicines',
        'create medicines',
        'edit medicines',
        'delete medicines',
        'view medicine categories',
        'create medicine categories',
        'edit medicine categories',
        'delete medicine categories',
        'view brands',
        'create brands',
        'edit brands',
        'delete brands',
        'view units',
        'create units',
        'edit units',
        'delete units',
        'create inventory',
        'edit inventory',
        'delete inventory',
        'view purchases',
        'create purchases',
        'edit purchases',
        'delete purchases',
        'view suppliers',
        'create suppliers',
        'edit suppliers',
        'delete suppliers',
        'view prescriptions',
        'create prescriptions',
        'edit prescriptions',
        'delete prescriptions',
        'view pos',

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
        'view accounting', // DEPRECATED: use granular accounting permissions below
        'view chart of accounts',
        'create chart of accounts',
        'edit chart of accounts',
        'delete chart of accounts',
        'view journal entries',
        'create journal entries',
        'edit journal entries',
        'delete journal entries',
        'view deposits',
        'create deposits',
        'view transfers',
        'create transfers',
        'view general ledger',
        'view patient ledgers',
        'view vendor ledgers',
        'view employee ledgers',
        'view profit and loss',
        'view balance sheet',
        'view fiscal years',
        'create fiscal years',
        'edit fiscal years',
        'close fiscal years',

        // ── Reports ───────────────────────────────────────────────────────────
        'view reports',

        // ── HR ────────────────────────────────────────────────────────────────
        'view hr', // DEPRECATED: use granular HR permissions below
        'view employees',
        'create employees',
        'edit employees',
        'delete employees',
        'view employee documents',
        'create employee documents',
        'delete employee documents',
        'view designations',
        'create designations',
        'edit designations',
        'delete designations',
        'view attendance',
        'create attendance',
        'edit attendance',
        'view leave requests',
        'create leave requests',
        'edit leave requests',
        'delete leave requests',
        'approve leave requests',
        'view leave types',
        'create leave types',
        'edit leave types',
        'delete leave types',
        'view leave balances',
        'view payroll runs',
        'create payroll runs',
        'edit payroll runs',
        'delete payroll runs',
        'approve payroll runs',
        'view payslips',
        'edit payslips',
        'view salary components',
        'create salary components',
        'edit salary components',
        'delete salary components',
        'view employee salary',
        'edit employee salary',
        'view shifts',
        'create shifts',
        'edit shifts',
        'delete shifts',
        'view duty roster',
        'create duty roster',
        'edit duty roster',
        'view shift swaps',
        'approve shift swaps',
        'view department staff',
        'edit department staff',
        'view hr documents',
        'create hr documents',
        'edit hr documents',
        'delete hr documents',
        'verify hr documents',

        // ── Doctor Share ──────────────────────────────────────────────────────
        'manage doctor shares', // DEPRECATED: use granular doctor share permissions below
        'view share rules',
        'create share rules',
        'edit share rules',
        'delete share rules',
        'view share items',
        'create share items',
        'edit share items',
        'delete share items',
        'view settlements',
        'create settlements',
        'edit settlements',
        'approve settlements',
        'view share reports',

        // ── Operation Theatre ─────────────────────────────────────────────────
        'view surgeries',
        'create surgeries',
        'edit surgeries',
        'delete surgeries',

        // ── Audit ─────────────────────────────────────────────────────────────
        'view audit logs',

        // ── Settings ──────────────────────────────────────────────────────────
        'access settings',
        'access settings.hospital-info',
        'access settings.prescription-print',
        'manage settings', // DEPRECATED: use granular settings permissions below
        'view settings',
        'edit settings',

        // ── Backup ────────────────────────────────────────────────────────────
        'manage backup', // DEPRECATED: use granular backup permissions below
        'view backup',
        'create backup',
        'restore backup',
        'delete backup',

        // ── Users ─────────────────────────────────────────────────────────────
        'view users',
        'create users',
        'edit users',
        'delete users',

        // ── Surgical Checklists ───────────────────────────────────────────────
        'manage surgical checklists',

        // ── OT Consumables ────────────────────────────────────────────────────
        'manage ot consumables',

        // ── Sterilization ─────────────────────────────────────────────────────
        'manage sterilization',

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
            'view bills', 'create bills', 'edit bills', 'create payments', 'edit payments', 'delete payments',
            'view services', 'create services', 'edit services',
            'view wards', 'create wards', 'edit wards',
            'view beds', 'create beds', 'edit beds',
            'view pharmacy', 'manage pharmacy', 'dispense pharmacy', // DEPRECATED: backward compatibility
            'view inventory', 'manage inventory', // DEPRECATED: backward compatibility
            'view medicines', 'create medicines', 'edit medicines', 'delete medicines',
            'view medicine categories', 'create medicine categories', 'edit medicine categories', 'delete medicine categories',
            'view brands', 'create brands', 'edit brands', 'delete brands',
            'view units', 'create units', 'edit units', 'delete units',
            'create inventory', 'edit inventory', 'delete inventory',
            'view purchases', 'create purchases', 'edit purchases', 'delete purchases',
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',
            'view prescriptions', 'create prescriptions', 'edit prescriptions', 'delete prescriptions',
            'view pos',
            'view investigations', 'create investigations', 'edit investigations',
            'view investigation orders', 'create investigation orders', 'edit investigation orders',
            'view lab results', 'create lab results', 'edit lab results',
            'view lab orders', 'create lab orders', 'edit lab orders',
            'view radiology results', 'create radiology results', 'edit radiology results',
            'view accounting', // DEPRECATED: backward compatibility
            'view chart of accounts', 'create chart of accounts', 'edit chart of accounts',
            'view journal entries', 'create journal entries', 'edit journal entries',
            'view deposits', 'create deposits',
            'view transfers', 'create transfers',
            'view general ledger',
            'view patient ledgers', 'view vendor ledgers', 'view employee ledgers',
            'view profit and loss', 'view balance sheet',
            'view fiscal years', 'create fiscal years', 'edit fiscal years',
            'view reports',
            'view hr', // DEPRECATED: backward compatibility
            'view employees', 'create employees', 'edit employees',
            'view employee documents', 'create employee documents',
            'view designations', 'create designations', 'edit designations',
            'view attendance', 'create attendance', 'edit attendance',
            'view leave requests', 'create leave requests', 'edit leave requests',
            'view leave types', 'create leave types', 'edit leave types',
            'view leave balances',
            'view payroll runs', 'create payroll runs', 'edit payroll runs',
            'view payslips', 'edit payslips',
            'view salary components', 'create salary components', 'edit salary components',
            'view employee salary', 'edit employee salary',
            'view shifts', 'create shifts', 'edit shifts',
            'view duty roster', 'create duty roster', 'edit duty roster',
            'view shift swaps',
            'view department staff', 'edit department staff',
            'view hr documents', 'create hr documents', 'edit hr documents',
            'view surgeries', 'create surgeries', 'edit surgeries', 'delete surgeries',
            'manage doctor shares', // DEPRECATED: backward compatibility
            'view share rules', 'create share rules', 'edit share rules', 'delete share rules',
            'view share items', 'create share items', 'edit share items', 'delete share items',
            'view settlements', 'create settlements', 'edit settlements', 'approve settlements',
            'view share reports',
            'manage user roles',
            'view users', 'create users', 'edit users', 'delete users',
            'view audit logs',
            'access settings',
            'manage settings', // DEPRECATED: backward compatibility
            'view settings', 'edit settings',
            'manage backup', // DEPRECATED: backward compatibility
            'view backup', 'create backup', 'restore backup', 'delete backup',
            'manage surgical checklists',
            'manage ot consumables',
            'manage sterilization',
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
            'manage surgical checklists',
            'manage ot consumables',
            'manage sterilization',
        ],
        'Receptionist' => [
            'view patients', 'create patients', 'edit patients',
            'view appointments', 'create appointments', 'edit appointments',
            'view visits', 'create visits',
            'view bills', 'create bills', 'create payments', 'edit payments',
            'access settings',
            'manage settings',
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
            'view pharmacy', 'manage pharmacy', // DEPRECATED: backward compatibility
            'manage inventory', // DEPRECATED: backward compatibility
            'view medicines', 'create medicines', 'edit medicines', 'delete medicines',
            'view medicine categories', 'create medicine categories', 'edit medicine categories', 'delete medicine categories',
            'view brands', 'create brands', 'edit brands', 'delete brands',
            'view units', 'create units', 'edit units', 'delete units',
            'view inventory', 'create inventory', 'edit inventory', 'delete inventory',
            'view purchases', 'create purchases', 'edit purchases', 'delete purchases',
            'view suppliers', 'create suppliers', 'edit suppliers', 'delete suppliers',
            'view prescriptions', 'create prescriptions', 'edit prescriptions', 'delete prescriptions',
            'view pos', 'dispense pharmacy',
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

    /**
     * @return list<string>
     */
    public static function catalogPermissions(): array
    {
        return self::PERMISSIONS;
    }

    /**
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        return self::ROLE_PERMISSIONS;
    }
}
