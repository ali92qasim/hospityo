@extends('super-admin.layout')
@section('title', 'Dashboard')

@section('content')
<h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-4 sm:mb-6">Platform Overview</h1>

{{-- Stats Grid --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs sm:text-sm text-gray-500">Hospitals</span>
            <i class="fas fa-hospital text-blue-400"></i>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $stats['total_tenants'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs sm:text-sm text-gray-500">Active</span>
            <i class="fas fa-check-circle text-green-400"></i>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $stats['active_tenants'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs sm:text-sm text-gray-500">Suspended</span>
            <i class="fas fa-ban text-red-400"></i>
        </div>
        <div class="text-xl sm:text-2xl font-bold text-gray-900">{{ $stats['suspended'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-5">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs sm:text-sm text-gray-500 truncate">Monthly Revenue</span>
            <i class="fas fa-chart-line text-purple-400"></i>
        </div>
        <div class="text-lg sm:text-2xl font-bold text-gray-900 truncate">PKR {{ number_format($stats['monthly_revenue']) }}</div>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    {{-- Plan Distribution --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Plan Distribution</h2>
        <div class="space-y-3">
            @foreach($planDistribution as $plan)
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <span class="text-sm font-medium text-gray-700">{{ $plan->name }}</span>
                    <span class="text-xs text-gray-400">PKR {{ number_format($plan->price) }}/mo</span>
                </div>
                <span class="text-sm font-semibold text-gray-900">{{ $plan->tenants_count }} hospitals</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Recent Hospitals --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-gray-900">Recent Hospitals</h2>
            <a href="{{ route('super-admin.tenants.index') }}" class="text-sm text-medical-blue hover:underline">View all</a>
        </div>
        <div class="space-y-3">
            @forelse($recentTenants as $tenant)
            <div class="flex flex-col xs:flex-row xs:items-center justify-between gap-1">
                <div class="min-w-0">
                    <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="text-sm font-medium text-gray-700 hover:text-medical-blue truncate block">{{ $tenant->name }}</a>
                    <div class="text-xs text-gray-400 truncate">{{ $tenant->slug }}.{{ parse_url(config('app.url'), PHP_URL_HOST) }}</div>
                </div>
                <div class="flex items-center space-x-2 flex-shrink-0">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : ($tenant->status === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ ucfirst($tenant->status) }}
                    </span>
                    <span class="text-xs text-gray-400 hidden sm:inline">{{ $tenant->plan?->name ?? 'No plan' }}</span>
                </div>
            </div>
            @empty
            <p class="text-sm text-gray-400">No hospitals registered yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Recent Payments --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 mt-6">
    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Recent Payments</h2>
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="w-full text-sm min-w-[500px]">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2 px-4 sm:px-0 font-medium">Hospital</th>
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Amount</th>
                    <th class="pb-2 font-medium hidden sm:table-cell">Method</th>
                    <th class="pb-2 font-medium">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($recentPayments as $payment)
                <tr>
                    <td class="py-2.5 px-4 sm:px-0 text-gray-700">{{ $payment->tenant?->name ?? 'N/A' }}</td>
                    <td class="py-2.5 text-gray-500">{{ $payment->subscription?->plan?->name ?? 'N/A' }}</td>
                    <td class="py-2.5 font-medium text-gray-900 whitespace-nowrap">PKR {{ number_format($payment->amount) }}</td>
                    <td class="py-2.5 text-gray-500 hidden sm:table-cell">{{ ucfirst($payment->payment_method ?? '-') }}</td>
                    <td class="py-2.5 text-gray-400 whitespace-nowrap">{{ $payment->paid_at?->format('d M Y') ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-400">No payments yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
