@extends('auth.layout')

@section('title', 'Register - Hospityo')
@section('subtitle', 'Create your account')

@section('content')
<form method="POST" action="{{ route('register') }}">
    @csrf
    
    <div class="mb-4">
        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-user text-gray-400"></i>
            </div>
            <input type="text" id="name" name="name" value="{{ old('name') }}" 
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                   placeholder="Enter your full name" required>
        </div>
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-envelope text-gray-400"></i>
            </div>
            <input type="email" id="email" name="email" value="{{ old('email') }}" 
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                   placeholder="Enter your email" required>
        </div>
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input type="password" id="password" name="password" 
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                   placeholder="Enter your password" required>
        </div>
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="mb-6">
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <i class="fas fa-lock text-gray-400"></i>
            </div>
            <input type="password" id="password_confirmation" name="password_confirmation" 
                   class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                   placeholder="Confirm your password" required>
        </div>
    </div>

    <button type="submit" class="w-full bg-medical-blue text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
        <i class="fas fa-user-plus mr-2"></i>
        Create Account
    </button>
</form>

<div class="mt-6 text-center">
    <p class="text-sm text-gray-600">
        Already have an account? 
        <a href="{{ route('login') }}" class="text-medical-blue hover:text-blue-700 font-medium">Sign in</a>
    </p>
</div>
@endsection