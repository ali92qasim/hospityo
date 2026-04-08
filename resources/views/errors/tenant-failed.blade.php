@extends('auth.layout')
@section('title', 'Setup Failed')
@section('subtitle', 'There was a problem setting up this hospital')
@section('content')
<div class="text-center py-6">
    <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
        <i class="fas fa-exclamation-triangle text-red-500 text-2xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-800 mb-2">Setup Failed</h3>
    <p class="text-sm text-gray-500 mb-6">There was a problem setting up this hospital. Please contact support or try registering again.</p>
    <a href="{{ config('app.url') }}/register" class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
        <i class="fas fa-redo mr-2"></i> Try Again
    </a>
</div>
@endsection
