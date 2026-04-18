<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
    <div class="px-3 sm:px-4 md:px-6 py-3 md:py-4">
        <div class="flex items-center justify-between gap-2">
            <button id="mobile-menu-btn" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none p-2 -ml-2">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="flex-1 min-w-0">
                <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 truncate">@yield('page-title', 'Super Admin')</h2>
                <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">@yield('page-description', 'Platform management')</p>
            </div>

            {{-- User dropdown --}}
            <div class="relative group flex-shrink-0">
                <button class="flex items-center gap-2 hover:bg-gray-50 rounded-lg p-2">
                    <div class="w-8 h-8 sm:w-9 sm:h-9 bg-gray-900 rounded-full flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-shield-alt text-white text-sm"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700 hidden md:inline max-w-[150px] truncate">{{ auth()->guard('super_admin')->user()->name }}</span>
                    <i class="fas fa-chevron-down text-xs text-gray-500 hidden md:inline"></i>
                </button>

                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                    <div class="py-2">
                        <a href="{{ route('super-admin.profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-user-circle mr-2"></i>Profile
                        </a>
                        <a href="{{ route('super-admin.site-settings.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                        <hr class="my-2">
                        <form method="POST" action="{{ route('super-admin.logout') }}" class="block">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <i class="fas fa-sign-out-alt mr-2"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
