<aside id="sidebar" class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg border-r border-gray-200 overflow-y-auto z-40 -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <div class="p-4 border-b border-gray-200">
        @php
            $hospitalLogo = cache('settings.hospital_logo');
            $hospitalName = cache('settings.hospital_name', 'HMS Admin');
        @endphp

        <div class="flex items-center px-4 py-3">
            <div class="bg-medical-blue rounded-lg p-2 flex items-center justify-center mr-3">
                <i class="fas fa-hospital text-white text-lg"></i>
            </div>
            <h1 class="text-xl font-bold text-medical-blue">Hospityo</h1>
        </div>
    </div>

    <nav class="mt-6 pb-6">
        <ul class="space-y-2 px-4">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('dashboard') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                    <span>{{ __('messages.dashboard') }}</span>
                </a>
            </li>
            <li>
                <a href="{{ route('patients.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('patients.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-user-injured mr-3 w-5"></i>
                    <span>{{ __('messages.patients') }}</span>
                </a>
            </li>
            @can('view departments')
            <li>
                <a href="{{ route('departments.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('departments.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-building mr-3 w-5"></i>
                    <span>{{ __('messages.departments') }}</span>
                </a>
            </li>
            @endcan
            @can('view doctors')
            <li>
                <a href="{{ route('doctors.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('doctors.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-user-md mr-3 w-5"></i>
                    <span>{{ __('messages.doctors') }}</span>
                </a>
            </li>
            @endcan
            @can('view visits')
            <li>
                <a href="{{ route('visits.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('visits.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-clipboard-list mr-3 w-5"></i>
                    <span>{{ __('messages.visits') }}</span>
                </a>
            </li>
            @endcan
            @can('view appointments')
            <li>
                <a href="{{ route('appointments.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('appointments.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-calendar-check mr-3 w-5"></i>
                    <span>{{ __('messages.appointments') }}</span>
                </a>
            </li>
            @endcan

            <!-- IPD Management Section -->
            @canany(['view departments', 'create departments'])
            <li class="pt-4">
                <button onclick="toggleSubmenu('ipd')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                    <span>{{ __('messages.ipd_management') }}</span>
                    <i id="ipd-icon" class="fas fa-chevron-down text-xs transition-transform {{ request()->routeIs('wards.*', 'beds.*') ? 'rotate-180' : '' }}"></i>
                </button>
            </li>
            @endcanany
            @can('view departments')
            <div id="ipd-submenu" class="space-y-1 {{ request()->routeIs('wards.*', 'beds.*') ? '' : 'hidden' }}">
                <li>
                    <a href="{{ route('wards.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('wards.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-hospital mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.wards') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('beds.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('beds.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-bed mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.beds') }}</span>
                    </a>
                </li>
            </div>
            @endcan

            <!-- Pharmacy Section -->
            @canany(['view services', 'create services'])
            <li class="pt-4">
                <button onclick="toggleSubmenu('pharmacy')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                    <span>{{ __('messages.pharmacy') }}</span>
                    <i id="pharmacy-icon" class="fas fa-chevron-down text-xs transition-transform {{ request()->routeIs('medicine-categories.*', 'medicine-brands.*', 'medicines.*', 'prescription-instructions.*', 'units.*', 'inventory.*', 'suppliers.*', 'purchases.*') ? 'rotate-180' : '' }}"></i>
                </button>
            </li>
            @endcanany
            @can('view services')
            <div id="pharmacy-submenu" class="space-y-1 {{ request()->routeIs('medicine-categories.*', 'medicine-brands.*', 'medicines.*', 'prescription-instructions.*', 'units.*', 'inventory.*', 'suppliers.*', 'purchases.*') ? '' : 'hidden' }}">
                <li>
                    <a href="{{ route('medicine-categories.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('medicine-categories.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-tags mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.categories') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('medicine-brands.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('medicine-brands.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-copyright mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.brands') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('medicines.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('medicines.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-pills mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.medicines') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('prescription-instructions.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('prescription-instructions.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-file-prescription mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.instructions') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('units.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('units.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-balance-scale mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.units') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('inventory.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('inventory.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-boxes mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.inventory') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('suppliers.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-truck mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.suppliers') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('purchases.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('purchases.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-shopping-cart mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.purchase_orders') }}</span>
                    </a>
                </li>
            </div>
            @endcan

            <!-- Laboratory Section -->
            @canany(['view services', 'create services'])
            <li class="pt-4">
                <button onclick="toggleSubmenu('diagnostics')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                    <span>{{ __('messages.diagnostics') }}</span>
                    <i id="diagnostics-icon" class="fas fa-chevron-down text-xs transition-transform {{ request()->routeIs('investigations.*', 'investigation-orders.*', 'lab-results.*', 'lab-tests.*', 'lab-orders.*', 'radiology-results.*') ? 'rotate-180' : '' }}"></i>
                </button>
            </li>
            @endcanany
            @can('view services')
            <div id="diagnostics-submenu" class="space-y-1 {{ request()->routeIs('investigations.*', 'investigation-orders.*', 'lab-results.*', 'lab-tests.*', 'lab-orders.*', 'radiology-results.*') ? '' : 'hidden' }}">
                <li>
                    <a href="{{ route('investigations.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('investigations.*') || request()->routeIs('lab-tests.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-flask mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.investigations') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('investigation-orders.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('investigation-orders.*') || request()->routeIs('lab-orders.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-clipboard-list mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.investigation_orders') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('lab-results.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('lab-results.*') || request()->routeIs('radiology-results.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-file-medical-alt mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.investigation_results') }}</span>
                    </a>
                </li>
            </div>
            @endcan

            <!-- Billing Section -->
            @canany(['view bills', 'view services'])
            <li class="pt-4">
                <button onclick="toggleSubmenu('billing')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                    <span>{{ __('messages.billing') }}</span>
                    <i id="billing-icon" class="fas fa-chevron-down text-xs transition-transform {{ request()->routeIs('bills.*', 'services.*') ? 'rotate-180' : '' }}"></i>
                </button>
            </li>
            @endcanany
            <div id="billing-submenu" class="space-y-1 {{ request()->routeIs('bills.*', 'services.*') ? '' : 'hidden' }}">
                @can('view bills')
                <li>
                    <a href="{{ route('bills.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('bills.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-file-invoice-dollar mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.bills') }}</span>
                    </a>
                </li>
                @endcan
                @can('view services')
                <li>
                    <a href="{{ route('services.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('services.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-concierge-bell mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.services') }}</span>
                    </a>
                </li>
                @endcan
            </div>

            <!-- Reports Section -->
            <li class="pt-4">
                <button onclick="toggleSubmenu('reports')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                    <span>{{ __('messages.reports') }}</span>
                    <i id="reports-icon" class="fas fa-chevron-down text-xs transition-transform {{ request()->routeIs('reports.*') ? 'rotate-180' : '' }}"></i>
                </button>
            </li>
            <div id="reports-submenu" class="space-y-1 {{ request()->routeIs('reports.*') ? '' : 'hidden' }}">
                <li>
                    <a href="{{ route('reports.daily-cash-register') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.daily-cash-register') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-cash-register mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.daily_cash_register') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.patient-visits') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.patient-visits') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-user-clock mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.patient_visit_report') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.revenue') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.revenue') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-chart-line mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.revenue_report') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.outstanding-bills') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.outstanding-bills') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-file-invoice-dollar mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.outstanding_bills') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.lab-tests') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.lab-tests') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-flask mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.lab_test_report') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.medicine-sales') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.medicine-sales') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-pills mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.medicine_sales_report') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.inventory-status') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.inventory-status') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-boxes mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.inventory_status') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.expiry-report') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.expiry-report') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-calendar-times mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.expiry_report') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.doctor-performance') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.doctor-performance') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-user-md mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.doctor_performance') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.appointment-statistics') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.appointment-statistics') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-calendar-alt mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.appointment_statistics') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.ipd-report') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.ipd-report') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-procedures mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.ipd_report') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.department-performance') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.department-performance') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-building mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.department_performance') }}</span>
                    </a>
                </li>
                <li>
                    <a href="{{ route('reports.patient-demographics') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('reports.patient-demographics') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-chart-pie mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.patient_demographics') }}</span>
                    </a>
                </li>
            </div>

            <!-- RBAC Section -->
            @canany(['view roles', 'view permissions', 'manage user roles', 'view users'])
            <li class="pt-4">
                <button onclick="toggleSubmenu('access')" class="w-full flex items-center justify-between px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider hover:text-gray-700 transition-colors">
                    <span>{{ __('messages.access_control') }}</span>
                    <i id="access-icon" class="fas fa-chevron-down text-xs transition-transform {{ request()->routeIs('users.*', 'roles.*', 'permissions.*', 'audit-logs.*') ? 'rotate-180' : '' }}"></i>
                </button>
            </li>
            @endcanany
            <div id="access-submenu" class="space-y-1 {{ request()->routeIs('users.*', 'roles.*', 'permissions.*', 'audit-logs.*') ? '' : 'hidden' }}">
                @hasrole('Super Admin|Hospital Administrator')
                <li>
                    <a href="{{ route('users.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('users.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-users mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.users') }}</span>
                    </a>
                </li>
                @endhasrole
                @can('view roles')
                <li>
                    <a href="{{ route('roles.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('roles.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-user-tag mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.roles') }}</span>
                    </a>
                </li>
                @endcan
                @can('view permissions')
                <li>
                    <a href="{{ route('permissions.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('permissions.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-key mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.permissions') }}</span>
                    </a>
                </li>
                @endcan
                @hasrole('Super Admin|Hospital Administrator')
                <li>
                    <a href="{{ route('audit-logs.index') }}" class="flex items-center px-4 py-2 pl-8 text-sm text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('audit-logs.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                        <i class="fas fa-history mr-3 text-xs w-5"></i>
                        <span>{{ __('messages.audit_logs') }}</span>
                    </a>
                </li>
                @endhasrole
            </div>
            
            <!-- Backup & Restore -->
            @hasrole('Super Admin|Hospital Administrator')
            <li class="pt-4">
                <a href="{{ route('backup.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('backup.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-database mr-3 w-5"></i>
                    <span>{{ __('messages.backup_restore') }}</span>
                </a>
            </li>
            @endhasrole
        </ul>
    </nav>
</aside>

<script>
function toggleSubmenu(menuId) {
    const submenu = document.getElementById(menuId + '-submenu');
    const icon = document.getElementById(menuId + '-icon');
    
    if (submenu.classList.contains('hidden')) {
        submenu.classList.remove('hidden');
        icon.classList.add('rotate-180');
    } else {
        submenu.classList.add('hidden');
        icon.classList.remove('rotate-180');
    }
}
</script>
