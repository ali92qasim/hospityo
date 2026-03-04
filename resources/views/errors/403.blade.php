<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full text-center">
            <!-- Error Icon -->
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-yellow-100 rounded-full">
                    <i class="fas fa-lock text-yellow-600 text-5xl"></i>
                </div>
            </div>

            <!-- Error Code -->
            <h1 class="text-6xl font-bold text-gray-800 mb-4">403</h1>

            <!-- Error Message -->
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Access Denied</h2>
            <p class="text-gray-600 mb-8">
                You don't have permission to access this resource. 
                Please contact your administrator if you believe this is an error.
            </p>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('dashboard') }}" 
                   class="inline-flex items-center justify-center px-6 py-3 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-home mr-2"></i>
                    Go to Dashboard
                </a>
                <button onclick="window.history.back()" 
                        class="inline-flex items-center justify-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Go Back
                </button>
            </div>

            <!-- Help Text -->
            <div class="mt-8 text-sm text-gray-500">
                <p>Need access? Contact your system administrator.</p>
            </div>
        </div>
    </div>
</body>
</html>
