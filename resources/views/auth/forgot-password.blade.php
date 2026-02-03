@extends('auth.layout')

@section('title', 'Forgot Password - Hospital Management System')
@section('subtitle', 'Reset your password')

@section('content')
<div class="text-center mb-6">
    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-key text-yellow-600 text-2xl"></i>
    </div>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Forgot your password?</h2>
    <p class="text-sm text-gray-600">No problem. Just let us know your email address and we will email you a password reset link.</p>
</div>

@if (session('status'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf
    
    <div class="mb-6">
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

    <button type="submit" class="w-full bg-medical-blue text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
        <i class="fas fa-paper-plane mr-2"></i>
        Email Password Reset Link
    </button>
</form>

<div class="mt-6 text-center">
    <a href="{{ route('login') }}" class="text-sm text-medical-blue hover:text-blue-700 flex items-center justify-center">
        <i class="fas fa-arrow-left mr-2"></i>
        Back to login
    </a>
</div>
@endsection