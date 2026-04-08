@extends('auth.layout')
@section('title', 'Account Suspended')
@section('subtitle', 'This hospital account has been suspended')
@section('content')
<div class="text-center py-6">
    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-ban text-red-500 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Account Suspended</h3>
    <p class="text-sm text-gray-500 mb-6">This hospital's account has been suspended. Please contact support for assistance.</p>
    <a href="{{ config('app.url') }}" class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
        <i class="fas fa-arrow-left mr-2"></i> Back to Home
    </a>
</div>
@endsection
