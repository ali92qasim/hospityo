<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requirements Check - Hospityo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">System Requirements</h1>
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3">PHP Extensions</h2>
                @foreach($requirements as $name => $met)
                    <div class="flex items-center justify-between py-2 border-b">
                        <span>{{ $name }}</span>
                        @if($met)
                            <i class="fas fa-check-circle text-green-500"></i>
                        @else
                            <i class="fas fa-times-circle text-red-500"></i>
                        @endif
                    </div>
                @endforeach
            </div>
            
            <div class="mb-6">
                <h2 class="text-lg font-semibold mb-3">Directory Permissions</h2>
                @foreach($permissions as $path => $writable)
                    <div class="flex items-center justify-between py-2 border-b">
                        <span>{{ $path }}</span>
                        @if($writable)
                            <i class="fas fa-check-circle text-green-500"></i>
                        @else
                            <i class="fas fa-times-circle text-red-500"></i>
                        @endif
                    </div>
                @endforeach
            </div>
            
            @if(collect($requirements)->every(fn($v) => $v) && collect($permissions)->every(fn($v) => $v))
                <a href="{{ route('install.database') }}" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 transition">
                    Continue <i class="fas fa-arrow-right ml-2"></i>
                </a>
            @else
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    Please fix the issues above before continuing.
                </div>
            @endif
        </div>
    </div>
</body>
</html>