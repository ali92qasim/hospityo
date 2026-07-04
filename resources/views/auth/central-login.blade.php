<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — UseClinicSync</title>
    @vite(['resources/css/app.css'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen flex flex-col">

<nav class="bg-white/80 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-md mx-auto px-4 sm:px-6">
        <div class="flex items-center justify-between h-14">
            <a href="{{ url('/') }}" class="flex items-center">
                @include('partials.logo', ['size' => 'sm'])
            </a>
            <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-medical-blue">
                <i class="fas fa-arrow-left mr-1"></i>Home
            </a>
        </div>
    </div>
</nav>

<main class="flex-1 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-medical-blue rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-sign-in-alt text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
                <p class="text-sm text-gray-500 mt-1">Sign in to access your hospital dashboard</p>
            </div>

            @if(request('welcome'))
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">
                <i class="fas fa-check-circle mr-2"></i>Your hospital is ready! Sign in with the email and password you used during registration.
            </div>
            @endif

            @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                <i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ url('/signin') }}">
                @csrf

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400 text-xs"></i>
                            </div>
                            <input type="email" name="email" value="{{ old('email') }}"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                                   placeholder="you@example.com" required autofocus>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 text-xs"></i>
                            </div>
                            <input type="password" name="password"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm"
                                   placeholder="Your password" required>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full mt-6 bg-medical-blue text-white py-3 rounded-xl hover:bg-blue-700 transition-colors text-sm font-semibold shadow-lg shadow-blue-200/50">
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-500">
                Don't have an account?
                <a href="{{ route('tenant.register') }}" class="text-medical-blue hover:underline font-medium">Register your hospital</a>
            </p>
        </div>
    </div>
</main>

<footer class="py-4 text-center text-xs text-gray-400">
    &copy; {{ date('Y') }} UseClinicSync. All rights reserved.
</footer>

</body>
</html>
