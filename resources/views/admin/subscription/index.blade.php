@extends('admin.layout')

@section('title', 'Subscription & Billing')
@section('page-title', 'Subscription & Billing')
@section('page-description', 'Manage your hospital subscription plan')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Current Plan --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Current Plan</h3>
        </div>
        <div class="p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl font-bold text-gray-900">{{ $tenant->plan?->name ?? 'No Plan' }}</span>
                        @if($tenant->onTrial())
                            <span class="px-2.5 py-1 text-xs rounded-full bg-amber-100 text-amber-700 font-medium">
                                <i class="fas fa-clock mr-1"></i>Trial — {{ $tenant->trialDaysRemaining() }} days left
                            </span>
                        @elseif($currentSubscription?->isActive())
                            <span class="px-2.5 py-1 text-xs rounded-full bg-green-100 text-green-700 font-medium">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        @else
                            <span class="px-2.5 py-1 text-xs rounded-full bg-red-100 text-red-700 font-medium">
                                <i class="fas fa-exclamation-circle mr-1"></i>Inactive
                            </span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-500 mt-1">{{ $tenant->plan?->description ?? 'No active subscription' }}</p>
                    @if($currentSubscription)
                        <p class="text-xs text-gray-400 mt-2">
                            Billing: {{ format_currency($currentSubscription->amount) }}/{{ $tenant->plan?->billing_cycle ?? 'month' }}
                            @if($currentSubscription->ends_at)
                                • Renews {{ $currentSubscription->ends_at->format('M d, Y') }}
                            @endif
                        </p>
                    @endif
                </div>
                @if($currentSubscription?->gateway === 'paddle' && $currentSubscription?->gateway_subscription_id)
                    <div class="flex gap-2">
                        <button onclick="cancelSubscription()" class="px-4 py-2 text-sm border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Available Plans --}}
    <h3 class="text-lg font-semibold text-gray-800 mb-4">
        {{ $currentSubscription ? 'Change Plan' : 'Choose a Plan' }}
    </h3>

    <div class="grid md:grid-cols-3 gap-6 mb-8">
        @foreach($plans as $plan)
        @php
            $hasPaidSubscription = $currentSubscription && $currentSubscription->isActive() && $currentSubscription->gateway !== 'manual';
            $isCurrent = $hasPaidSubscription && $currentSubscription->plan_id === $plan->id;
            $canCheckout = $gateway && $plan->paddle_price_id && !$isCurrent && $plan->price > 0;
        @endphp
        <div class="bg-white rounded-xl shadow-sm border-2 {{ $isCurrent ? 'border-medical-blue' : ($tenant->plan_id === $plan->id ? 'border-amber-300' : 'border-gray-200') }} relative">
            @if($isCurrent)
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-medical-blue text-white text-xs font-medium rounded-full">Active Subscription</div>
            @elseif($tenant->plan_id === $plan->id && $tenant->onTrial())
                <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 bg-amber-500 text-white text-xs font-medium rounded-full">Trial Plan</div>
            @endif
            <div class="p-6">
                <h4 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h4>
                <div class="mt-3 flex items-baseline">
                    <span class="text-3xl font-bold text-gray-900">{{ $plan->price > 0 ? format_currency($plan->price) : 'Free' }}</span>
                    @if($plan->price > 0)
                        <span class="ml-1 text-sm text-gray-500">/{{ $plan->billing_cycle }}</span>
                    @endif
                </div>
                <p class="text-sm text-gray-500 mt-2">{{ $plan->description }}</p>

                @if($plan->trial_days && !$currentSubscription)
                    <p class="text-xs text-medical-blue mt-2"><i class="fas fa-clock mr-1"></i>{{ $plan->trial_days }}-day free trial</p>
                @endif

                <ul class="mt-4 space-y-2">
                    @foreach($plan->modules ?? [] as $module)
                    <li class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-check text-green-500 mr-2 text-xs"></i>{{ ucfirst($module) }}
                    </li>
                    @endforeach
                </ul>

                <div class="mt-6">
                    @if($isCurrent)
                        <button disabled class="w-full py-2.5 px-4 bg-gray-100 text-gray-500 rounded-lg text-sm font-medium cursor-not-allowed">
                            Active Subscription
                        </button>
                    @elseif($plan->price == 0)
                        <button disabled class="w-full py-2.5 px-4 bg-gray-100 text-gray-500 rounded-lg text-sm font-medium cursor-not-allowed">
                            Free Plan
                        </button>
                    @elseif(!$gateway || !$gateway->is_enabled)
                        <button disabled class="w-full py-2.5 px-4 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-sm font-medium cursor-not-allowed">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Payment gateway not enabled
                        </button>
                    @elseif(!$plan->paddle_price_id)
                        <button disabled class="w-full py-2.5 px-4 bg-yellow-50 text-yellow-700 border border-yellow-200 rounded-lg text-sm font-medium cursor-not-allowed">
                            <i class="fas fa-exclamation-triangle mr-1"></i>Price not configured
                        </button>
                    @else
                        <button onclick="openCheckout('{{ $plan->paddle_price_id }}', '{{ $plan->name }}')"
                                class="w-full py-2.5 px-4 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                            <i class="fas fa-credit-card mr-2"></i>
                            @if($tenant->onTrial() && $tenant->plan_id === $plan->id)
                                Subscribe Now
                            @elseif($hasPaidSubscription)
                                {{ $plan->price > ($currentSubscription->plan?->price ?? 0) ? 'Upgrade' : 'Switch Plan' }}
                            @else
                                Subscribe
                            @endif
                        </button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Payment History --}}
    @if($currentSubscription && $currentSubscription->payments->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800">Payment History</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($currentSubscription->payments()->latest()->get() as $payment)
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-700">{{ $payment->paid_at?->format('M d, Y h:i A') ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ format_currency($payment->amount) }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ ucfirst($payment->payment_method) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full {{ $payment->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

@if($gateway && $gateway->is_enabled && $gateway->slug === 'paddle')
<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script>
    // Initialize Paddle
    @if($gateway->isSandbox())
        Paddle.Environment.set('sandbox');
    @endif
    Paddle.Initialize({
        token: '{{ $gateway->getCredential("client_side_token") }}',
        eventCallback: function(event) {
            if (event.name === 'checkout.completed') {
                // Send transaction data to our server to activate subscription
                var data = event.data;
                fetch('{{ route("subscription.activate") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        transaction_id: data.transaction_id,
                        subscription_id: data.subscription_id || null,
                        customer_id: data.customer?.id || null,
                        status: data.status
                    })
                })
                .then(function(r) { return r.json(); })
                .then(function(result) {
                    if (result.success) {
                        window.location.href = '{{ route("subscription.index") }}?success=1';
                    } else {
                        window.location.href = '{{ route("subscription.index") }}?success=1&pending=1';
                    }
                })
                .catch(function() {
                    window.location.href = '{{ route("subscription.index") }}?success=1&pending=1';
                });
            }
        }
    });

    function openCheckout(priceId, planName) {
        Paddle.Checkout.open({
            items: [{ priceId: priceId, quantity: 1 }],
            customData: {
                tenant_id: '{{ $tenant->id }}',
                tenant_slug: '{{ $tenant->slug }}'
            },
            customer: {
                email: '{{ $tenant->email }}'
            },
            settings: {
                displayMode: 'overlay',
                theme: 'light',
                successUrl: '{{ route("subscription.index") }}?success=1'
            }
        });
    }

    function cancelSubscription() {
        if (!confirm('Are you sure you want to cancel your subscription? You will retain access until the end of the current billing period.')) return;
        // For now, redirect to contact — full cancellation via API can be added later
        alert('Please contact support to cancel your subscription. Email: billing@hospityo.com');
    }
</script>
@endif

@if(request('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show success notification
        var div = document.createElement('div');
        div.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50';
        div.innerHTML = '<div class="font-bold">Success!</div><div>Your subscription is being processed. It may take a moment to activate.</div>';
        document.body.appendChild(div);
        setTimeout(function() { div.remove(); }, 5000);
    });
</script>
@endif
@endsection
