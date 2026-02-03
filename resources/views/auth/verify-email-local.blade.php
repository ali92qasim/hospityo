@extends('auth.layout')

@section('title', 'Verify Email - Hospital Management System')
@section('subtitle', 'Verify your email address')

@section('content')
<div class="text-center mb-6">
    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-envelope-open text-blue-600 text-2xl"></i>
    </div>
    <h2 class="text-xl font-semibold text-gray-800 mb-2">Verify Your Email</h2>
    <p class="text-sm text-gray-600 mb-4">Thanks for signing up! Since you're in local development mode, you can verify your email instantly.</p>
    
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
            <p class="text-sm text-yellow-800">
                <strong>Local Development Mode:</strong> Email sending is disabled. Click the button below to verify instantly.
            </p>
        </div>
    </div>
</div>

@if (session('status') == 'verification-link-sent')
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        A new verification link has been sent to your email address.
    </div>
@endif

<div class="space-y-4">
    <form method="POST" action="{{ route('verification.local') }}">
        @csrf
        <button type="submit" class="w-full bg-green-500 text-white py-2 px-4 rounded-lg hover:bg-green-600 transition-colors flex items-center justify-center">
            <i class="fas fa-check-circle mr-2"></i>
            Verify Email Instantly (Local Only)
        </button>
    </form>

    <div class="text-center text-sm text-gray-500">
        <p>OR</p>
    </div>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="w-full bg-medical-blue text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors flex items-center justify-center">
            <i class="fas fa-paper-plane mr-2"></i>
            Send Verification Email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full bg-gray-500 text-white py-2 px-4 rounded-lg hover:bg-gray-600 transition-colors flex items-center justify-center">
            <i class="fas fa-sign-out-alt mr-2"></i>
            Log Out
        </button>
    </form>
</div>
@endsection