<?php

namespace App\Models;

/**
 * Central registry of all SaaS modules.
 *
 * Maps module slugs to their display names and the route name prefixes
 * they protect. The CheckModule middleware uses this to determine which
 * module a route belongs to.
 */
class ModuleRegistry
{
    /**
     * Module definitions.
     * Key = module slug (stored in plans.modules JSON).
     * routes = route name prefixes that belong to this module.
     */
    protected static array $modules = [
        'patients' => [
            'name'        => 'Patient Management',
            'group'       => 'Clinical',
            'description' => 'Patient records, demographics, and history.',
            'routes'      => ['patients.'],
        ],
        'doctors' => [
            'name'        => 'Doctor Management',
            'group'       => 'Clinical',
            'description' => 'Doctor profiles, schedules, and assignments.',
            'routes'      => ['doctors.', 'doctor.'],
        ],
        'appointments' => [
            'name'        => 'Appointments',
            'group'       => 'Clinical',
            'description' => 'Appointment booking and calendar management.',
            'routes'      => ['appointments.', 'calendar.'],
        ],
        'visits' => [
            'name'        => 'OPD / Visits',
            'group'       => 'Clinical',
            'description' => 'Outpatient visits and clinical workflows.',
            'routes'      => ['visits.', 'test-orders.'],
        ],
        'departments' => [
            'name'        => 'Departments',
            'group'       => 'Operations',
            'description' => 'Hospital departments and organizational units.',
            'routes'      => ['departments.'],
        ],
        'billing' => [
            'name'        => 'Billing & Invoicing',
            'group'       => 'Finance',
            'description' => 'Patient billing, services, and tax configuration.',
            'routes'      => ['bills.', 'services.', 'taxes.'],
        ],
        'accounting' => [
            'name'        => 'Accounting',
            'group'       => 'Finance',
            'description' => 'Chart of accounts, journals, and financial reports.',
            'routes'      => ['accounting.'],
        ],
        'pharmacy' => [
            'name'        => 'Pharmacy & Inventory',
            'group'       => 'Clinical',
            'description' => 'Medicines, inventory, prescriptions, and POS.',
            'routes'      => [
                'medicines.', 'medicine-categories.', 'medicine-brands.',
                'prescription-instructions.', 'units.',
                'inventory.', 'suppliers.', 'purchases.',
                'prescriptions.', 'pharmacy.pos.',
            ],
        ],
        'laboratory' => [
            'name'        => 'Laboratory',
            'group'       => 'Diagnostics',
            'description' => 'Lab tests, orders, results, and sample workflows.',
            'routes'      => [
                'lab.', 'lab-tests.', 'lab-orders.', 'lab-results.',
                'investigations.', 'investigation-orders.',
            ],
        ],
        'imaging' => [
            'name'        => 'Imaging',
            'group'       => 'Diagnostics',
            'description' => 'Radiology studies, imaging orders, and results.',
            'routes'      => ['imaging.', 'radiology-results.'],
        ],
        'ipd' => [
            'name'        => 'IPD Management',
            'group'       => 'Clinical',
            'description' => 'Inpatient wards, beds, and admissions.',
            'routes'      => ['wards.', 'beds.'],
        ],
        'ot' => [
            'name'        => 'Operation Theatre',
            'group'       => 'Clinical',
            'description' => 'Surgical scheduling and OT workflows.',
            'routes'      => ['ot.'],
        ],
        'doctor-share' => [
            'name'        => 'Doctor Share',
            'group'       => 'Finance',
            'description' => 'Doctor revenue sharing and payouts.',
            'routes'      => ['doctor-share.'],
        ],
        'hr' => [
            'name'        => 'HR & Payroll',
            'group'       => 'Operations',
            'description' => 'Staff, attendance, payroll, and rosters.',
            'routes'      => ['hr.'],
        ],
        'reports' => [
            'name'        => 'Reports & Analytics',
            'group'       => 'Admin',
            'description' => 'Operational and financial reporting dashboards.',
            'routes'      => ['reports.'],
        ],
        'rbac' => [
            'name'        => 'User & Role Management',
            'group'       => 'Admin',
            'description' => 'Users, roles, and permission management.',
            'routes'      => ['users.', 'roles.', 'permissions.'],
        ],
        'audit' => [
            'name'        => 'Audit Logs',
            'group'       => 'Admin',
            'description' => 'System activity and change audit trail.',
            'routes'      => ['audit-logs.'],
        ],
        'backup' => [
            'name'        => 'Backup & Restore',
            'group'       => 'Admin',
            'description' => 'Database backup and restore utilities.',
            'routes'      => ['backup.'],
        ],
    ];

    /**
     * Get all module slugs.
     */
    public static function all(): array
    {
        return array_keys(static::$modules);
    }

    /**
     * Get module definitions with names.
     */
    public static function definitions(): array
    {
        return static::$modules;
    }

    /**
     * Find which module a route name belongs to.
     * Returns null if the route isn't gated by any module.
     */
    public static function moduleForRoute(string $routeName): ?string
    {
        foreach (static::$modules as $slug => $definition) {
            foreach ($definition['routes'] as $prefix) {
                if (str_starts_with($routeName, $prefix)) {
                    return $slug;
                }
            }
        }

        return null;
    }

    /**
     * Get display name for a module slug.
     */
    public static function nameFor(string $slug): string
    {
        return static::$modules[$slug]['name'] ?? $slug;
    }
}
