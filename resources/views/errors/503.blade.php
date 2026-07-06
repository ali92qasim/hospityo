<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>503 - Server Busy</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-lg w-full text-center">
            <div class="mb-8">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-yellow-100 rounded-full">
                    <i class="fas fa-hourglass-half text-yellow-600 text-5xl"></i>
                </div>
            </div>

            <h1 class="text-6xl font-bold text-gray-800 mb-4">503</h1>
            <h2 class="text-2xl font-semibold text-gray-700 mb-4">Server Temporarily Busy</h2>
            <p class="text-gray-600 mb-2">
                {{ $message ?? 'The server is under high load. Please wait a moment.' }}
            </p>
            <p class="text-gray-500 text-sm mb-8">
                This page will automatically refresh in <span id="countdown" class="font-semibold text-yellow-600">5</span> seconds.
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="window.location.reload()"
                        class="inline-flex items-center justify-center px-6 py-3 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="fas fa-redo mr-2"></i>
                    Retry Now
                </button>
            </div>

            <div class="mt-8 text-sm text-gray-500">
                <p>If this keeps happening, please contact technical support.</p>
            </div>
        </div>
    </div>

    <script>
        let seconds = 5;
        const el = document.getElementById('countdown');
        const timer = setInterval(() => {
            seconds--;
            el.textContent = seconds;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }, 1000);
    </script>
</body>
</html>
