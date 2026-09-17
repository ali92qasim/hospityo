<?php

namespace App\Models;

use Illuminate\Http\Request;

/**
 * Central registry of all SaaS modules.
 *
 * Maps module slugs to their display names and the route name prefixes
 * they protect. The CheckModule middleware uses this to determine which
 * module a route belongs to.
 */
class ModuleRegistry
{
    public const REPORT_CHILD_SLUGS = [
        'reports.daily-cash-register',
        'reports.patient-visits',
        'reports.revenue',
        'reports.outstanding-bills',
        'reports.investigations',
        'reports.medicine-sales',
        'reports.inventory-status',
        'reports.expiry-report',
        'reports.doctor-performance',
        'reports.appointment-statistics',
        'reports.ipd-report',
        'reports.department-performance',
        'reports.patient-demographics',
    ];

    public const ACCOUNTING_CHILD_SLUGS = [
        'accounting.profit-loss',
        'accounting.balance-sheet',
        'accounting.general-ledger',
        'accounting.patient-ledger',
        'accounting.vendor-ledger',
        'accounting.employee-ledger',
    ];

    public const PHARMACY_CHILD_SLUGS = [
        'pharmacy.pos',
        'pharmacy.inventory',
        'pharmacy.catalog',
    ];

    public const HR_CHILD_SLUGS = [
        'hr.employees',
        'hr.attendance-leave',
        'hr.payroll',
        'hr.scheduling',
    ];

