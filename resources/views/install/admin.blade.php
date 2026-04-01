<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Account - Hospityo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-6">Create Admin Account</h1>
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif
            
            <form action="{{ route('install.admin.setup') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium mb-2">Full Name</label>
                    <input type="text" name="name" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
                    Create Admin <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>
    </div>
</body>
</html>