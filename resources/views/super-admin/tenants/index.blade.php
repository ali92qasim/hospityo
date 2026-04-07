@extends('super-admin.layout')
@section('title', 'All Hospitals')
@section('page-title', 'Hospitals')
@section('page-description', 'Manage all registered hospitals')

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-4 sm:mb-6 gap-2">
    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Hospitals</h1>
    <span class="text-sm text-gray-500">{{ $tenants->total() }} total</span>
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-200 p-3 sm:p-4 mb-4 sm:mb-6">
    <form method="GET" class="flex flex-col sm:flex-row sm:flex-wrap sm:items-end gap-3">
        <div class="w-full sm:flex-1 sm:min-w-[200px]">
            <label class="block text-xs text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, slug, or email..."
                   class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div class="w-full xs:w-auto">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" class="w-full xs:w-auto px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All</option>
                @foreach(['active', 'suspended', 'provisioning', 'failed'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full xs:w-auto">
            <label class="block text-xs text-gray-500 mb-1">Plan</label>
            <select name="plan_id" class="w-full xs:w-auto px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Plans</option>
                @foreach($plans as $plan)
                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">Filter</button>
        @if(request()->hasAny(['search', 'status', 'plan_id']))
        <a href="{{ route('super-admin.tenants.index') }}" class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead class="bg-gray-50">
                <tr class="text-left text-gray-500">
                    <th class="px-4 py-3 font-medium">Hospital</th>
                    <th class="px-4 py-3 font-medium">Subdomain</th>
                    <th class="px-4 py-3 font-medium">Plan</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Created</th>
                    <th class="px-4 py-3 font-medium">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tenants as $tenant)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="font-medium text-gray-900 hover:text-medical-blue">{{ $tenant->name }}</a>
                        <div class="text-xs text-gray-400">{{ $tenant->email }}</div>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $tenant->slug }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">{{ $tenant->plan?->name ?? 'None' }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : ($tenant->status === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-400">{{ $tenant->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="text-medical-blue hover:underline text-xs">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">No hospitals found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($tenants->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $tenants->links() }}
    </div>
    @endif
</div>
@endsection
