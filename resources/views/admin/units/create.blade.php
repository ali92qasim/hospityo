@extends('admin.layout')

@section('title', 'Add Unit - Hospital Management System')
@section('page-title', 'Add Unit')
@section('page-description', 'Create new medicine unit')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Unit Information</h3>
                <a href="{{ route('units.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Units
                </a>
            </div>
        </div>

        <form action="{{ route('units.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Abbreviation *</label>
                    <input type="text" name="abbreviation" value="{{ old('abbreviation') }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('abbreviation')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Base Unit</label>
                    <select name="base_unit_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <option value="">None (This is a base unit)</option>
                        @foreach($baseUnits as $baseUnit)
                            <option value="{{ $baseUnit->id }}" {{ old('base_unit_id') == $baseUnit->id ? 'selected' : '' }}>
                                {{ $baseUnit->name }} ({{ $baseUnit->abbreviation }})
                            </option>
                        @endforeach
                    </select>
                    @error('base_unit_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Conversion Factor *</label>
                    <input type="number" name="conversion_factor" step="0.0001" min="0.0001" value="{{ old('conversion_factor', 1) }}" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    <p class="text-xs text-gray-500 mt-1">How many base units equal 1 of this unit</p>
                    @error('conversion_factor')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                    <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="solid" {{ old('type') == 'solid' ? 'selected' : '' }}>Solid</option>
                        <option value="liquid" {{ old('type') == 'liquid' ? 'selected' : '' }}>Liquid</option>
                        <option value="gas" {{ old('type') == 'gas' ? 'selected' : '' }}>Gas</option>
                        <option value="packaging" {{ old('type') == 'packaging' ? 'selected' : '' }}>Packaging</option>
                    </select>
                    @error('type')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                           class="h-4 w-4 text-medical-blue focus:ring-medical-blue border-gray-300 rounded">
                    <label class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('units.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-save mr-2"></i>Save Unit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection