<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full text-center">
            <!-- Error Icon -->
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-red-100 rounded-full">
                    <i class="fas fa-server text-red-600 text-5xl"></i>
                </div>
            </div>

            <!-- Error Code -->
            <h1 class="text-6xl font-bold text-gray-800 mb-4">500</h1>

            <!-- Error Message -->
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Internal Server Error</h2>
            <p class="text-gray-600 mb-8">
                Oops! Something went wrong on our end. 
                Our team has been notified and is working to fix the issue.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Go to Dashboard
                </a>
                <button onclick="window.location.reload()" 
                        class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    Try Again
                </button>
            </div>

            <!-- Help Text -->
            <div class="mt-8 text-sm text-gray-500">
                <p>If the problem persists, please contact technical support.</p>
                @if(config('app.debug'))
                    <p class="mt-2 text-red-600">Debug mode is enabled. Check logs for details.</p>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
