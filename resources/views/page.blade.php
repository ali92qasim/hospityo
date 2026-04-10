<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} — Hospityo</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased">

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <div class="h-9 w-9 bg-medical-blue rounded-lg flex items-center justify-center">
                    <i class="fas fa-hospital text-white text-sm"></i>
                </div>
                <span class="text-xl font-bold text-gray-900">Hospityo</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm text-gray-600 hover:text-medical-blue"><i class="fas fa-arrow-left mr-1"></i>Back to Home</a>
        </div>
    </div>
</nav>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white rounded-lg shadow-sm p-8 sm:p-12">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $page->title }}</h1>
        <p class="text-sm text-gray-500 mb-8">Last updated: {{ $page->updated_at->format('F d, Y') }}</p>
        <div class="prose prose-gray max-w-none text-sm leading-relaxed">
            {!! $page->content !!}
        </div>
    </div>
</main>

<footer class="py-8 text-center text-sm text-gray-500">
    &copy; {{ date('Y') }} Hospityo. All rights reserved.
</footer>

</body>
</html>
