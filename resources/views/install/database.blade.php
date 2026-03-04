<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Hospityo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Database Configuration</h1>
            
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif
            
            <form action="{{ route('install.database.setup') }}" method="POST" class="space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium mb-2">Database Type</label>
                    <select name="db_connection" class="w-full px-3 py-2 border rounded-lg" onchange="toggleMysqlFields(this.value)">
                        <option value="sqlite">SQLite (Recommended)</option>
                        <option value="mysql">MySQL</option>
                    </select>
                </div>
                
                <div id="mysql-fields" class="hidden space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Host</label>
                        <input type="text" name="db_host" value="127.0.0.1" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Port</label>
                        <input type="text" name="db_port" value="3306" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Username</label>
                        <input type="text" name="db_username" value="root" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="db_password" class="w-full px-3 py-2 border rounded-lg">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">Database Name</label>
                    <input type="text" name="db_database" value="hospityo" class="w-full px-3 py-2 border rounded-lg" required>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
                    Setup Database <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function toggleMysqlFields(value) {
            document.getElementById('mysql-fields').classList.toggle('hidden', value !== 'mysql');
        }
    </script>
</body>
</html>