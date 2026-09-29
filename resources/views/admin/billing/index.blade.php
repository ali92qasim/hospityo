@extends('admin.layout')

@section('title', 'Plan Billing')
@section('page-title', 'Plan Billing')
@section('page-description', 'Manage PayFast subscription checkout for your hospital')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Current subscription</h3>
        @if($currentSubscription)
            <p class="text-sm text-gray-600">
                Status: <span class="font-medium">{{ $currentSubscription->status }}</span>
                · Amount: {{ format_currency($currentSubscription->amount) }}
                @if($currentSubscription->ends_at)
                    · Ends {{ $currentSubscription->ends_at->format('M d, Y') }}
                @endif
            </p>
        @else
            <p class="text-sm text-gray-500">No active subscription on this PayFast billing page.</p>
        @endif
        <p class="text-xs text-gray-400 mt-3">
            Prefer the main subscription page?
            <a href="{{ route('subscription.index') }}" class="text-medical-blue hover:underline">Open Subscription &amp; Billing</a>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Available plans</h3>
        <div class="space-y-3">
            @forelse($plans as $plan)
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border border-gray-100 rounded-lg p-4">
                    <div>
                        <div class="font-medium text-gray-900">{{ $plan->name }}</div>
                        <div class="text-sm text-gray-500">{{ format_currency($plan->price) }} / {{ $plan->billing_cycle }}</div>
                    </div>
                    <form method="POST" action="{{ route('billing.subscribe') }}">
                        @csrf
                        <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                        <button type="submit" class="px-4 py-2 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                            {{ $plan->price > 0 ? 'Subscribe' : 'Switch to free' }}
                        </button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">No active plans available.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
