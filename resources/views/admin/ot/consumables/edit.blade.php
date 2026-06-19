@extends('admin.layout')

@section('title', 'Edit — ' . $consumable->name)
@section('page-title', 'Edit Consumable')

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('ot.consumables.update', $consumable) }}" method="POST">
        @csrf @method('PUT')

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $consumable->name) }}" required
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $consumable->sku) }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                    <select name="category" required class="w-full border-gray-300 rounded-lg text-sm">
                        @foreach(\App\Models\OtConsumable::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $consumable->category) == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                    <input type="text" name="unit" value="{{ old('unit', $consumable->unit) }}" required
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reorder Level *</label>
                    <input type="number" name="reorder_level" value="{{ old('reorder_level', $consumable->reorder_level) }}" min="0" required
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Unit Cost</label>
                    <input type="number" name="unit_cost" value="{{ old('unit_cost', $consumable->unit_cost) }}" min="0" step="0.01"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Supplier</label>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name', $consumable->supplier_name) }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                </div>
                <div class="md:col-span-2 flex gap-6">
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_reusable" value="0">
                        <input type="checkbox" name="is_reusable" value="1" {{ old('is_reusable', $consumable->is_reusable) ? 'checked' : '' }} class="rounded text-medical-blue">
                        Reusable
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="requires_serial_tracking" value="0">
                        <input type="checkbox" name="requires_serial_tracking" value="1" {{ old('requires_serial_tracking', $consumable->requires_serial_tracking) ? 'checked' : '' }} class="rounded text-medical-blue">
                        Serial Tracking
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $consumable->is_active) ? 'checked' : '' }} class="rounded text-medical-blue">
                        Active
                    </label>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('notes', $consumable->notes) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.consumables.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-save mr-2"></i>Update
            </button>
        </div>
    </form>
</div>
@endsection
