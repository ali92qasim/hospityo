@extends('admin.layout')

@section('title', 'Medicine Expiry Report')

@section('content')
<div class="mb-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Medicine Expiry Report</h1>
            <p class="text-gray-600 mt-1">Track expired and expiring medicines</p>
        </div>
        <button onclick="window.print()" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
            <i class="fas fa-print mr-2"></i>Print Report
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-lg shadow p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Within (Days)</label>
            <select name="days" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="30" {{ $days == 30 ? 'selected' : '' }}>30 Days</option>
                <option value="60" {{ $days == 60 ? 'selected' : '' }}>60 Days</option>
                <option value="90" {{ $days == 90 ? 'selected' : '' }}>90 Days</option>
                <option value="180" {{ $days == 180 ? 'selected' : '' }}>180 Days</option>
                <option value="365" {{ $days == 365 ? 'selected' : '' }}>1 Year</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
            <select name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-medical-blue text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-search mr-2"></i>Generate
            </button>
        </div>
    </form>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Total Items</p>
                <p class="text-2xl font-bold text-gray-800">{{ $stats['total_items'] }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
                <i class="fas fa-boxes text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expired</p>
                <p class="text-2xl font-bold text-red-600">{{ $stats['expired'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $stats['total_quantity_expired'] }} units</p>
            </div>
            <div class="bg-red-100 rounded-full p-3">
                <i class="fas fa-times-circle text-red-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expiring (30 Days)</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $stats['expiring_30_days'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $stats['total_quantity_expiring'] }} units</p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-600">Expiring Later</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['expiring_later'] }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
                <i class="fas fa-clock text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Medicine-wise Expiry -->
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Medicine-wise Expiry Status</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Earliest Expiry</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expired Qty</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiring Soon</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($medicineExpiry as $item)
                @php
                    $daysUntilExpiry = now()->diffInDays($item['earliest_expiry'], false);
                    $isExpired = $daysUntilExpiry < 0;
                    $isExpiringSoon = $daysUntilExpiry >= 0 && $daysUntilExpiry <= 30;
                @endphp
                <tr class="{{ $isExpired ? 'bg-red-50' : ($isExpiringSoon ? 'bg-yellow-50' : '') }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $item['medicine']->name }}</div>
                        <div class="text-xs text-gray-500">{{ $item['medicine']->brand?->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $item['medicine']->category?->name ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($item['earliest_expiry'])->format('d M Y') }}
                        <div class="text-xs text-gray-500">
                            @if($isExpired)
                                Expired {{ abs($daysUntilExpiry) }} days ago
                            @else
                                {{ $daysUntilExpiry }} days left
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        {{ $item['expired_quantity'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                        {{ $item['expiring_quantity'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($isExpired)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Expired
                            </span>
                        @elseif($isExpiringSoon)
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Expiring Soon
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>OK
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No expiry data available for the selected period
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Category-wise Expiry -->
@if($categoryExpiry->count() > 0)
<div class="bg-white rounded-lg shadow mb-6">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Category-wise Expiry Status</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Batches</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expired</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiring Soon</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($categoryExpiry as $category)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                        {{ $category['category']->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $category['total'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                        {{ $category['expired'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-yellow-600">
                        {{ $category['expiring_soon'] }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<!-- Detailed Batch List -->
<div class="bg-white rounded-lg shadow">
    <div class="p-6 border-b border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800">Detailed Batch List</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch No</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                @php
                    $daysLeft = now()->diffInDays($transaction->expiry_date, false);
                    $isExpired = $daysLeft < 0;
                    $isExpiringSoon = $daysLeft >= 0 && $daysLeft <= 30;
                @endphp
                <tr class="{{ $isExpired ? 'bg-red-50' : ($isExpiringSoon ? 'bg-yellow-50' : '') }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $transaction->medicine->name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $transaction->batch_number ?? 'N/A' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $transaction->quantity }} {{ $transaction->medicine->baseUnit?->abbreviation }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ \Carbon\Carbon::parse($transaction->expiry_date)->format('d M Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($isExpired)
                            <span class="text-red-600 font-medium">Expired {{ abs($daysLeft) }} days ago</span>
                        @else
                            <span class="{{ $isExpiringSoon ? 'text-yellow-600 font-medium' : 'text-gray-600' }}">
                                {{ $daysLeft }} days
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($isExpired)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                <i class="fas fa-times-circle mr-1"></i>Expired
                            </span>
                        @elseif($isExpiringSoon)
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Expiring Soon
                            </span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>OK
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        No batches found
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
}
</style>
@endpush
@endsection
