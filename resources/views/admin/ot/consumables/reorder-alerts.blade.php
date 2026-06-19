@extends('admin.layout')

@section('title', 'OT Reorder Alerts')
@section('page-title', 'Reorder Alerts')
@section('page-description', 'OT consumables below reorder level')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">
        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Reorder Alerts
    </h1>
    <a href="{{ route('ot.consumables.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">
        <i class="fas fa-arrow-left mr-1"></i>Back to Inventory
    </a>
</div>

@if($lowStock->isEmpty())
<div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
    <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
    <p class="text-green-800 font-medium">All items are above reorder level.</p>
</div>
@else
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-red-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Item</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Current Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Reorder Level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Deficit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Supplier</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-red-700 uppercase">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($lowStock as $item)
                <tr class="hover:bg-red-25">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                        {{ $item->name }}
                        @if($item->sku) <span class="text-xs text-gray-400 ml-1">({{ $item->sku }})</span> @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-700 capitalize">{{ $item->category }}</td>
                    <td class="px-6 py-4 text-sm text-red-600 font-semibold">{{ $item->current_stock }} {{ $item->unit }}</td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->reorder_level }} {{ $item->unit }}</td>
                    <td class="px-6 py-4 text-sm text-red-700 font-bold">
                        -{{ $item->reorder_level - $item->current_stock }} {{ $item->unit }}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ $item->supplier_name ?? '—' }}</td>
                    <td class="px-6 py-4 text-sm">
                        <a href="{{ route('ot.consumables.stock-in', $item) }}" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700">
                            <i class="fas fa-plus mr-1"></i>Stock In
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
