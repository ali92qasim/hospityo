@extends('super-admin.layout')
@section('title', $tenant->name)

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 sm:mb-6 gap-2">
    <div>
        <a href="{{ route('super-admin.tenants.index') }}" class="text-sm text-gray-500 hover:text-gray-700"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mt-1">{{ $tenant->name }}</h1>
        <p class="text-xs sm:text-sm text-gray-500 truncate">{{ $tenant->domain }} &middot; Created {{ $tenant->created_at->format('d M Y') }}</p>
    </div>
    <span class="self-start sm:self-auto text-sm px-3 py-1 rounded-full flex-shrink-0 {{ $tenant->status === 'active' ? 'bg-green-100 text-green-700' : ($tenant->status === 'suspended' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
        {{ ucfirst($tenant->status) }}
    </span>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    {{-- Info Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Details</h2>
        <dl class="space-y-3 text-sm">
            <div><dt class="text-gray-400">Email</dt><dd class="text-gray-900 break-all">{{ $tenant->email ?? '-' }}</dd></div>
            <div><dt class="text-gray-400">Phone</dt><dd class="text-gray-900">{{ $tenant->phone ?? '-' }}</dd></div>
            <div><dt class="text-gray-400">Plan</dt><dd class="text-gray-900">{{ $tenant->plan?->name ?? 'No plan' }}</dd></div>
            <div><dt class="text-gray-400">Trial Ends</dt><dd class="text-gray-900">{{ $tenant->trial_ends_at?->format('d M Y') ?? 'N/A' }}</dd></div>
        </dl>
    </div>

    {{-- Usage Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Usage</h2>
        <div class="grid grid-cols-2 gap-4">
            @foreach([
                ['Users', $usage['users'], $tenant->getLimit('max_users')],
                ['Patients', $usage['patients'], $tenant->getLimit('max_patients')],
                ['Doctors', $usage['doctors'], $tenant->getLimit('max_doctors')],
                ['Visits', $usage['visits'], null],
                ['Appointments', $usage['appointments'], null],
                ['Bills', $usage['bills'], null],
            ] as [$label, $current, $limit])
            <div>
                <div class="text-xs text-gray-400">{{ $label }}</div>
                <div class="text-lg font-semibold text-gray-900">
                    {{ number_format($current) }}
                    @if($limit)<span class="text-xs text-gray-400 font-normal">/ {{ $limit === null ? '∞' : number_format($limit) }}</span>@endif
                </div>
            </div>
            @endforeach
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <div class="text-xs text-gray-400">Database Size</div>
            <div class="text-sm font-medium text-gray-700">{{ $usage['db_size'] }}</div>
        </div>
    </div>

    {{-- Actions Card --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">Actions</h2>
        <div class="space-y-3">
            {{-- Suspend / Activate --}}
            @if($tenant->status === 'active')
            <form method="POST" action="{{ route('super-admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspend {{ $tenant->name }}? Users will not be able to log in.')">
                @csrf
                <button type="submit" class="w-full px-4 py-2 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                    <i class="fas fa-ban mr-1"></i> Suspend Hospital
                </button>
            </form>
            @elseif($tenant->status === 'suspended')
            <form method="POST" action="{{ route('super-admin.tenants.activate', $tenant) }}">
                @csrf
                <button type="submit" class="w-full px-4 py-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                    <i class="fas fa-check-circle mr-1"></i> Activate Hospital
                </button>
            </form>
            @endif

            {{-- Change Plan --}}
            <form method="POST" action="{{ route('super-admin.tenants.change-plan', $tenant) }}">
                @csrf
                <label class="block text-xs text-gray-500 mb-1">Change Plan</label>
                <div class="flex flex-col xs:flex-row gap-2">
                    <select name="plan_id" class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @foreach(\App\Models\Plan::orderBy('sort_order')->get() as $plan)
                        <option value="{{ $plan->id }}" {{ $tenant->plan_id == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} (PKR {{ number_format($plan->price) }})
                        </option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 text-sm bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors whitespace-nowrap">Apply</button>
                </div>
            </form>

            {{-- Visit Tenant --}}
            <a href="http://{{ $tenant->domain }}" target="_blank"
               class="block w-full px-4 py-2 text-sm text-center text-gray-700 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-external-link-alt mr-1"></i> Visit Hospital Portal
            </a>
        </div>
    </div>
</div>

{{-- Subscription History --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6 mt-4 sm:mt-6">
    <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Subscription History</h2>
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="w-full text-sm min-w-[520px]">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="pb-2 font-medium">Plan</th>
                    <th class="pb-2 font-medium">Amount</th>
                    <th class="pb-2 font-medium">Status</th>
                    <th class="pb-2 font-medium">Period</th>
                    <th class="pb-2 font-medium">Payments</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tenant->subscriptions->sortByDesc('created_at') as $sub)
                <tr>
                    <td class="py-2.5 text-gray-700">{{ $sub->plan?->name ?? 'N/A' }}</td>
                    <td class="py-2.5 font-medium">PKR {{ number_format($sub->amount) }}</td>
                    <td class="py-2.5">
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $sub->status === 'active' ? 'bg-green-100 text-green-700' : ($sub->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                            {{ ucfirst($sub->status) }}
                        </span>
                    </td>
                    <td class="py-2.5 text-gray-400">{{ $sub->starts_at?->format('d M Y') ?? '-' }} — {{ $sub->ends_at?->format('d M Y') ?? 'Ongoing' }}</td>
                    <td class="py-2.5 text-gray-500">{{ $sub->payments->where('status', 'success')->count() }} paid</td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-4 text-center text-gray-400">No subscriptions.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
