@extends('admin.layout')

@section('title', 'OT Consumables')
@section('page-title', 'OT Inventory & Consumables')
@section('page-description', 'Manage surgical instruments, implants, and disposables')

@section('content')
<div class="flex flex-wrap justify-between items-center mb-6 gap-3">
    <h1 class="text-2xl font-bold text-gray-800">OT Consumables</h1>
    <div class="flex gap-2">
        @if($lowStockCount > 0)
        <a href="{{ route('ot.consumables.reorder-alerts') }}" class="bg-red-100 text-red-700 px-4 py-2 rounded-lg hover:bg-red-200 text-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i>{{ $lowStockCount }} Low Stock
        </a>
        @endif
        <a href="{{ route('ot.consumables.create') }}" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
            <i class="fas fa-plus mr-2"></i>Add Consumable
        </a>
    </div>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
    <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
</div>
@endif

{{-- Filters --}}
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Category</label>
            <select name="category" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">All</option>
                @foreach(\App\Models\OtConsumable::CATEGORIES as $key => $label)
                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name..." class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} class="rounded text-red-500">
            Low Stock Only
        </label>
        <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-filter mr-1"></i>Filter
        </button>
        <a href="{{ route('ot.consumables.index') }}" class="text-gray-500 hover:text-gray-700 text-sm px-3 py-2">Clear</a>
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reorder Level</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit Cost</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($consumables as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm">
                        <div class="font-medium text-gray-900">{{ $item->name }}</div>
                        @if($item->sku) <div class="text-xs text-gray-400">SKU: {{ $item->sku }}</div> @endif
                        @if($item->is_reusable) <span class="text-xs text-blue-600"><i class="fas fa-sync mr-1"></i>Reusable</span> @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700 capitalize">{{ $item->category }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <span class="{{ $item->needsReorder() ? 'text-red-600 font-semibold' : 'text-gray-800' }}">
                            {{ $item->current_stock }} {{ $item->unit }}
                        </span>
                        @if($item->needsReorder())
                            <i class="fas fa-exclamation-circle text-red-500 ml-1" title="Below reorder level"></i>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $item->reorder_level }} {{ $item->unit }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ number_format($item->unit_cost, 2) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                        <a href="{{ route('ot.consumables.stock-in', $item) }}" class="text-green-600 hover:text-green-800" title="Stock In">
                            <i class="fas fa-plus-circle"></i>
                        </a>
                        <a href="{{ route('ot.consumables.edit', $item) }}" class="text-gray-500 hover:text-gray-700" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-boxes text-4xl text-gray-300 mb-3"></i>
                        <p>No consumables configured yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($consumables->hasPages())
    <div class="px-6 py-4 border-t border-gray-200">{{ $consumables->links() }}</div>
    @endif
</div>
@endsection
