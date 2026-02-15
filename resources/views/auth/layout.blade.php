<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospital Management System')</title>
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="text-center">
                <div class="mx-auto h-16 w-16 bg-medical-blue rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-hospital text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Hospityo</h1>
                <p class="mt-2 text-sm text-gray-600">@yield('subtitle')</p>
            </div>
            
            <div class="bg-white rounded-xl shadow-lg p-8">
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>