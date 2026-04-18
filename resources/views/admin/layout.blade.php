<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospityo')</title>
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Sidebar - hidden off-screen on mobile by default -->
    @include('partials.sidebar')
    
    <!-- Mobile overlay - only shows when sidebar is open -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-30 hidden"></div>
    
    <!-- Main content area -->
    <div class="lg:ml-64">
        @include('partials.header')
        
        <main class="p-3 sm:p-4 md:p-6">
            @php
                $currentTenant = \App\Models\Tenant::current();
            @endphp
            @if($currentTenant && $currentTenant->onTrial())
                <div class="mb-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-center text-sm text-amber-800">
                        <i class="fas fa-clock mr-2"></i>
                        <span>Trial ends in <span class="font-semibold">{{ $currentTenant->trialDaysRemaining() }} day{{ $currentTenant->trialDaysRemaining() !== 1 ? 's' : '' }}</span> ({{ $currentTenant->trial_ends_at->format('M d, Y') }})</span>
                    </div>
                    <a href="{{ route('subscription.index') }}" class="text-sm font-medium text-amber-800 hover:text-amber-900 underline whitespace-nowrap">
                        Upgrade Now <i class="fas fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            @endif
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>
    
    @stack('scripts')
    
    <script>
        // Mobile menu functionality
        (function() {
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const sidebar = document.getElementById('sidebar');
            const mobileOverlay = document.getElementById('mobile-overlay');
            
            function toggleMobileMenu() {
                const isHidden = sidebar.classList.contains('-translate-x-full');
                
                if (isHidden) {
                    // Open sidebar
                    sidebar.classList.remove('-translate-x-full');
                    mobileOverlay.classList.remove('hidden');
                    document.body.classList.add('overflow-hidden');
                } else {
                    // Close sidebar
                    sidebar.classList.add('-translate-x-full');
                    mobileOverlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            }
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', toggleMobileMenu);
            }
            
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', toggleMobileMenu);
            }
            
            // Handle window resize
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (window.innerWidth >= 1024) {
                        // Desktop: show sidebar, hide overlay
                        sidebar.classList.remove('-translate-x-full');
                        mobileOverlay.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                    } else {
                        // Mobile: ensure sidebar is hidden if overlay is not visible
                        if (mobileOverlay.classList.contains('hidden')) {
                            sidebar.classList.add('-translate-x-full');
                        }
                    }
                }, 250);
            });
        })();
    </script>
</body>
</html>