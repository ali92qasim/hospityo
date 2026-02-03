@extends('admin.layout')

@section('title', 'Expiring Stock - Inventory Management')
@section('page-title', 'Expiring Stock')
@section('page-description', 'Medicines expiring within 3 months')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-clock text-red-600 text-xl mr-3"></i>
            <div>
                <p class="text-sm text-red-600">Expiring Items</p>
                <p class="text-2xl font-semibold text-red-800">{{ $expiringStock->count() }}</p>
            </div>
        </div>
    </div>
    
    <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-800">
        <i class="fas fa-arrow-left mr-2"></i>Back to Inventory
    </a>
</div>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Medicine</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Batch</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Expiry Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Days Left</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($expiringStock as $stock)
                @php
                    $daysLeft = now()->diffInDays($stock->expiry_date, false);
                @endphp
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $stock->medicine->name }}</div>
                        <div class="text-xs text-gray-500">{{ $stock->medicine->generic_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $stock->batch_no ?: '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $stock->quantity }} {{ $stock->medicine->unit }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $stock->expiry_date->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $daysLeft }} days
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($daysLeft <= 30)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Critical</span>
                        @elseif($daysLeft <= 60)
                            <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">Warning</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Watch</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        @if($daysLeft <= 30)
                            <a href="{{ route('inventory.stock-out') }}?medicine={{ $stock->medicine->id }}" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700">
                                <i class="fas fa-trash mr-1"></i>Remove
                            </a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        <div class="flex flex-col items-center py-8">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-600">No expiring stock found!</p>
                            <p class="text-sm text-gray-500">All medicines have sufficient shelf life</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection