@extends('auth.layout')

@section('title', 'Register Your Hospital - Hospityo')
@section('subtitle', 'Set up your hospital management system in seconds')

@section('content')
<form method="POST" action="{{ route('tenant.register.store') }}">
    @csrf

    {{-- Hospital Info --}}
    <div class="mb-4">
        <label for="hospital_name" class="block text-sm font-medium text-gray-700 mb-1">Hospital / Clinic Name</label>
        <input type="text" id="hospital_name" name="hospital_name" value="{{ old('hospital_name') }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
               placeholder="e.g. City General Hospital" required>
        @error('hospital_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label for="slug" class="block text-sm font-medium text-gray-700 mb-1">Subdomain</label>
        <div class="flex items-center">
            <input type="text" id="slug" name="slug" value="{{ old('slug') }}"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                   placeholder="city-general" pattern="[a-z0-9][a-z0-9\-]*[a-z0-9]">
            <span class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-sm text-gray-500">
                .{{ parse_url(config('app.url'), PHP_URL_HOST) }}
            </span>
        </div>
        <p class="mt-1 text-xs text-gray-500">Leave blank to auto-generate from hospital name</p>
        @error('slug')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 xs:grid-cols-2 gap-3 mb-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Hospital Email</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                   required>
            @error('email')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone') }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
    </div>

    <hr class="my-5 border-gray-200">

    {{-- Plan Selection --}}
    <p class="text-sm font-semibold text-gray-700 mb-3">Choose Your Plan</p>
    <div class="space-y-2 mb-4">
        @foreach($plans as $plan)
        <label class="flex items-center p-3 border rounded-lg cursor-pointer transition-colors
            {{ old('plan', 'starter') === $plan->slug ? 'border-medical-blue bg-blue-50' : 'border-gray-200 hover:border-gray-300' }}">
            <input type="radio" name="plan" value="{{ $plan->slug }}"
                   class="text-medical-blue focus:ring-medical-blue mr-3 flex-shrink-0"
                   {{ old('plan', 'starter') === $plan->slug ? 'checked' : '' }}
                   onchange="document.querySelectorAll('[name=plan]').forEach(r => r.closest('label').className = r.closest('label').className.replace(/border-medical-blue bg-blue-50/, 'border-gray-200')); this.closest('label').classList.remove('border-gray-200'); this.closest('label').classList.add('border-medical-blue', 'bg-blue-50');">
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-900">{{ $plan->name }}</span>
                    <span class="text-sm font-semibold text-gray-700">
                        {{ $plan->price > 0 ? 'PKR ' . number_format($plan->price) . '/mo' : 'Free' }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-0.5">{{ $plan->description }}</p>
            </div>
        </label>
        @endforeach
    </div>
    @error('plan')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <hr class="my-5 border-gray-200">

    {{-- Admin Account --}}
    <p class="text-sm font-semibold text-gray-700 mb-3">Your Admin Account</p>

    <div class="mb-4">
        <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
        <input type="text" id="admin_name" name="admin_name" value="{{ old('admin_name') }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
               required>
        @error('admin_name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Admin Email</label>
        <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email') }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
               required>
        @error('admin_email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 xs:grid-cols-2 gap-3 mb-6">
        <div>
            <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
            <input type="password" id="admin_password" name="admin_password"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                   required>
            @error('admin_password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm</label>
            <input type="password" id="admin_password_confirmation" name="admin_password_confirmation"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                   required>
        </div>
    </div>

    <button type="submit"
            class="w-full bg-medical-blue text-white py-2.5 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
        <i class="fas fa-rocket mr-2"></i>
        Create My Hospital
    </button>

    <p class="mt-4 text-center text-sm text-gray-500">
        Already have an account? Sign in at your hospital's subdomain.
    </p>
</form>
@endsection
