<aside id="sidebar" class="fixed left-0 top-0 w-64 h-full bg-gray-900 shadow-lg overflow-y-auto z-40 -translate-x-full lg:translate-x-0 transition-transform duration-300">
    <div class="p-4 border-b border-gray-800">
        <div class="flex items-center px-4 py-3">
            <div class="bg-medical-blue rounded-lg p-2 flex items-center justify-center mr-3">
                <i class="fas fa-shield-alt text-white text-lg"></i>
            </div>
            <div>
                <h1 class="text-lg font-bold text-white">Hospityo</h1>
                <p class="text-xs text-gray-400">Super Admin</p>
            </div>
        </div>
    </div>

    <nav class="mt-6 pb-6">
        <ul class="space-y-1 px-4">
            <li>
                <a href="{{ route('super-admin.dashboard') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('super-admin.dashboard') ? 'bg-gray-800 text-white' : '' }}">
                    <i class="fas fa-tachometer-alt mr-3 w-5"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="{{ route('super-admin.tenants.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('super-admin.tenants.*') ? 'bg-gray-800 text-white' : '' }}">
                    <i class="fas fa-hospital mr-3 w-5"></i>
                    <span>Hospitals</span>
                </a>
            </li>

            <li>
                <a href="{{ route('super-admin.plans.index') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('super-admin.plans.*') ? 'bg-gray-800 text-white' : '' }}">
                    <i class="fas fa-layer-group mr-3 w-5"></i>
                    <span>Plans</span>
                </a>
            </li>

            <li class="pt-4">
                <p class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Settings</p>
            </li>

            <li>
                <a href="{{ route('super-admin.profile') }}" class="flex items-center px-4 py-3 text-gray-300 rounded-lg hover:bg-gray-800 hover:text-white transition-colors {{ request()->routeIs('super-admin.profile*') ? 'bg-gray-800 text-white' : '' }}">
                    <i class="fas fa-user-cog mr-3 w-5"></i>
                    <span>Profile</span>
                </a>
            </li>
        </ul>
    </nav>
</aside>
