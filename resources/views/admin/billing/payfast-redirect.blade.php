@extends('auth.layout')

@section('title', 'Redirecting to PayFast...')
@section('subtitle', 'Please wait while we redirect you to the payment gateway')

@section('content')
<div class="text-center py-8">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 mb-4">
        <i class="fas fa-spinner fa-spin text-medical-blue text-2xl"></i>
    </div>
    <p class="text-gray-600">Redirecting to PayFast...</p>
    <p class="text-sm text-gray-400 mt-2">If you are not redirected automatically, click the button below.</p>

    <form id="payfast-form" method="POST" action="{{ $checkout_url }}" class="mt-6">
        @foreach($payload as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-credit-card mr-2"></i>
            Proceed to Payment
        </button>
    </form>
</div>

<script>
    document.getElementById('payfast-form').submit();
</script>
@endsection
