@extends('admin.layout')

@section('title', 'Low Stock Alert - Inventory Management')
@section('page-title', 'Low Stock Alert')
@section('page-description', 'Medicines below reorder level')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-orange-600 text-xl mr-3"></i>
            <div>
                <p class="text-sm text-orange-600">Low Stock Items</p>
                <p class="text-2xl font-semibold text-orange-800">{{ $lowStockMedicines->count() }}</p>
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
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Stock</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($lowStockMedicines as $medicine)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $medicine->name }}</div>
                        <div class="text-xs text-gray-500">{{ $medicine->generic_name }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $medicine->getCurrentStockInUnit($medicine->dispensing_unit_id) }} {{ $medicine->dispensingUnit->abbreviation }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $medicine->reorder_level }} {{ $medicine->baseUnit->abbreviation }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php $currentStock = $medicine->getCurrentStock(); @endphp
                        @if($currentStock == 0)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Out of Stock</span>
                        @elseif($currentStock <= $medicine->reorder_level / 2)
                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">Critical</span>
                        @else
                            <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">Low Stock</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <a href="{{ route('inventory.stock-in') }}?medicine={{ $medicine->id }}" class="bg-medical-blue text-white px-3 py-1 rounded text-xs hover:bg-blue-700">
                            <i class="fas fa-plus mr-1"></i>Add Stock
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                        <div class="flex flex-col items-center py-8">
                            <i class="fas fa-check-circle text-4xl text-green-300 mb-4"></i>
                            <p class="text-lg font-medium text-gray-600">All medicines are well stocked!</p>
                            <p class="text-sm text-gray-500">No medicines below reorder level</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection