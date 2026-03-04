<header class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-20">
    <div class="px-3 sm:px-4 md:px-6 py-3 md:py-4">
        <div class="flex items-center justify-between gap-2">
            <!-- Mobile menu button -->
            <button id="mobile-menu-btn" class="lg:hidden text-gray-600 hover:text-gray-900 focus:outline-none p-2 -ml-2">
                <i class="fas fa-bars text-xl"></i>
            </button>
            
            <!-- Page title -->
            <div class="flex-1 min-w-0">
                <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 truncate">@yield('page-title', __('messages.dashboard'))</h2>
                <p class="text-xs sm:text-sm text-gray-600 hidden sm:block truncate">@yield('page-description', __('messages.welcome'))</p>
            </div>
            
            <!-- Language Switcher & User menu -->
            <div class="flex items-center gap-2 flex-shrink-0">
                <!-- Language Switcher -->
                <div class="relative group">
                    <button class="flex items-center gap-1 sm:gap-2 hover:bg-gray-50 rounded-lg p-2 text-gray-700">
                        <i class="fas fa-globe text-base sm:text-lg"></i>
                        <span class="text-xs sm:text-sm font-medium hidden sm:inline">{{ strtoupper(app()->getLocale()) }}</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="py-2">
                            <a href="{{ route('language.switch', 'en') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() == 'en' ? 'bg-medical-light text-medical-blue' : '' }}">
                                <i class="fas fa-check mr-2 {{ app()->getLocale() == 'en' ? '' : 'invisible' }}"></i>English
                            </a>
                            <a href="{{ route('language.switch', 'fr') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() == 'fr' ? 'bg-medical-light text-medical-blue' : '' }}">
                                <i class="fas fa-check mr-2 {{ app()->getLocale() == 'fr' ? '' : 'invisible' }}"></i>Français
                            </a>
                            <a href="{{ route('language.switch', 'es') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() == 'es' ? 'bg-medical-light text-medical-blue' : '' }}">
                                <i class="fas fa-check mr-2 {{ app()->getLocale() == 'es' ? '' : 'invisible' }}"></i>Español
                            </a>
                            <a href="{{ route('language.switch', 'de') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() == 'de' ? 'bg-medical-light text-medical-blue' : '' }}">
                                <i class="fas fa-check mr-2 {{ app()->getLocale() == 'de' ? '' : 'invisible' }}"></i>Deutsch
                            </a>
                            <a href="{{ route('language.switch', 'ar') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ app()->getLocale() == 'ar' ? 'bg-medical-light text-medical-blue' : '' }}">
                                <i class="fas fa-check mr-2 {{ app()->getLocale() == 'ar' ? '' : 'invisible' }}"></i>العربية
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- User menu -->
                <div class="relative group">
                    <button class="flex items-center gap-2 hover:bg-gray-50 rounded-lg p-2">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-medical-blue rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 hidden md:inline max-w-[150px] truncate">{{ Auth::user()->name }}</span>
                        <i class="fas fa-chevron-down text-xs text-gray-500 hidden md:inline"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="py-2">
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user-circle mr-2"></i>{{ __('messages.profile') }}
                            </a>
                            <a href="{{ route('settings.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog mr-2"></i>{{ __('messages.settings') }}
                            </a>
                            <hr class="my-2">
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt mr-2"></i>{{ __('messages.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>