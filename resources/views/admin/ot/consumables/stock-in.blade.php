@extends('admin.layout')

@section('title', 'Stock In — ' . $consumable->name)
@section('page-title', 'Stock In')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <p class="text-sm font-medium text-blue-800">{{ $consumable->name }}</p>
        <p class="text-xs text-blue-600">Current stock: {{ $consumable->current_stock }} {{ $consumable->unit }} · Category: {{ ucfirst($consumable->category) }}</p>
    </div>

    <form action="{{ route('ot.consumables.process-stock-in', $consumable) }}" method="POST">
        @csrf

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                    <input type="number" name="quantity" value="{{ old('quantity') }}" min="1" required
                        class="w-full border-gray-300 rounded-lg text-sm">
                    @error('quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                    <input type="number" name="unit_cost" value="{{ old('unit_cost', $consumable->unit_cost) }}" min="0" step="0.01"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Batch No.</label>
                    <input type="text" name="batch_no" value="{{ old('batch_no') }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                @if($consumable->requires_serial_tracking)
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Serial Number (Implant)</label>
                    <input type="text" name="serial_number" value="{{ old('serial_number') }}"
                        class="w-full border-gray-300 rounded-lg text-sm" placeholder="Implant serial for traceability">
                </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name', $consumable->supplier_name) }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PO / Reference No.</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.consumables.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-plus-circle mr-2"></i>Record Stock In
            </button>
        </div>
    </form>
</div>
@endsection
