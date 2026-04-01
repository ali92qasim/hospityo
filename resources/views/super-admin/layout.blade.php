<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — Hospityo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">

{{-- Top Nav --}}
<nav class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <div class="flex items-center space-x-4 sm:space-x-6 min-w-0">
                <a href="{{ route('super-admin.dashboard') }}" class="flex items-center space-x-2 flex-shrink-0">
                    <div class="h-8 w-8 bg-medical-blue rounded-lg flex items-center justify-center">
                        <i class="fas fa-shield-alt text-white text-xs"></i>
                    </div>
                    <span class="font-semibold text-sm hidden sm:inline">Hospityo Super Admin</span>
                    <span class="font-semibold text-sm sm:hidden">Admin</span>
                </a>
                <div class="hidden md:flex items-center space-x-4 text-sm">
                    <a href="{{ route('super-admin.dashboard') }}" class="text-gray-300 hover:text-white transition-colors {{ request()->routeIs('super-admin.dashboard') ? 'text-white' : '' }}">Dashboard</a>
                    <a href="{{ route('super-admin.tenants.index') }}" class="text-gray-300 hover:text-white transition-colors {{ request()->routeIs('super-admin.tenants.*') ? 'text-white' : '' }}">Hospitals</a>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <span class="text-sm text-gray-400 hidden sm:inline truncate max-w-[120px]">{{ auth()->guard('super_admin')->user()->name }}</span>
                <form method="POST" action="{{ route('super-admin.logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-400 hover:text-white transition-colors" aria-label="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </button>
                </form>
                <button id="sa-mobile-btn" class="md:hidden text-gray-400 hover:text-white p-1" aria-label="Toggle menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        {{-- Mobile nav --}}
        <div id="sa-mobile-nav" class="hidden md:hidden pb-3 border-t border-gray-800 mt-1 pt-3">
            <div class="flex flex-col space-y-2 text-sm">
                <a href="{{ route('super-admin.dashboard') }}" class="text-gray-300 hover:text-white py-1 {{ request()->routeIs('super-admin.dashboard') ? 'text-white' : '' }}">Dashboard</a>
                <a href="{{ route('super-admin.tenants.index') }}" class="text-gray-300 hover:text-white py-1 {{ request()->routeIs('super-admin.tenants.*') ? 'text-white' : '' }}">Hospitals</a>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
    @if(session('success'))
        <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 sm:mb-6 p-3 sm:p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif
    @yield('content')
</main>

<script>
(function() {
    var btn = document.getElementById('sa-mobile-btn');
    var nav = document.getElementById('sa-mobile-nav');
    if (btn && nav) {
        btn.addEventListener('click', function() { nav.classList.toggle('hidden'); });
    }
})();
</script>
</body>
</html>
