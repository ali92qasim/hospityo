@extends('super-admin.layout')
@section('title', 'Plans')
@section('page-title', 'Plans')
@section('page-description', 'Manage subscription plans and modules')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Subscription Plans</h1>
    <a href="{{ route('super-admin.plans.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
        <i class="fas fa-plus mr-2"></i> New Plan
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    @forelse($plans as $plan)
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden {{ !$plan->is_active ? 'opacity-60' : '' }}">
        <div class="p-5 sm:p-6">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">{{ $plan->name }}</h3>
                    <p class="text-xs text-gray-400">{{ $plan->slug }}</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <div class="flex items-baseline mb-3">
                <span class="text-2xl font-bold text-gray-900">{{ $plan->price > 0 ? currency_symbol('PKR') . ' ' . number_format($plan->price) : 'Free' }}</span>
                @if($plan->price > 0)
                <span class="ml-1 text-sm text-gray-500">/{{ $plan->billing_cycle }}</span>
                @endif
            </div>

            <p class="text-sm text-gray-500 mb-4">{{ $plan->description ?? 'No description' }}</p>

            <div class="mb-4">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">Modules ({{ count($plan->modules ?? []) }})</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($plan->modules ?? [] as $mod)
                    <span class="text-xs px-2 py-0.5 bg-blue-50 text-blue-700 rounded">{{ \App\Models\ModuleRegistry::nameFor($mod) }}</span>
                    @endforeach
                </div>
            </div>

            <div class="mb-4">
                <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Limits</p>
                <div class="text-xs text-gray-600 space-y-0.5">
                    <div>Users: {{ $plan->getLimit('max_users') ?? '∞' }}</div>
                    <div>Patients: {{ $plan->getLimit('max_patients') ?? '∞' }}</div>
                    <div>Doctors: {{ $plan->getLimit('max_doctors') ?? '∞' }}</div>
                </div>
            </div>

            <div class="text-xs text-gray-400 mb-4">{{ $plan->tenants_count }} hospital{{ $plan->tenants_count !== 1 ? 's' : '' }} on this plan</div>

            <div class="flex gap-2">
                <a href="{{ route('super-admin.plans.edit', $plan) }}" class="flex-1 text-center px-3 py-2 text-sm text-medical-blue border border-medical-blue rounded-lg hover:bg-blue-50 transition-colors">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                @if($plan->tenants_count === 0)
                <form method="POST" action="{{ route('super-admin.plans.destroy', $plan) }}" onsubmit="return confirm('Delete {{ $plan->name }}?')" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full px-3 py-2 text-sm text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                        <i class="fas fa-trash mr-1"></i> Delete
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="col-span-full text-center py-12 text-gray-400">
        <i class="fas fa-layer-group text-4xl mb-3"></i>
        <p>No plans created yet.</p>
    </div>
    @endforelse
</div>
@endsection
