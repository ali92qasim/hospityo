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
            'name'   => 'Patient Management',
            'routes' => ['patients.'],
        ],
        'doctors' => [
            'name'   => 'Doctor Management',
            'routes' => ['doctors.', 'doctor.'],
        ],
        'appointments' => [
            'name'   => 'Appointments',
            'routes' => ['appointments.', 'calendar.'],
        ],
        'visits' => [
            'name'   => 'OPD / Visits',
            'routes' => ['visits.', 'test-orders.'],
        ],
        'billing' => [
            'name'   => 'Billing & Invoicing',
            'routes' => ['bills.', 'services.'],
        ],
        'pharmacy' => [
            'name'   => 'Pharmacy & Inventory',
            'routes' => [
                'medicines.', 'medicine-categories.', 'medicine-brands.',
                'prescription-instructions.', 'units.',
                'inventory.', 'suppliers.', 'purchases.',
                'prescriptions.',
            ],
        ],
        'laboratory' => [
            'name'   => 'Laboratory & Radiology',
            'routes' => [
                'investigations.', 'lab-tests.', 'investigation-orders.',
                'lab-orders.', 'lab-results.', 'radiology-results.',
            ],
        ],
        'ipd' => [
            'name'   => 'IPD Management',
            'routes' => ['wards.', 'beds.'],
        ],
        'reports' => [
            'name'   => 'Reports & Analytics',
            'routes' => ['reports.'],
        ],
        'rbac' => [
            'name'   => 'User & Role Management',
            'routes' => ['users.', 'roles.', 'permissions.'],
        ],
        'audit' => [
            'name'   => 'Audit Logs',
            'routes' => ['audit-logs.'],
        ],
        'backup' => [
            'name'   => 'Backup & Restore',
            'routes' => ['backup.'],
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
