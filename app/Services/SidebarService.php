<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Builds the sidebar navigation tree for the current user.
 *
 * Each menu group declares:
 *   - label        : display text
 *   - module       : tenant module slug (optional — if set, tenant must have this module)
 *   - permission   : Spatie permission name required to see the group header
 *   - role         : role name(s) required instead of a permission (for admin-only sections)
 *   - items        : array of leaf links, each with label, route, icon, permission (optional)
 *
 * The sidebar blade iterates over the result of build() and renders only
 * what the current user is allowed to see — no hardcoded @can() strings.
 */
class SidebarService
{
    /**
     * Build the sidebar menu for the given user.
     *
     * @return array<int, array{label: string, id: string, icon: string, route: string|null, items: array, active_patterns: string[]}>
     */
    public function build(Authenticatable $user, ?Tenant $tenant): array
    {
        $menu = [];

        // ── Dashboard (always visible) ────────────────────────────────────────
        $menu[] = $this->link('dashboard', 'Dashboard', 'fa-tachometer-alt', 'dashboard', ['dashboard']);

        // ── Patients ──────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'patients') && $user->can('view patients')) {
            $menu[] = $this->link('patients', 'Patients', 'fa-user-injured', 'patients.index', ['patients.*']);
        }

        // ── Doctors ───────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'doctors') && $user->can('view doctors')) {
            $menu[] = $this->link('doctors', 'Doctors', 'fa-user-md', 'doctors.index', ['doctors.*']);
        }

        // ── Departments ───────────────────────────────────────────────────────
        if ($user->can('view departments')) {
            $menu[] = $this->link('departments', 'Departments', 'fa-building', 'departments.index', ['departments.*']);
        }

        // ── Visits ────────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'visits') && $user->can('view visits')) {
            $menu[] = $this->link('visits', 'Visits', 'fa-clipboard-list', 'visits.index', ['visits.*']);
        }

        // ── Appointments ──────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'appointments') && $user->can('view appointments')) {
            $menu[] = $this->link('appointments', 'Appointments', 'fa-calendar-check', 'appointments.index', ['appointments.*']);
        }

        // ── IPD Management ────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'ipd') && ($user->can('view wards') || $user->can('view beds'))) {
            $items = [];
            if ($user->can('view wards')) {
                $items[] = $this->item('Wards', 'fa-hospital', 'wards.index', ['wards.*']);
            }
            if ($user->can('view beds')) {
                $items[] = $this->item('Beds', 'fa-bed', 'beds.index', ['beds.*']);
            }
            if (!empty($items)) {
                $menu[] = $this->group('ipd', 'IPD Management', $items, ['wards.*', 'beds.*']);
            }
        }

        // ── Pharmacy ──────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'pharmacy') && ($user->can('view services') || $user->can('view pharmacy') || $user->can('manage pharmacy'))) {
            $items = [
                $this->item('Categories', 'fa-tags', 'medicine-categories.index', ['medicine-categories.*']),
                $this->item('Brands', 'fa-copyright', 'medicine-brands.index', ['medicine-brands.*']),
                $this->item('Medicines', 'fa-pills', 'medicines.index', ['medicines.*']),
                $this->item('Instructions', 'fa-file-prescription', 'prescription-instructions.index', ['prescription-instructions.*']),
                $this->item('Units', 'fa-balance-scale', 'units.index', ['units.*']),
                $this->item('Inventory', 'fa-boxes', 'inventory.index', ['inventory.*']),
                $this->item('Suppliers', 'fa-truck', 'suppliers.index', ['suppliers.*']),
                $this->item('Purchase Orders', 'fa-shopping-cart', 'purchases.index', ['purchases.*']),
            ];
            $menu[] = $this->group('pharmacy', 'Pharmacy', $items, [
                'medicine-categories.*', 'medicine-brands.*', 'medicines.*',
                'prescription-instructions.*', 'units.*', 'inventory.*',
                'suppliers.*', 'purchases.*',
            ]);
        }

        // ── Diagnostics / Laboratory ──────────────────────────────────────────
        if ($this->hasModule($tenant, 'laboratory') && ($user->can('view investigations') || $user->can('view investigation orders') || $user->can('view lab results'))) {
            $items = [];
            if ($user->can('view investigations')) {
                $items[] = $this->item('Investigations', 'fa-flask', 'investigations.index', ['investigations.*', 'lab-tests.*']);
            }
            if ($user->can('view investigation orders') || $user->can('view lab orders')) {
                $items[] = $this->item('Investigation Orders', 'fa-clipboard-list', 'investigation-orders.index', ['investigation-orders.*', 'lab-orders.*']);
            }
            if ($user->can('view lab results') || $user->can('view radiology results')) {
                $items[] = $this->item('Results', 'fa-file-medical-alt', 'lab-results.index', ['lab-results.*', 'radiology-results.*']);
            }
            if (!empty($items)) {
                $menu[] = $this->group('diagnostics', 'Diagnostics', $items, [
                    'investigations.*', 'investigation-orders.*', 'lab-results.*',
                    'lab-tests.*', 'lab-orders.*', 'radiology-results.*',
                ]);
            }
        }

        // ── Billing ───────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'billing') && ($user->can('view bills') || $user->can('view services'))) {
            $items = [];
            if ($user->can('view bills')) {
                $items[] = $this->item('Bills', 'fa-file-invoice-dollar', 'bills.index', ['bills.*']);
            }
            if ($user->can('view services')) {
                $items[] = $this->item('Services', 'fa-concierge-bell', 'services.index', ['services.*']);
            }
            if ($user->can('view bills')) {
                $items[] = $this->item('Tax Configuration', 'fa-percentage', 'taxes.index', ['taxes.*']);
            }
            if (!empty($items)) {
                $menu[] = $this->group('billing', 'Billing', $items, ['bills.*', 'services.*', 'taxes.*']);
            }
        }

        // ── Doctor Share ──────────────────────────────────────────────────────
        if ($user->can('manage doctor shares')) {
            $items = [
                $this->item('Share Rules', 'fa-list-alt', 'doctor-share.rules.index', ['doctor-share.rules.*']),
                $this->item('Share Items', 'fa-hand-holding-usd', 'doctor-share.items.index', ['doctor-share.items.*']),
                $this->item('Settlements', 'fa-check-circle', 'doctor-share.settlements.index', ['doctor-share.settlements.*']),
                $this->item('Share Reports', 'fa-chart-bar', 'doctor-share.reports.index', ['doctor-share.reports.*']),
            ];
            $menu[] = $this->group('doctor-share', 'Doctor Share', $items, ['doctor-share.*']);
        }

        // ── Accounting ────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'billing') && $user->can('view accounting')) {
            $items = [
                $this->item('Chart of Accounts', 'fa-sitemap', 'accounting.chart-of-accounts', ['accounting.chart-of-accounts*', 'accounting.create-account*']),
                $this->item('Journal Entries', 'fa-book', 'accounting.journal-entries', ['accounting.journal-entries']),
                $this->item('General Ledger', 'fa-file-alt', 'accounting.general-ledger', ['accounting.general-ledger']),
                $this->item('Patient Ledger', 'fa-user', 'accounting.patient-ledger', ['accounting.patient-ledger']),
                $this->item('Vendor Ledger', 'fa-truck', 'accounting.vendor-ledger', ['accounting.vendor-ledger']),
                $this->item('Profit & Loss', 'fa-chart-line', 'accounting.profit-loss', ['accounting.profit-loss']),
                $this->item('Balance Sheet', 'fa-balance-scale', 'accounting.balance-sheet', ['accounting.balance-sheet']),
            ];
            $menu[] = $this->group('accounting', 'Accounting', $items, ['accounting.*']);
        }

        // ── Reports ───────────────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'reports') && $user->can('view reports')) {
            $items = [
                $this->item('Daily Cash Register', 'fa-cash-register', 'reports.daily-cash-register', ['reports.daily-cash-register']),
                $this->item('Patient Visits', 'fa-user-clock', 'reports.patient-visits', ['reports.patient-visits']),
                $this->item('Revenue Report', 'fa-chart-line', 'reports.revenue', ['reports.revenue']),
                $this->item('Outstanding Bills', 'fa-file-invoice-dollar', 'reports.outstanding-bills', ['reports.outstanding-bills']),
                $this->item('Lab Test Report', 'fa-flask', 'reports.lab-tests', ['reports.lab-tests']),
                $this->item('Medicine Sales', 'fa-pills', 'reports.medicine-sales', ['reports.medicine-sales']),
                $this->item('Inventory Status', 'fa-boxes', 'reports.inventory-status', ['reports.inventory-status']),
                $this->item('Expiry Report', 'fa-calendar-times', 'reports.expiry-report', ['reports.expiry-report']),
                $this->item('Doctor Performance', 'fa-user-md', 'reports.doctor-performance', ['reports.doctor-performance']),
                $this->item('Appointment Statistics', 'fa-calendar-alt', 'reports.appointment-statistics', ['reports.appointment-statistics']),
                $this->item('IPD Report', 'fa-procedures', 'reports.ipd-report', ['reports.ipd-report']),
                $this->item('Department Performance', 'fa-building', 'reports.department-performance', ['reports.department-performance']),
                $this->item('Patient Demographics', 'fa-chart-pie', 'reports.patient-demographics', ['reports.patient-demographics']),
            ];
            $menu[] = $this->group('reports', 'Reports', $items, ['reports.*']);
        }

        // ── HR ────────────────────────────────────────────────────────────────
        if ($user->can('view hr')) {
            $items = [
                $this->item('Employees', 'fa-users', 'hr.employees.index', ['hr.employees.*']),
                $this->item('Designations', 'fa-id-badge', 'hr.designations.index', ['hr.designations.*']),
                $this->item('Attendance', 'fa-clipboard-check', 'hr.attendance.index', ['hr.attendance.*']),
                $this->item('Leave Requests', 'fa-calendar-minus', 'hr.leave.index', ['hr.leave.*']),
                $this->item('Leave Balances', 'fa-balance-scale', 'hr.leave.balances', ['hr.leave.balances']),
                $this->item('Payroll', 'fa-money-bill-wave', 'hr.payroll.index', ['hr.payroll.*']),
                $this->item('Shifts', 'fa-clock', 'hr.shifts.index', ['hr.shifts.*']),
                $this->item('Duty Roster', 'fa-calendar-week', 'hr.shifts.roster', ['hr.shifts.roster']),
                $this->item('Departments Staff', 'fa-building', 'hr.department-staff.index', ['hr.department-staff.*']),
            ];
            $menu[] = $this->group('hr', 'HR', $items, ['hr.*']);
        }

        // ── Access Control (RBAC) ─────────────────────────────────────────────
        if ($this->hasModule($tenant, 'rbac') && $user->canAny(['view roles', 'view permissions', 'manage user roles'])) {
            $items = [];
            if ($user->hasAnyRole(['Super Admin', 'Hospital Administrator'])) {
                $items[] = $this->item('Users', 'fa-users', 'users.index', ['users.*']);
            }
            if ($user->can('view roles')) {
                $items[] = $this->item('Roles', 'fa-user-tag', 'roles.index', ['roles.*']);
            }
            if ($user->can('view permissions')) {
                $items[] = $this->item('Permissions', 'fa-key', 'permissions.index', ['permissions.*']);
            }
            if ($user->hasAnyRole(['Super Admin', 'Hospital Administrator'])) {
                $items[] = $this->item('Audit Logs', 'fa-history', 'audit-logs.index', ['audit-logs.*']);
            }
            if (!empty($items)) {
                $menu[] = $this->group('access', 'Access Control', $items, ['users.*', 'roles.*', 'permissions.*', 'audit-logs.*']);
            }
        }

        // ── Backup & Restore ──────────────────────────────────────────────────
        if ($this->hasModule($tenant, 'backup') && $user->hasAnyRole(['Super Admin', 'Hospital Administrator'])) {
            $menu[] = $this->link('backup', 'Backup & Restore', 'fa-database', 'backup.index', ['backup.*']);
        }

        return $menu;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function hasModule(?Tenant $tenant, string $module): bool
    {
        return !$tenant || $tenant->hasModule($module);
    }

    /** A standalone link (no children) */
    private function link(string $id, string $label, string $icon, string $route, array $patterns): array
    {
        return [
            'type'     => 'link',
            'id'       => $id,
            'label'    => $label,
            'icon'     => $icon,
            'route'    => $route,
            'patterns' => $patterns,
        ];
    }

    /** A collapsible group with child items */
    private function group(string $id, string $label, array $items, array $patterns): array
    {
        return [
            'type'     => 'group',
            'id'       => $id,
            'label'    => $label,
            'items'    => $items,
            'patterns' => $patterns,
        ];
    }

    /** A child item inside a group */
    private function item(string $label, string $icon, string $route, array $patterns): array
    {
        return [
            'label'    => $label,
            'icon'     => $icon,
            'route'    => $route,
            'patterns' => $patterns,
        ];
    }
}
