<aside class="fixed left-0 top-0 w-64 h-full bg-white shadow-lg border-r border-gray-200 overflow-y-auto">
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
    
    <nav class="mt-6">
        <ul class="space-y-2 px-4">
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('dashboard') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="{{ route('patients.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('patients.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-user-injured mr-3"></i>
                    Patients
                </a>
            </li>
            @can('view departments')
            <li>
                <a href="{{ route('departments.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('departments.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-building mr-3"></i>
                    Departments
                </a>
            </li>
            @endcan
            @can('view doctors')
            <li>
                <a href="{{ route('doctors.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('doctors.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-user-md mr-3"></i>
                    Doctors
                </a>
            </li>
            @endcan
            @can('view visits')
            <li>
                <a href="{{ route('visits.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('visits.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-clipboard-list mr-3"></i>
                    Visits
                </a>
            </li>
            @endcan
            @can('view appointments')
            <li>
                <a href="{{ route('appointments.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('appointments.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-calendar-check mr-3"></i>
                    Appointments
                </a>
            </li>
            @endcan
            
            <!-- IPD Management Section -->
            @canany(['view departments', 'create departments'])
            <li class="pt-4">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    IPD Management
                </div>
            </li>
            @endcanany
            @can('view departments')
            <li>
                <a href="{{ route('wards.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('wards.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-hospital mr-3"></i>
                    Wards
                </a>
            </li>
            <li>
                <a href="{{ route('beds.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('beds.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-bed mr-3"></i>
                    Beds
                </a>
            </li>
            @endcan
            
            <!-- Pharmacy Section -->
            @canany(['view services', 'create services'])
            <li class="pt-4">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Pharmacy
                </div>
            </li>
            @endcanany
            @can('view services')
            <li>
                <a href="{{ route('medicines.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('medicines.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-pills mr-3"></i>
                    Medicines
                </a>
            </li>
            <li>
                <a href="{{ route('units.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('units.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-balance-scale mr-3"></i>
                    Units
                </a>
            </li>
            <li>
                <a href="{{ route('inventory.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('inventory.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-boxes mr-3"></i>
                    Inventory
                </a>
            </li>
            <li>
                <a href="{{ route('suppliers.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('suppliers.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-truck mr-3"></i>
                    Suppliers
                </a>
            </li>
            <li>
                <a href="{{ route('purchases.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('purchases.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-shopping-cart mr-3"></i>
                    Purchase Orders
                </a>
            </li>
            @endcan
            
            <!-- Laboratory Section -->
            @canany(['view services', 'create services'])
            <li class="pt-4">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Laboratory
                </div>
            </li>
            @endcanany
            @can('view services')
            <li>
                <a href="{{ route('lab-tests.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('lab-tests.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-flask mr-3"></i>
                    Lab Tests
                </a>
            </li>
            <li>
                <a href="{{ route('lab-orders.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('lab-orders.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-clipboard-list mr-3"></i>
                    Lab Orders
                </a>
            </li>
            <li>
                <a href="{{ route('lab-results.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('lab-results.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-chart-line mr-3"></i>
                    Lab Results
                </a>
            </li>


            @endcan
            
            <!-- Billing Section -->
            @canany(['view bills', 'view services'])
            <li class="pt-4">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Billing
                </div>
            </li>
            @endcanany
            @can('view bills')
            <li>
                <a href="{{ route('bills.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('bills.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-file-invoice-dollar mr-3"></i>
                    Bills
                </a>
            </li>
            @endcan
            @can('view services')
            <li>
                <a href="{{ route('services.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('services.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-concierge-bell mr-3"></i>
                    Services
                </a>
            </li>
            @endcan
            
            <!-- RBAC Section -->
            @canany(['view roles', 'view permissions', 'manage user roles', 'view users'])
            <li class="pt-4">
                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    Access Control
                </div>
            </li>
            @endcanany
            @hasrole('Super Admin|Hospital Administrator')
            <li>
                <a href="{{ route('users.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('users.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
            </li>
            @endhasrole
            @can('view roles')
            <li>
                <a href="{{ route('roles.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('roles.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-user-tag mr-3"></i>
                    Roles
                </a>
            </li>
            @endcan
            @can('view permissions')
            <li>
                <a href="{{ route('permissions.index') }}" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-medical-light hover:text-medical-blue transition-colors {{ request()->routeIs('permissions.*') ? 'bg-medical-light text-medical-blue' : '' }}">
                    <i class="fas fa-key mr-3"></i>
                    Permissions
                </a>
            </li>
            @endcan
        </ul>
    </nav>
</aside>