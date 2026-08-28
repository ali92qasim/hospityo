<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'POS')</title>
    @include('partials.favicon')
    <script>
        window.csrf = @json(csrf_token());
        window.appConfig = {
            currency: @json(currency_symbol()),
            csrf: @json(csrf_token()),
            timezone: @json(app_timezone()),
            dateFormat: @json(setting('date_format', 'd/m/Y')),
            timeFormat: @json(setting('time_format', 'h:i A')),
            timezoneAutoSet: @json(setting('timezone_auto_set') === '1'),
            detectTimezoneUrl: @json(url('/settings/detect-timezone')),
        };
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    @include('admin.pharmacy.pos.partials.header')

    <main class="pb-24 min-h-[calc(100vh-4rem)] max-w-[1600px] mx-auto px-3 sm:px-4 py-4">
        @yield('content')
    </main>

    @include('admin.pharmacy.pos.partials.checkout-bar')
    @include('admin.pharmacy.pos.partials.payment-modal')
    @include('partials.confirm-dialog')

    @vite(['resources/js/pharmacy-pos.js'])
    @stack('scripts')
</body>
</html>
