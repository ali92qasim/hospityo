<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin') — Hospityo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    @include('super-admin.partials.sidebar')

    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-30 hidden"></div>

    <div class="lg:ml-64">
        @include('super-admin.partials.header')

        <main class="p-3 sm:p-4 md:p-6">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>

    @stack('scripts')

    <script>
    (function() {
        const btn = document.getElementById('mobile-menu-btn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        function toggle() {
            const hidden = sidebar.classList.contains('-translate-x-full');
            if (hidden) { sidebar.classList.remove('-translate-x-full'); overlay.classList.remove('hidden'); document.body.classList.add('overflow-hidden'); }
            else { sidebar.classList.add('-translate-x-full'); overlay.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
        }
        if (btn) btn.addEventListener('click', toggle);
        if (overlay) overlay.addEventListener('click', toggle);
        let t; window.addEventListener('resize', function() { clearTimeout(t); t = setTimeout(function() {
            if (window.innerWidth >= 1024) { sidebar.classList.remove('-translate-x-full'); overlay.classList.add('hidden'); document.body.classList.remove('overflow-hidden'); }
            else if (overlay.classList.contains('hidden')) { sidebar.classList.add('-translate-x-full'); }
        }, 250); });
    })();
    </script>
</body>
</html>
