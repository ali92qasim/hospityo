@extends('admin.layout')

@section('title', 'Add Medicine')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Add Medicine</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('medicines.store') }}">
        @csrf
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Medicine Name</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="generic_name" class="block text-sm font-medium text-gray-700 mb-2">Generic Name</label>
                <input type="text" id="generic_name" name="generic_name" value="{{ old('generic_name') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('generic_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="brand" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                <input type="text" id="brand" name="brand" value="{{ old('brand') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('brand')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <input type="text" id="category" name="category" value="{{ old('category') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('category')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="dosage_form" class="block text-sm font-medium text-gray-700 mb-2">Dosage Form</label>
                <select id="dosage_form" name="dosage_form" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Form</option>
                    <option value="tablet" {{ old('dosage_form') == 'tablet' ? 'selected' : '' }}>Tablet</option>
                    <option value="capsule" {{ old('dosage_form') == 'capsule' ? 'selected' : '' }}>Capsule</option>
                    <option value="syrup" {{ old('dosage_form') == 'syrup' ? 'selected' : '' }}>Syrup</option>
                    <option value="injection" {{ old('dosage_form') == 'injection' ? 'selected' : '' }}>Injection</option>
                    <option value="cream" {{ old('dosage_form') == 'cream' ? 'selected' : '' }}>Cream</option>
                </select>
                @error('dosage_form')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="strength" class="block text-sm font-medium text-gray-700 mb-2">Strength</label>
                <input type="text" id="strength" name="strength" value="{{ old('strength') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('strength')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="unit_price" class="block text-sm font-medium text-gray-700 mb-2">Unit Price (₨)</label>
                <input type="number" id="unit_price" name="unit_price" value="{{ old('unit_price') }}" min="0" step="0.01"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('unit_price')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity</label>
                <input type="number" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity') }}" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('stock_quantity')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                <input type="number" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', 10) }}" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('reorder_level')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="expiry_date" class="block text-sm font-medium text-gray-700 mb-2">Expiry Date</label>
                <input type="date" id="expiry_date" name="expiry_date" value="{{ old('expiry_date') }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('expiry_date')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="batch_number" class="block text-sm font-medium text-gray-700 mb-2">Batch Number</label>
                <input type="text" id="batch_number" name="batch_number" value="{{ old('batch_number') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('batch_number')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                <input type="text" id="manufacturer" name="manufacturer" value="{{ old('manufacturer') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('manufacturer')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('medicines.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-save mr-2"></i>Save Medicine
            </button>
        </div>
    </form>
</div>
@endsection