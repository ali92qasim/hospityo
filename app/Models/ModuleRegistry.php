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
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['accounting.'],
        ],
        'pharmacy' => [
            'name'        => 'Pharmacy & Inventory',
            'group'       => 'Clinical',
            'description' => 'Medicines, inventory, prescriptions, and POS.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
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
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['ot.'],
        ],
        'doctor-share' => [
            'name'        => 'Doctor Share',
            'group'       => 'Finance',
            'description' => 'Doctor revenue sharing and payouts.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['doctor-share.'],
        ],
        'hr' => [
            'name'        => 'HR & Payroll',
            'group'       => 'Operations',
            'description' => 'Staff, attendance, payroll, and rosters.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['hr.'],
        ],
        'reports' => [
            'name'        => 'Reports & Analytics',
            'group'       => 'Admin',
            'description' => 'Operational and financial reporting dashboards.',
            'entitlement' => 'plan',
            'child_access_requires_explicit_grant' => true,
            'routes'      => ['reports.'],
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
            'child_access_requires_explicit_grant' => false,
            'routes'      => ['settings.index'],
            'children'    => ['settings.hospital-info', 'settings.prescription-print'],
        ],
        'settings.hospital-info' => [
            'name'        => 'Hospital Info',
            'group'       => 'Admin',
            'parent'      => 'settings',
            'description' => 'Hospital name, contact, timezone, and branding.',
            'entitlement' => 'plan',
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
    ];

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