    public const OT_CHILD_SLUGS = [
        'ot.pac',
        'ot.checklist',
        'ot.consumables',
        'ot.sterilization',
    ];

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
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['patients.'],
        ],
        'doctors' => [
            'name'        => 'Doctor Management',
            'group'       => 'Clinical',
            'description' => 'Doctor profiles, schedules, and assignments.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['doctors.', 'doctor.'],
        ],
        'appointments' => [
            'name'        => 'Appointments',
            'group'       => 'Clinical',
            'description' => 'Appointment booking and calendar management.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['appointments.', 'calendar.'],
        ],
        'visits' => [
            'name'        => 'OPD / Visits',
            'group'       => 'Clinical',
            'description' => 'Outpatient visits and clinical workflows.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['visits.', 'test-orders.'],
        ],
        'emergency' => [
            'name'        => 'Emergency',
            'group'       => 'Clinical',
            'description' => 'Emergency visits and triage workflows.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => [],
        ],
        'departments' => [
            'name'        => 'Departments',
            'group'       => 'Operations',
            'description' => 'Hospital departments and organizational units.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['departments.'],
        ],
        'billing' => [
            'name'        => 'Billing & Invoicing',
            'group'       => 'Finance',
            'description' => 'Patient billing, services, and tax configuration.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['bills.', 'services.', 'taxes.'],
        ],
        'accounting' => [
            'name'        => 'Accounting',
            'group'       => 'Finance',
            'description' => 'Chart of accounts, journals, and financial reports.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['accounting.'],
            'children'    => [
                'accounting.profit-loss',
                'accounting.balance-sheet',
                'accounting.general-ledger',
                'accounting.patient-ledger',
                'accounting.vendor-ledger',
                'accounting.employee-ledger',
            ],
        ],
        'accounting.profit-loss' => [
            'name'        => 'Profit & Loss',
            'group'       => 'Finance',
            'parent'      => 'accounting',
            'entitlement' => 'plan',
            'description' => 'Profit and loss statement.',
            'routes'      => ['accounting.profit-loss'],
        ],
        'accounting.balance-sheet' => [
            'name'        => 'Balance Sheet',
            'group'       => 'Finance',
            'parent'      => 'accounting',
            'entitlement' => 'plan',
            'description' => 'Balance sheet statement.',
            'routes'      => ['accounting.balance-sheet'],
        ],
        'accounting.general-ledger' => [
            'name'        => 'General Ledger',
            'group'       => 'Finance',
            'parent'      => 'accounting',
            'entitlement' => 'plan',
            'description' => 'General ledger.',
            'routes'      => ['accounting.general-ledger'],
        ],
        'accounting.patient-ledger' => [
            'name'        => 'Patient Ledger',
            'group'       => 'Finance',
            'parent'      => 'accounting',
            'entitlement' => 'plan',
            'description' => 'Patient ledger.',
            'routes'      => ['accounting.patient-ledger'],
        ],
        'accounting.vendor-ledger' => [
            'name'        => 'Vendor Ledger',
            'group'       => 'Finance',
            'parent'      => 'accounting',
            'entitlement' => 'plan',
            'description' => 'Vendor ledger.',
            'routes'      => ['accounting.vendor-ledger'],
        ],
        'accounting.employee-ledger' => [
            'name'        => 'Employee Ledger',
            'group'       => 'Finance',
            'parent'      => 'accounting',
            'entitlement' => 'plan',
            'description' => 'Employee ledger.',
            'routes'      => ['accounting.employee-ledger'],
        ],
        'pharmacy' => [
            'name'        => 'Pharmacy & Inventory',
            'group'       => 'Clinical',
            'description' => 'Medicines, inventory, prescriptions, and POS.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['prescriptions.'],
            'children'    => [
                'pharmacy.pos',
                'pharmacy.inventory',
                'pharmacy.catalog',
            ],
        ],
        'pharmacy.pos' => [
            'name'        => 'POS',
            'group'       => 'Clinical',
            'parent'      => 'pharmacy',
            'entitlement' => 'plan',
            'description' => 'Pharmacy point of sale.',
            'routes'      => ['pharmacy.pos.'],
        ],
        'pharmacy.inventory' => [
            'name'        => 'Inventory',
            'group'       => 'Clinical',
            'parent'      => 'pharmacy',
            'entitlement' => 'plan',
            'description' => 'Pharmacy inventory, suppliers, and purchases.',
            'routes'      => ['inventory.', 'suppliers.', 'purchases.'],
        ],
        'pharmacy.catalog' => [
            'name'        => 'Catalog',
            'group'       => 'Clinical',
            'parent'      => 'pharmacy',
            'entitlement' => 'plan',
            'description' => 'Medicines, categories, brands, units, and prescription instructions.',
            'routes'      => [
                'medicines.', 'medicine-categories.', 'medicine-brands.',
                'units.', 'prescription-instructions.',
            ],
        ],
        'laboratory' => [
            'name'        => 'Laboratory',
            'group'       => 'Diagnostics',
            'description' => 'Lab tests, orders, results, and sample workflows.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => [
                'lab.', 'lab-tests.', 'lab-orders.', 'lab-results.',
                'investigations.', 'investigation-orders.',
            ],
        ],
        'imaging' => [
            'name'        => 'Imaging',
            'group'       => 'Diagnostics',
            'description' => 'Radiology studies, imaging orders, and results.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['imaging.', 'radiology-results.'],
        ],
        'ipd' => [
            'name'        => 'IPD Management',
            'group'       => 'Clinical',
            'description' => 'Inpatient wards, beds, and admissions.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['wards.', 'beds.'],
        ],
        'ot' => [
            'name'        => 'Operation Theatre',
            'group'       => 'Clinical',
            'description' => 'Surgical scheduling and OT workflows.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['ot.'],
            'children'    => [
                'ot.pac',
                'ot.checklist',
                'ot.consumables',
                'ot.sterilization',
            ],
        ],
        'ot.pac' => [
            'name'        => 'Pre-Anesthesia Checkup',
            'group'       => 'Clinical',
            'parent'      => 'ot',
            'entitlement' => 'plan',
            'description' => 'Pre-anesthesia assessment and PAC workflows.',
            'routes'      => ['ot.pac.'],
        ],
        'ot.checklist' => [
            'name'        => 'OT Checklists',
            'group'       => 'Clinical',
            'parent'      => 'ot',
            'entitlement' => 'plan',
            'description' => 'Surgical safety and OT checklists.',
            'routes'      => ['ot.checklist.'],
        ],
        'ot.consumables' => [
            'name'        => 'OT Consumables',
            'group'       => 'Clinical',
            'parent'      => 'ot',
            'entitlement' => 'plan',
            'description' => 'Consumables tracking and usage in the OT.',
            'routes'      => ['ot.consumables.'],
        ],
        'ot.sterilization' => [
            'name'        => 'Sterilization',
            'group'       => 'Clinical',
            'parent'      => 'ot',
            'entitlement' => 'plan',
            'description' => 'Instrument sterilization and autoclave workflows.',
            'routes'      => ['ot.sterilization.'],
        ],
        'hr' => [
            'name'        => 'HR & Payroll',
            'group'       => 'Operations',
            'description' => 'Staff, attendance, payroll, and rosters.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['hr.'],
            'children'    => [
                'hr.employees',
                'hr.attendance-leave',
                'hr.payroll',
                'hr.scheduling',
            ],
        ],
        'hr.employees' => [
            'name'        => 'Employees',
            'group'       => 'Operations',
            'parent'      => 'hr',
            'entitlement' => 'plan',
            'description' => 'Employees, designations, department staff, and HR documents.',
            'routes'      => ['hr.employees.', 'hr.designations.', 'hr.department-staff.', 'hr.documents.'],
        ],
        'hr.attendance-leave' => [
            'name'        => 'Attendance & Leave',
            'group'       => 'Operations',
            'parent'      => 'hr',
            'entitlement' => 'plan',
            'description' => 'Attendance, leave requests, leave balances, and leave types.',
            'routes'      => ['hr.attendance.', 'hr.leave.', 'hr.leave-types.'],
        ],
        'hr.payroll' => [
            'name'        => 'Payroll',
            'group'       => 'Operations',
            'parent'      => 'hr',
            'entitlement' => 'plan',
            'description' => 'Payroll runs, payslips, salary components, and employee salary.',
            'routes'      => ['hr.payroll.'],
        ],
        'hr.scheduling' => [
            'name'        => 'Scheduling',
            'group'       => 'Operations',
            'parent'      => 'hr',
            'entitlement' => 'plan',
            'description' => 'Shifts, duty roster, and shift swaps.',
            'routes'      => ['hr.shifts.'],
        ],
        'reports' => [
            'name'        => 'Reports & Analytics',
            'group'       => 'Admin',
            'description' => 'Operational and financial reporting dashboards.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['reports.'],
            'children'    => [
                'reports.daily-cash-register',
                'reports.patient-visits',
                'reports.revenue',
                'reports.outstanding-bills',
                'reports.investigations',
                'reports.medicine-sales',
                'reports.inventory-status',
                'reports.expiry-report',
                'reports.doctor-performance',
                'reports.appointment-statistics',
                'reports.ipd-report',
                'reports.department-performance',
                'reports.patient-demographics',
            ],
        ],
        'reports.daily-cash-register' => [
            'name'        => 'Daily Cash Register',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Daily cash register report.',
            'routes'      => ['reports.daily-cash-register'],
        ],
        'reports.patient-visits' => [
            'name'        => 'Patient Visits',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Patient visit report.',
            'routes'      => ['reports.patient-visits'],
        ],
        'reports.revenue' => [
            'name'        => 'Revenue Report',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Revenue report.',
            'routes'      => ['reports.revenue'],
        ],
        'reports.outstanding-bills' => [
            'name'        => 'Outstanding Bills',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Outstanding bills report.',
            'routes'      => ['reports.outstanding-bills'],
        ],
        'reports.investigations' => [
            'name'        => 'Investigation Report',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Lab and imaging investigation report.',
            'routes'      => ['reports.investigations', 'reports.lab-tests'],
        ],
        'reports.medicine-sales' => [
            'name'        => 'Medicine Sales',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Medicine sales report.',
            'routes'      => ['reports.medicine-sales'],
        ],
        'reports.inventory-status' => [
            'name'        => 'Inventory Status',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Inventory status report.',
            'routes'      => ['reports.inventory-status'],
        ],
        'reports.expiry-report' => [
            'name'        => 'Expiry Report',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Medicine expiry report.',
            'routes'      => ['reports.expiry-report'],
        ],
        'reports.doctor-performance' => [
            'name'        => 'Doctor Performance',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Doctor performance report.',
            'routes'      => ['reports.doctor-performance'],
        ],
        'reports.appointment-statistics' => [
            'name'        => 'Appointment Statistics',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Appointment statistics report.',
            'routes'      => ['reports.appointment-statistics'],
        ],
        'reports.ipd-report' => [
            'name'        => 'IPD Report',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'IPD report.',
            'routes'      => ['reports.ipd-report'],
        ],
        'reports.department-performance' => [
            'name'        => 'Department Performance',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Department performance report.',
            'routes'      => ['reports.department-performance'],
        ],
        'reports.patient-demographics' => [
            'name'        => 'Patient Demographics',
            'group'       => 'Admin',
            'parent'      => 'reports',
            'entitlement' => 'plan',
            'description' => 'Patient demographics report.',
            'routes'      => ['reports.patient-demographics'],
        ],
        'rbac' => [
            'name'        => 'User & Role Management',
            'group'       => 'Admin',
            'description' => 'Users, roles, and permission management.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['users.', 'roles.', 'permissions.'],
        ],
        'audit' => [
            'name'        => 'Audit Logs',
            'group'       => 'Admin',
            'description' => 'System activity and change audit trail.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['audit-logs.'],
        ],
        'backup' => [
            'name'        => 'Backup & Restore',
            'group'       => 'Admin',
            'description' => 'Database backup and restore utilities.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['backup.'],
        ],
        'settings' => [
            'name'        => 'Settings',
            'group'       => 'Admin',
            'description' => 'Hospital configuration and print templates.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['settings.index'],
            'children'    => [
                'settings.hospital-info',
                'settings.prescription-print',
                'settings.doctor-share',
            ],
        ],
        'settings.hospital-info' => [
            'name'        => 'Hospital Info',
            'group'       => 'Admin',
            'parent'      => 'settings',
            'description' => 'Hospital name, contact, timezone, and branding.',
            'entitlement' => 'plan',
            'bundled'     => true,
            'routes'      => ['settings.hospital-info', 'settings.update'],
        ],
        'settings.prescription-print' => [
            'name'        => 'Prescription Print Templates',
            'group'       => 'Admin',
            'parent'      => 'settings',
            'description' => 'Prescription print layout templates.',
            'entitlement' => 'plan',
            'routes'      => ['settings.prescription-print-templates.'],
        ],
        'settings.doctor-share' => [
            'name'        => 'Doctor Share',
            'group'       => 'Admin',
            'parent'      => 'settings',
            'description' => 'Doctor revenue sharing and payouts.',
            'entitlement' => 'plan',
            'routes'      => ['doctor-share.'],
        ],
    ];

    public static function reportChildSlugs(): array
    {
        return self::REPORT_CHILD_SLUGS;
    }

    public static function backfillReportChildren(array $modules): array
    {
        if (! in_array('reports', $modules, true)) {
            return array_values($modules);
        }

        return array_values(array_unique([...$modules, ...self::REPORT_CHILD_SLUGS]));
    }

    public static function accountingChildSlugs(): array
    {
        return self::ACCOUNTING_CHILD_SLUGS;
    }

    public static function pharmacyChildSlugs(): array
    {
        return self::PHARMACY_CHILD_SLUGS;
    }

    public static function backfillAccountingChildren(array $modules): array
    {
        if (! in_array('accounting', $modules, true)) {
            return array_values($modules);
        }

        return array_values(array_unique([...$modules, ...self::ACCOUNTING_CHILD_SLUGS]));
    }

    public static function backfillPharmacyChildren(array $modules): array
    {
        if (! in_array('pharmacy', $modules, true)) {
            return array_values($modules);
        }

        return array_values(array_unique([...$modules, ...self::PHARMACY_CHILD_SLUGS]));
    }

    public static function hrChildSlugs(): array
    {
        return self::HR_CHILD_SLUGS;
    }

    public static function backfillHrChildren(array $modules): array
    {
        if (! in_array('hr', $modules, true)) {
            return array_values($modules);
        }

        return array_values(array_unique([...$modules, ...self::HR_CHILD_SLUGS]));
    }

    public static function backfillSettingsDoctorShare(array $modules): array
    {
        if (! in_array('doctor-share', $modules, true)) {
            return $modules;
        }

        $modules = array_filter(
            $modules,
            fn (string $module) => $module !== 'doctor-share'
        );

        return array_values(array_unique([...$modules, 'settings.doctor-share']));
    }

    public static function otChildSlugs(): array
    {
        return self::OT_CHILD_SLUGS;
    }

    public static function backfillOtChildren(array $modules): array
    {
        if (! in_array('ot', $modules, true)) {
            return array_values($modules);
        }

        return array_values(array_unique([...$modules, ...self::OT_CHILD_SLUGS]));
    }

    /**
     * Get all module slugs, including nested children.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(static::$modules);
    }

    /**
     * Top-level module slugs (excludes nested children).
     *
     * @return list<string>
     */
    public static function topLevel(): array
    {
        return array_keys(array_filter(
            static::$modules,
            fn (array $definition) => empty($definition['parent'])
        ));
    }

    /**
     * Get module definitions with names.
     */
    public static function definitions(): array
    {
        return static::$modules;
    }

    /**
     * Parent slug for a nested module, if any.
     */
    public static function parentOf(string $slug): ?string
    {
        return static::$modules[$slug]['parent'] ?? null;
    }

    public static function planAllows(?Tenant $tenant, string $slug): bool
    {
        $definition = static::$modules[$slug] ?? null;
        if ($definition === null) {
            return false;
        }
        if (($definition['entitlement'] ?? 'plan') === 'always') {
            return true;
        }
        if ($tenant === null) {
            return true;
        }

        return $tenant->hasModule($slug);
    }

    public static function allows(?Tenant $tenant, ?\Illuminate\Contracts\Auth\Access\Authorizable $user, string $slug): bool
    {
        if (! static::planAllows($tenant, $slug)) {
            return false;
        }

        $parent = static::parentOf($slug);
        if ($parent !== null && ! static::planAllows($tenant, $parent)) {
            return false;
        }

        if ($user === null) {
            return true;
        }

        $names = \App\Support\PermissionRegistry::forModule($slug);
        if ($names === []) {
            return true;
        }

        foreach ($names as $name) {
            if ($user->can($name)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ensure selected child slugs also include their parent.
     *
     * @param  list<string>  $slugs
     * @return list<string>
     */
    public static function normalize(array $slugs): array
    {
        $normalized = $slugs;

        foreach ($slugs as $slug) {
            $parent = static::parentOf($slug);
            if ($parent && ! in_array($parent, $normalized, true)) {
                $normalized[] = $parent;
            }
        }

        if (in_array('settings', $normalized, true) && ! in_array('settings.hospital-info', $normalized, true)) {
            $normalized[] = 'settings.hospital-info';
        }

        return array_values(array_unique($normalized));
    }

    /**
     * Resolve the SaaS module for the current HTTP request.
     */
    public static function moduleForRequest(Request $request): ?string
    {
        $routeName = $request->route()?->getName();

        if (! $routeName) {
            return null;
        }

        if (str_starts_with($routeName, 'visits.') || str_starts_with($routeName, 'test-orders.')) {
            return match (static::visitTypeFromRequest($request)) {
                'emergency' => 'emergency',
                'ipd' => 'ipd',
                default => 'visits',
            };
        }

        return static::moduleForRoute($routeName);
    }

    /**
     * Find which module a route name belongs to.
     * Returns null if the route isn't gated by any module.
     */
    public static function moduleForRoute(string $routeName): ?string
    {
        $bestSlug = null;
        $bestLength = -1;

        foreach (static::$modules as $slug => $definition) {
            foreach ($definition['routes'] ?? [] as $prefix) {
                if (str_starts_with($routeName, $prefix) && strlen($prefix) > $bestLength) {
                    $bestSlug = $slug;
                    $bestLength = strlen($prefix);
                }
            }
        }

        return $bestSlug;
    }

    /**
     * Get display name for a module slug.
     */
    public static function nameFor(string $slug): string
    {
        return static::$modules[$slug]['name'] ?? $slug;
    }

    private static function visitTypeFromRequest(Request $request): ?string
    {
        $route = $request->route();
        $visit = $route && $route->hasParameters() ? $route->parameter('visit') : null;

        if (is_object($visit) && isset($visit->visit_type)) {
            return $visit->visit_type;
        }

        $type = $request->query('visit_type') ?? $request->input('visit_type');

        if (is_string($type) && in_array($type, ['opd', 'ipd', 'emergency'], true)) {
            return $type;
        }

        return null;
    }
}
