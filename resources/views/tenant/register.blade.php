<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register Your Hospital — Hospityo</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen">

<nav class="bg-white/80 backdrop-blur-sm border-b border-gray-100">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-14">
            <a href="{{ url('/') }}" class="flex items-center space-x-2">
                <div class="h-8 w-8 bg-medical-blue rounded-lg flex items-center justify-center">
                    <i class="fas fa-hospital text-white text-xs"></i>
                </div>
                <span class="text-lg font-bold text-gray-900">Hospityo</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm text-gray-500 hover:text-medical-blue transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>Back to Home
            </a>
        </div>
    </div>
</nav>

<main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

    {{-- Header --}}
    <div class="text-center mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Create your hospital account</h1>
        <p class="mt-2 text-gray-500">Set up your management system in under 60 seconds.</p>
    </div>

    @if(session('error'))
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    <form method="POST" action="{{ route('tenant.register.store') }}" class="space-y-8">
        @csrf

        {{-- Section 1: Hospital Info --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex items-center mb-6">
                <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-hospital text-medical-blue"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Hospital Information</h2>
                    <p class="text-xs text-gray-500">Tell us about your healthcare facility</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="hospital_name" class="block text-sm font-medium text-gray-700 mb-1.5">Hospital / Clinic Name <span class="text-red-400">*</span></label>
                    <input type="text" id="hospital_name" name="hospital_name" value="{{ old('hospital_name') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                           placeholder="e.g. City General Hospital" required>
                    @error('hospital_name')<p class="mt-1.5 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="slug" class="block text-sm font-medium text-gray-700 mb-1.5">Subdomain</label>
                    <div class="flex">
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                               class="flex-1 px-4 py-3 border border-gray-300 rounded-l-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                               placeholder="city-general" pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]">
                        <span class="inline-flex items-center px-4 py-3 bg-gray-50 border border-l-0 border-gray-300 rounded-r-xl text-sm text-gray-500 whitespace-nowrap">
                            .{{ parse_url(config('app.url'), PHP_URL_HOST) }}
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">Leave blank to auto-generate from hospital name</p>
                    @error('slug')<p class="mt-1.5 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Hospital Email <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400 text-xs"></i>
                            </div>
                            <input type="email" id="email" name="email" value="{{ old('email') }}"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                                   placeholder="info@hospital.com" required>
                        </div>
                        @error('email')<p class="mt-1.5 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400 text-xs"></i>
                            </div>
                            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                                   placeholder="+92 300 1234567">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Plan Selection --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex items-center mb-6">
                <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-layer-group text-green-600"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Choose Your Plan</h2>
                    <p class="text-xs text-gray-500">All plans include a free trial period</p>
                </div>
            </div>

            <div class="space-y-3">
                @foreach($plans as $plan)
                <label class="group flex items-center p-4 border-2 rounded-xl cursor-pointer transition-all duration-200
                    {{ old('plan', 'starter') === $plan->slug ? 'border-medical-blue bg-blue-50/50 shadow-sm' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50/50' }}"
                    id="plan-label-{{ $plan->slug }}">
                    <input type="radio" name="plan" value="{{ $plan->slug }}"
                           class="text-medical-blue focus:ring-medical-blue h-4 w-4 flex-shrink-0"
                           {{ old('plan', 'starter') === $plan->slug ? 'checked' : '' }}
                           onchange="document.querySelectorAll('[id^=plan-label-]').forEach(l => { l.classList.remove('border-medical-blue','bg-blue-50/50','shadow-sm'); l.classList.add('border-gray-200'); }); this.closest('label').classList.remove('border-gray-200'); this.closest('label').classList.add('border-medical-blue','bg-blue-50/50','shadow-sm');">
                    <div class="flex-1 ml-4 min-w-0">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-900">{{ $plan->name }}</span>
                            <span class="text-sm font-bold {{ $plan->price > 0 ? 'text-medical-blue' : 'text-green-600' }}">
                                {{ $plan->price > 0 ? currency_symbol('PKR') . ' ' . number_format($plan->price) . '/mo' : 'Free' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $plan->description }}</p>
                        @if($plan->trial_days)
                        <span class="inline-flex items-center mt-2 text-xs text-medical-blue bg-blue-50 px-2 py-0.5 rounded-full">
                            <i class="fas fa-clock mr-1"></i>{{ $plan->trial_days }}-day free trial
                        </span>
                        @endif
                    </div>
                </label>
                @endforeach
            </div>
            @error('plan')<p class="mt-2 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
        </div>

        {{-- Section 3: Admin Account --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex items-center mb-6">
                <div class="w-9 h-9 bg-purple-50 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-user-shield text-purple-600"></i>
                </div>
                <div>
                    <h2 class="text-base font-semibold text-gray-900">Admin Account</h2>
                    <p class="text-xs text-gray-500">This will be the primary administrator</p>
                </div>
            </div>

            <div class="space-y-5">
                <div>
                    <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400 text-xs"></i>
                        </div>
                        <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                               placeholder="Dr. Ahmed Khan" required>
                    </div>
                    @error('admin_name')<p class="mt-1.5 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1.5">Admin Email <span class="text-red-400">*</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 text-xs"></i>
                        </div>
                        <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                               placeholder="admin@hospital.com" required>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-400">You'll use this email to log in to your dashboard</p>
                    @error('admin_email')<p class="mt-1.5 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1.5">Password <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 text-xs"></i>
                            </div>
                            <input type="password" id="admin_password" name="admin_password"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                                   placeholder="Min 8 characters" required>
                        </div>
                        @error('admin_password')<p class="mt-1.5 text-sm text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Confirm Password <span class="text-red-400">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 text-xs"></i>
                            </div>
                            <input type="password" id="admin_password_confirmation" name="admin_password_confirmation"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-medical-blue focus:border-transparent text-sm transition-shadow"
                                   placeholder="Repeat password" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Terms & Submit --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
            <div class="flex items-start mb-6">
                <input type="checkbox" name="terms" id="terms" class="h-5 w-5 text-medical-blue border-gray-300 rounded mt-0.5 flex-shrink-0" required>
                <label for="terms" class="ml-3 text-sm text-gray-600 leading-relaxed">
                    I agree to the <a href="{{ route('page.show', 'terms-and-conditions') }}" target="_blank" class="text-medical-blue hover:underline font-medium">Terms &amp; Conditions</a>
                    and <a href="{{ route('page.show', 'privacy-policy') }}" target="_blank" class="text-medical-blue hover:underline font-medium">Privacy Policy</a>
                </label>
            </div>

            <button type="submit"
                    class="w-full bg-medical-blue text-white py-3.5 px-6 rounded-xl hover:bg-blue-700 transition-colors flex items-center justify-center text-sm font-semibold shadow-lg shadow-blue-200/50">
                <i class="fas fa-rocket mr-2"></i>
                Create My Hospital
            </button>

            <p class="mt-5 text-center text-sm text-gray-500">
                Already have an account?
                <button type="button" onclick="document.getElementById('login-modal').classList.remove('hidden')" class="text-medical-blue hover:underline font-medium">Sign in</button>
            </p>
        </div>
    </form>
</main>

<footer class="py-6 text-center text-xs text-gray-400">
    &copy; {{ date('Y') }} Hospityo. All rights reserved.
</footer>

{{-- Inline Sign-In Modal --}}
<div id="login-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-black/50" onclick="document.getElementById('login-modal').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-sm w-full p-8 z-10">
            <button onclick="document.getElementById('login-modal').classList.add('hidden')" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            <div class="text-center mb-5">
                <h3 class="text-lg font-bold text-gray-900">Sign in to your hospital</h3>
                <p class="text-sm text-gray-500 mt-1">Enter your subdomain</p>
            </div>
            <form onsubmit="event.preventDefault(); var s=document.getElementById('modal-slug').value.trim().toLowerCase(); if(s){var h='{{ parse_url(config('app.url'), PHP_URL_HOST) }}'; window.location.href='{{ parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http' }}://'+s+'.'+h+'/login';}">
                <div class="flex mb-4">
                    <input type="text" id="modal-slug" class="flex-1 px-4 py-3 border border-gray-300 rounded-l-xl focus:ring-2 focus:ring-medical-blue text-sm" placeholder="your-hospital" required>
                    <span class="inline-flex items-center px-3 py-3 bg-gray-50 border border-l-0 border-gray-300 rounded-r-xl text-xs text-gray-500">.{{ parse_url(config('app.url'), PHP_URL_HOST) }}</span>
                </div>
                <button type="submit" class="w-full bg-medical-blue text-white py-3 rounded-xl hover:bg-blue-700 transition-colors text-sm font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>Continue to Login
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
