<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Hospityo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="max-w-2xl w-full bg-white rounded-lg shadow-lg p-8">
            <div class="text-center mb-8">
                <i class="fas fa-hospital text-6xl text-blue-600 mb-4"></i>
                <h1 class="text-3xl font-bold text-gray-900">Welcome to Hospityo</h1>
                <p class="text-gray-600 mt-2">Hospital Management System Installation Wizard</p>
            </div>
            
            <div class="space-y-4 mb-8">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <h3 class="font-semibold">Complete Hospital Management</h3>
                        <p class="text-sm text-gray-600">Patient records, appointments, billing, and more</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <h3 class="font-semibold">Laboratory Information System</h3>
                        <p class="text-sm text-gray-600">Manage lab tests, results, and reports</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 mt-1 mr-3"></i>
                    <div>
                        <h3 class="font-semibold">Pharmacy & Inventory</h3>
                        <p class="text-sm text-gray-600">Track medicines, stock, and purchases</p>
                    </div>
                </div>
            </div>
            
            <a href="{{ route('install.requirements') }}" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg hover:bg-blue-700 transition">
                Get Started <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</body>
</html>