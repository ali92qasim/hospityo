<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Hospityo')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'medical-blue': '#0066CC',
                        'medical-green': '#00A86B',
                        'medical-light': '#F0F8FF',
                        'medical-gray': '#6B7280'
                    }
                }
            }
        }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    @include('partials.sidebar')
    
    <div class="ml-64">
        @include('partials.header')
        
        <main class="p-6">
            @include('partials.alerts')
            @yield('content')
        </main>
    </div>
</body>
</html>