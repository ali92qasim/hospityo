<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sample Data - Hospityo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Sample Data</h1>
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif
            
            <form action="{{ route('install.seed.run') }}" method="POST" class="space-y-6">
                @csrf
                
                <div class="bg-blue-50 border border-blue-200 p-4 rounded-lg">
                    <label class="flex items-start cursor-pointer">
                        <input type="checkbox" name="seed_sample_data" value="1" class="mt-1 mr-3">
                        <div>
                            <div class="font-semibold">Install Sample Data</div>
                            <div class="text-sm text-gray-600 mt-1">
                                Includes: Hospital services, pharmaceutical units, and lab tests
                            </div>
                        </div>
                    </label>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
                    Continue <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>