<header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
            <p class="text-sm text-gray-600">@yield('page-description', 'Welcome to Hospityo')</p>
        </div>
        
        <div class="flex items-center space-x-4">
            <div class="relative" id="notification-dropdown">
                <button onclick="toggleNotifications()" class="relative p-2 text-gray-600 hover:text-medical-blue">
                    <i class="fas fa-bell text-lg"></i>
                    <span id="notification-count" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                </button>
                
                <div id="notification-panel" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible transition-all duration-200 z-50">
                    <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="font-medium text-gray-800">Notifications</h3>
                        <button onclick="markAllAsRead()" class="text-xs text-medical-blue hover:underline">Mark all read</button>
                    </div>
                    <div id="notification-list" class="max-h-64 overflow-y-auto">
                        <div class="p-4 text-center text-gray-500">No notifications</div>
                    </div>
                </div>
            </div>
            
            <div class="relative group">
                <button class="flex items-center space-x-3 hover:bg-gray-50 rounded-lg p-2">
                    <div class="w-8 h-8 bg-medical-blue rounded-full flex items-center justify-center">
                        <i class="fas fa-user text-white text-sm"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-700">{{ Auth::user()->name }}</span>
                    <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                </button>
                
                <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                    <div class="py-2">
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-user-circle mr-2"></i>Profile
                        </a>
                        <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-cog mr-2"></i>Settings
                        </a>
                        <hr class="my-2">
                        <form method="POST" action="{{ route('logout') }}" class="block">
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