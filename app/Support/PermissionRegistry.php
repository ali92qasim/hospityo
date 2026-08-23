<?php

namespace App\Support;

/**
 * Central registry of tenant permissions grouped by module for role UI.
 *
 * Mirrors ModuleRegistry structure. Existing permissions from RolePermissionSeeder
 * are grouped here; Accounting and Doctor Share also include upcoming granular
 * permissions from the design spec (not yet in the seeder).
 */
class PermissionRegistry
{
    /**
     * Module slug => label + submodule groups of permission strings.
     *
     * @var array<string, array{label: string, groups: array<string, list<string>>}>
     */
    protected static array $modules = [
        'patients' => [
            'label' => 'Patient Management',
            'groups' => [
                'patients' => [
                    'view patients',
                    'create patients',
                    'edit patients',
                    'delete patients',
                ],
            ],
        ],
        'doctors' => [
            'label' => 'Doctor Management',
            'groups' => [
                'doctors' => [
                    'view doctors',
                    'create doctors',
                    'edit doctors',
                    'delete doctors',
                ],
            ],
        ],
        'departments' => [
            'label' => 'Departments',
            'groups' => [
                'departments' => [
                    'view departments',
                    'create departments',
                    'edit departments',
                    'delete departments',
                ],
            ],
        ],
        'visits' => [
            'label' => 'OPD / Visits',
            'groups' => [
                'visits' => [
                    'view visits',
                    'create visits',
                    'edit visits',
                    'delete visits',
                ],
            ],
        ],
        'appointments' => [
            'label' => 'Appointments',
            'groups' => [
                'appointments' => [
                    'view appointments',
                    'create appointments',
                    'edit appointments',
                    'delete appointments',
                ],
            ],
        ],
        'billing' => [
            'label' => 'Billing & Invoicing',
            'groups' => [
                'bills' => [
                    'view bills',
                    'create bills',
                    'edit bills',
                    'delete bills',
                ],
                'payments' => [
                    'create payments',
                    'edit payments',
                    'delete payments',
                ],
                'services' => [
                    'view services',
                    'create services',
                    'edit services',
                    'delete services',
                ],
            ],
        ],
        'ipd' => [
            'label' => 'IPD Management',
            'groups' => [
                'wards' => [
                    'view wards',
                    'create wards',
                    'edit wards',
                    'delete wards',
                ],
                'beds' => [
                    'view beds',
                    'create beds',
                    'edit beds',
                    'delete beds',
                ],
            ],
        ],
        'pharmacy' => [
            'label' => 'Pharmacy & Inventory',
            'groups' => [
                'general' => [
                    'view pharmacy', // DEPRECATED: use granular pharmacy permissions
                    'manage pharmacy', // bulk import operations
                ],
                'medicines' => [
                    'view medicines',
                    'create medicines',
                    'edit medicines',
                    'delete medicines',
                ],
                'medicine categories' => [
                    'view medicine categories',
                    'create medicine categories',
                    'edit medicine categories',
                    'delete medicine categories',
                ],
                'brands' => [
                    'view brands',
                    'create brands',
                    'edit brands',
                    'delete brands',
                ],
                'units' => [
                    'view units',
                    'create units',
                    'edit units',
                    'delete units',
                ],
                'inventory' => [
                    'view inventory', // DEPRECATED: backward compatibility
                    'manage inventory', // DEPRECATED: backward compatibility
                    'create inventory',
                    'edit inventory',
                    'delete inventory',
                ],
                'purchases' => [
                    'view purchases',
                    'create purchases',
                    'edit purchases',
                    'delete purchases',
                ],
                'suppliers' => [
                    'view suppliers',
                    'create suppliers',
                    'edit suppliers',
                    'delete suppliers',
                ],
                'prescriptions' => [
                    'view prescriptions',
                    'create prescriptions',
                    'edit prescriptions',
                    'delete prescriptions',
                ],
                'POS' => [
                    'view pos',
                    'dispense pharmacy',
                ],
            ],
        ],
        'laboratory' => [
            'label' => 'Laboratory',
            'groups' => [
                'investigations' => [
                    'view investigations',
                    'create investigations',
                    'edit investigations',
                    'delete investigations',
                ],
                'investigation orders' => [
                    'view investigation orders',
                    'create investigation orders',
                    'edit investigation orders',
                    'delete investigation orders',
                ],
                'lab orders' => [
                    'view lab orders',
                    'create lab orders',
                    'edit lab orders',
                    'delete lab orders',
                ],
                'lab results' => [
                    'view lab results',
                    'create lab results',
                    'edit lab results',
                    'delete lab results',
                ],
            ],
        ],
        'imaging' => [
            'label' => 'Imaging / Radiology',
            'groups' => [
                'radiology results' => [
                    'view radiology results',
                    'create radiology results',
                    'edit radiology results',
                    'delete radiology results',
                ],
            ],
        ],
        'accounting' => [
            'label' => 'Accounting',
            'groups' => [
                'general' => [
                    'view accounting',
                ],
                'chart of accounts' => [
                    'view chart of accounts',
                    'create chart of accounts',
                    'edit chart of accounts',
                    'delete chart of accounts',
                ],
                'journal entries' => [
                    'view journal entries',
                    'create journal entries',
                    'edit journal entries',
                    'delete journal entries',
                ],
                'deposits' => [
                    'view deposits',
                    'create deposits',
                ],
                'transfers' => [
                    'view transfers',
                    'create transfers',
                ],
                'general ledger' => [
                    'view general ledger',
                ],
                'patient ledgers' => [
                    'view patient ledgers',
                ],
                'vendor ledgers' => [
                    'view vendor ledgers',
                ],
                'employee ledgers' => [
                    'view employee ledgers',
                ],
                'profit and loss' => [
                    'view profit and loss',
                ],
                'balance sheet' => [
                    'view balance sheet',
                ],
                'fiscal years' => [
                    'view fiscal years',
                    'create fiscal years',
                    'edit fiscal years',
                    'close fiscal years',
                ],
            ],
        ],
        'reports' => [
            'label' => 'Reports & Analytics',
            'groups' => [
                'reports' => [
                    'view reports',
                ],
            ],
        ],
        'hr' => [
            'label' => 'HR & Payroll',
            'groups' => [
                'general' => [
                    'view hr', // DEPRECATED: use granular HR submodule permissions
                ],
                'employees' => [
                    'view employees',
                    'create employees',
                    'edit employees',
                    'delete employees',
                ],
                'employee documents' => [
                    'view employee documents',
                    'create employee documents',
                    'delete employee documents',
                ],
                'designations' => [
                    'view designations',
                    'create designations',
                    'edit designations',
                    'delete designations',
                ],
                'attendance' => [
                    'view attendance',
                    'create attendance',
                    'edit attendance',
                ],
                'leave requests' => [
                    'view leave requests',
                    'create leave requests',
                    'edit leave requests',
                    'delete leave requests',
                    'approve leave requests',
                ],
                'leave types' => [
                    'view leave types',
                    'create leave types',
                    'edit leave types',
                    'delete leave types',
                ],
                'leave balances' => [
                    'view leave balances',
                ],
                'payroll runs' => [
                    'view payroll runs',
                    'create payroll runs',
                    'edit payroll runs',
                    'delete payroll runs',
                    'approve payroll runs',
                ],
                'payslips' => [
                    'view payslips',
                    'edit payslips',
                ],
                'salary components' => [
                    'view salary components',
                    'create salary components',
                    'edit salary components',
                    'delete salary components',
                ],
                'employee salary' => [
                    'view employee salary',
                    'edit employee salary',
                ],
                'shifts' => [
                    'view shifts',
                    'create shifts',
                    'edit shifts',
                    'delete shifts',
                ],
                'duty roster' => [
                    'view duty roster',
                    'create duty roster',
                    'edit duty roster',
                ],
                'shift swaps' => [
                    'view shift swaps',
                    'approve shift swaps',
                ],
                'department staff' => [
                    'view department staff',
                    'edit department staff',
                ],
                'hr documents' => [
                    'view hr documents',
                    'create hr documents',
                    'edit hr documents',
                    'delete hr documents',
                    'verify hr documents',
                ],
            ],
        ],
        'doctor-share' => [
            'label' => 'Doctor Share',
            'groups' => [
                'general' => [
                    'manage doctor shares',
                ],
                'share rules' => [
                    'view share rules',
                    'create share rules',
                    'edit share rules',
                    'delete share rules',
                ],
                'share items' => [
                    'view share items',
                    'create share items',
                    'edit share items',
                    'delete share items',
                ],
                'settlements' => [
                    'view settlements',
                    'create settlements',
                    'edit settlements',
                    'approve settlements',
                ],
                'share reports' => [
                    'view share reports',
                ],
            ],
        ],
        'ot' => [
            'label' => 'Operation Theatre',
            'groups' => [
                'surgeries' => [
                    'view surgeries',
                    'create surgeries',
                    'edit surgeries',
                    'delete surgeries',
                ],
                'surgical checklists' => [
                    'manage surgical checklists',
                ],
                'ot consumables' => [
                    'manage ot consumables',
                ],
                'sterilization' => [
                    'manage sterilization',
                ],
            ],
        ],
        'audit' => [
            'label' => 'Audit Logs',
            'groups' => [
                'audit logs' => [
                    'view audit logs',
                ],
            ],
        ],
        'settings' => [
            'label' => 'Settings',
            'groups' => [
                'settings' => [
                    'manage settings',
                    'view settings',
                    'edit settings',
                ],
            ],
        ],
        'backup' => [
            'label' => 'Backup & Restore',
            'groups' => [
                'backup' => [
                    'manage backup',
                    'view backup',
                    'create backup',
                    'restore backup',
                    'delete backup',
                ],
            ],
        ],
        'rbac' => [
            'label' => 'User & Role Management',
            'groups' => [
                'users' => [
                    'view users',
                    'create users',
                    'edit users',
                    'delete users',
                ],
                'roles' => [
                    'view roles',
                    'create roles',
                    'edit roles',
                    'delete roles',
                ],
                'permissions' => [
                    'view permissions',
                    'create permissions',
                    'edit permissions',
                    'delete permissions',
                ],
                'user roles' => [
                    'manage user roles',
                ],
            ],
        ],
    ];

    /**
     * Permissions grouped by module and submodule for role UI.
     *
     * @return array<string, array{label: string, groups: array<string, list<string>>}>
     */
    public static function grouped(): array
    {
        return static::$modules;
    }

    /**
     * Flat unique list of all registered permission strings.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $permissions = [];

        foreach (static::$modules as $module) {
            foreach ($module['groups'] as $groupPermissions) {
                foreach ($groupPermissions as $permission) {
                    $permissions[] = $permission;
                }
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Alias for all().
     *
     * @return list<string>
     */
    public static function flat(): array
    {
        return static::all();
    }
}
