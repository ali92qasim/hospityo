@extends('admin.layout')

@section('title', 'Settlement Batch')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Settlement Batch #{{ $settlement->id }}</h1>
    <a href="{{ route('doctor-share.settlements.index') }}" class="text-medical-blue hover:text-blue-700 flex items-center">
        <i class="fas fa-arrow-left mr-2"></i>Back to Settlements
    </a>
</div>

<div class="bg-white rounded-lg shadow p-6 mb-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Doctor Scope</p>
            <p class="text-sm text-gray-900">{{ $settlement->doctor->name ?? 'All Doctors' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Date Range</p>
            <p class="text-sm text-gray-900">
                {{ $settlement->date_from->format('M d, Y') }} &ndash; {{ $settlement->date_to->format('M d, Y') }}
            </p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Item Count</p>
            <p class="text-sm text-gray-900">{{ $settlement->item_count }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Settled</p>
            <p class="text-sm text-gray-900">{{ currency_symbol() }}{{ number_format($settlement->total_settled_amount, 2) }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Created By</p>
            <p class="text-sm text-gray-900">{{ $settlement->createdBy->name ?? '—' }}</p>
        </div>
        <div>
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Created At</p>
            <p class="text-sm text-gray-900">{{ $settlement->created_at->format('M d, Y H:i') }}</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Share Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collected at Settlement</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($settlement->shareItems as $item)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->doctor->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $item->bill->bill_number ?? '—' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($item->share_amount, 2) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ currency_symbol() }}{{ number_format($item->collected_at_settlement ?? 0, 2) }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                        No items in this settlement.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
