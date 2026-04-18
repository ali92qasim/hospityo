<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Expired — Hospityo</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-lg p-8 text-center">
            <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-6">
                <i class="fas fa-clock text-orange-500 text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Trial Period Ended</h1>
            <p class="text-gray-600 mb-6">
                Your free trial for <span class="font-semibold">{{ $tenant->name }}</span> expired on
                <span class="font-semibold">{{ $tenant->trial_ends_at->format('M d, Y') }}</span>.
            </p>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-6 text-sm text-orange-800">
                <i class="fas fa-info-circle mr-1"></i>
                Your data is safe and will be retained for 90 days. Subscribe to a plan to regain access.
            </div>
            <div class="space-y-3">
                <a href="{{ route('subscription.index') }}" class="block w-full bg-medical-blue text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                    <i class="fas fa-credit-card mr-2"></i>Subscribe Now
                </a>
                <a href="{{ config('app.url') }}/contact" class="block w-full border border-gray-300 text-gray-700 py-2.5 px-4 rounded-lg hover:bg-gray-50 transition-colors text-sm">
                    <i class="fas fa-envelope mr-2"></i>Contact Support
                </a>
            </div>
            <p class="mt-6 text-xs text-gray-400">
                Current plan: {{ $tenant->plan?->name ?? 'None' }} •
                Hospital ID: {{ $tenant->slug }}
            </p>
        </div>
    </div>
</body>
</html>
