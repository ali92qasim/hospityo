@extends('auth.layout')

@section('title', 'Super Admin Login')
@section('subtitle', 'Platform administration')

@section('content')
<form method="POST" action="{{ route('super-admin.login') }}">
    @csrf
    <div class="mb-4">
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required autofocus>
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="mb-4">
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <input type="password" id="password" name="password"
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
    </div>
    <label class="flex items-center mb-6">
        <input type="checkbox" name="remember" class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue">
        <span class="ml-2 text-sm text-gray-600">Remember me</span>
    </label>
    <button type="submit" class="w-full bg-gray-900 text-white py-2.5 px-4 rounded-lg hover:bg-gray-800 transition-colors">
        <i class="fas fa-shield-alt mr-2"></i> Sign In
    </button>
</form>
@endsection
