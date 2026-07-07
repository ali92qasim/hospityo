@extends('admin.layout')

@section('title', 'Share Item Ledger')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Share Item Ledger</h1>
</div>

@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
        {{ session('success') }}
    </div>
@endif

@if($errors->has('error'))
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
        {{ $errors->first('error') }}
    </div>
@endif

{{-- Filter bar --}}
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" action="{{ route('doctor-share.items.index') }}" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Doctor</label>
            <select name="doctor_id" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All Doctors</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" {{ request('doctor_id') == $doctor->id ? 'selected' : '' }}>
                        {{ $doctor->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                <option value="">All</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="settled" {{ request('status') == 'settled' ? 'selected' : '' }}>Settled</option>
                <option value="voided" {{ request('status') == 'voided' ? 'selected' : '' }}>Voided</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-filter mr-1"></i>Filter
            </button>
            @if(request('doctor_id') || request('status') || request('date_from') || request('date_to'))
                <a href="{{ route('doctor-share.items.index') }}" class="text-gray-500 hover:text-gray-700 px-3 py-2">
                    Clear
                </a>
            @endif
        </div>
    </form>
</div>

{{-- Summary stats --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Revenue Generated</p>
                <p class="text-2xl font-bold text-gray-800 mt-1">
                    {{ format_currency($totalRevenue) }}
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-chart-line text-medical-blue text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Hospital Share</p>
                <p class="text-2xl font-bold text-indigo-600 mt-1">
                    {{ format_currency($totalHospitalShare) }}
                </p>
            </div>
            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                <i class="fas fa-hospital text-indigo-600 text-xl"></i>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Doctor Share</p>
                <p class="text-2xl font-bold text-green-600 mt-1">
                    {{ format_currency($totalDoctorShare) }}
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-user-md text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

{{-- Main table --}}
<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Base Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Share Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                </tr>
            </thead>
            @forelse($items as $item)
                {{-- Each row + detail panel wrapped in a tbody for Alpine.js scoping --}}
                <tbody x-data="{ open: false }" class="divide-y divide-gray-200">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->doctor->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $item->bill->bill_number ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($item->bill && $item->bill->bill_date)
                                {{ $item->bill->bill_date->format('M d, Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ currency_symbol() }}{{ number_format($item->base_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ currency_symbol() }}{{ number_format($item->share_amount, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ currency_symbol() }}{{ number_format($item->allocations_sum_amount ?? 0, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($item->status === 'pending')
                                <span class="text-xs px-2 py-1 rounded capitalize bg-yellow-100 text-yellow-800">{{ $item->status }}</span>
                            @elseif($item->status === 'settled')
                                <span class="text-xs px-2 py-1 rounded capitalize bg-green-100 text-green-800">{{ $item->status }}</span>
                            @elseif($item->status === 'voided')
                                <span class="text-xs px-2 py-1 rounded capitalize bg-red-100 text-red-800">{{ $item->status }}</span>
                            @else
                                <span class="text-xs px-2 py-1 rounded capitalize bg-gray-100 text-gray-800">{{ $item->status }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <button type="button" @click="open = !open"
                                    class="text-medical-blue hover:text-blue-700 flex items-center gap-1">
                                <i class="fas" :class="open ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </button>
                        </td>
                    </tr>
                    {{-- Expandable detail panel --}}
                    <tr x-show="open" x-transition>
                        <td colspan="8" class="bg-gray-50 px-6 py-4">
                            @if($item->status === 'voided')
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                                    <p class="text-sm font-medium text-yellow-800">
                                        <i class="fas fa-ban mr-1"></i>Voided
                                        @if($item->voided_at)
                                            on {{ $item->voided_at->format('M d, Y H:i') }}
                                        @endif
                                    </p>
                                    @if($item->void_reason)
                                        <p class="text-sm text-yellow-700 mt-1">Reason: {{ $item->void_reason }}</p>
                                    @endif
                                </div>
                            @endif

                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Payment Allocations</p>
                            @if($item->allocations && $item->allocations->count() > 0)
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            <th class="pb-2 pr-6">Payment Date</th>
                                            <th class="pb-2 pr-6">Payment Amount</th>
                                            <th class="pb-2">Allocated Share Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200">
                                        @foreach($item->allocations as $allocation)
                                        <tr>
                                            <td class="py-2 pr-6 text-gray-700">
                                                {{ $allocation->created_at->format('M d, Y') }}
                                            </td>
                                            <td class="py-2 pr-6 text-gray-700">
                                                {{ currency_symbol() }}{{ number_format($allocation->amount, 2) }}
                                            </td>
                                            <td class="py-2 text-gray-700">
                                                {{ currency_symbol() }}{{ number_format($allocation->amount, 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <p class="text-sm text-gray-500">No payment allocations recorded yet.</p>
                            @endif
                        </td>
                    </tr>
                </tbody>
                @empty
                <tbody>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            No share items found for the selected filters.
                        </td>
                    </tr>
                </tbody>
                @endforelse
        </table>
    </div>
    <div class="px-6 py-4">
        {{ $items->links() }}
    </div>
</div>
@endsection
