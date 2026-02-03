@extends('auth.layout')

@section('title', 'Confirm Password - Hospital Management System')
@section('subtitle', 'Please confirm your password to continue')

@section('content')
<div class="text-center mb-6">
    <div class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-shield-alt text-orange-600 text-2xl"></i>
    </div>
    <p class="text-sm text-gray-600">This is a secure area of the application. Please confirm your password before continuing.</p>
</div>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    
    <div class="mb-6">
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

    <button type="submit" class="w-full bg-medical-blue text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
        <i class="fas fa-check mr-2"></i>
        Confirm
    </button>
</form>
@endsection