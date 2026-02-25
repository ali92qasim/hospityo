@extends('admin.layout')

@section('title', 'Edit Medicine')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Edit Medicine</h1>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <form method="POST" action="{{ route('medicines.update', $medicine) }}">
        @csrf @method('PUT')
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Medicine Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $medicine->name) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="generic_name" class="block text-sm font-medium text-gray-700 mb-2">Generic Name</label>
                <input type="text" id="generic_name" name="generic_name" value="{{ old('generic_name', $medicine->generic_name) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('generic_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                <select id="brand_id" name="brand_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue select2-brand">
                    <option value="">Select Brand</option>
                    @foreach(\App\Models\MedicineBrand::active()->orderBy('name')->get() as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id', $medicine->brand_id) == $brand->id ? 'selected' : '' }}>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
                @error('brand_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select id="category_id" name="category_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue select2-category">
                    <option value="">Select Category</option>
                    @foreach(\App\Models\MedicineCategory::active()->orderBy('name')->get() as $category)
                        <option value="{{ $category->id }}" {{ old('category_id', $medicine->category_id) == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="dosage_form" class="block text-sm font-medium text-gray-700 mb-2">Dosage Form</label>
                <select id="dosage_form" name="dosage_form" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue select2-dosage">
                    <option value="">Select Form</option>
                    <option value="tablet" {{ old('dosage_form', $medicine->dosage_form) == 'tablet' ? 'selected' : '' }}>Tablet</option>
                    <option value="capsule" {{ old('dosage_form', $medicine->dosage_form) == 'capsule' ? 'selected' : '' }}>Capsule</option>
                    <option value="syrup" {{ old('dosage_form', $medicine->dosage_form) == 'syrup' ? 'selected' : '' }}>Syrup</option>
                    <option value="suspension" {{ old('dosage_form', $medicine->dosage_form) == 'suspension' ? 'selected' : '' }}>Suspension</option>
                    <option value="injection" {{ old('dosage_form', $medicine->dosage_form) == 'injection' ? 'selected' : '' }}>Injection</option>
                    <option value="cream" {{ old('dosage_form', $medicine->dosage_form) == 'cream' ? 'selected' : '' }}>Cream</option>
                    <option value="ointment" {{ old('dosage_form', $medicine->dosage_form) == 'ointment' ? 'selected' : '' }}>Ointment</option>
                    <option value="gel" {{ old('dosage_form', $medicine->dosage_form) == 'gel' ? 'selected' : '' }}>Gel</option>
                    <option value="drops" {{ old('dosage_form', $medicine->dosage_form) == 'drops' ? 'selected' : '' }}>Drops</option>
                    <option value="inhaler" {{ old('dosage_form', $medicine->dosage_form) == 'inhaler' ? 'selected' : '' }}>Inhaler</option>
                    <option value="powder" {{ old('dosage_form', $medicine->dosage_form) == 'powder' ? 'selected' : '' }}>Powder</option>
                    <option value="solution" {{ old('dosage_form', $medicine->dosage_form) == 'solution' ? 'selected' : '' }}>Solution</option>
                    <option value="lotion" {{ old('dosage_form', $medicine->dosage_form) == 'lotion' ? 'selected' : '' }}>Lotion</option>
                    <option value="spray" {{ old('dosage_form', $medicine->dosage_form) == 'spray' ? 'selected' : '' }}>Spray</option>
                    <option value="patch" {{ old('dosage_form', $medicine->dosage_form) == 'patch' ? 'selected' : '' }}>Patch</option>
                </select>
                @error('dosage_form')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="strength" class="block text-sm font-medium text-gray-700 mb-2">Strength</label>
                <input type="text" id="strength" name="strength" value="{{ old('strength', $medicine->strength) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('strength')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="manufacturer" class="block text-sm font-medium text-gray-700 mb-2">Manufacturer</label>
                <input type="text" id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $medicine->manufacturer) }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('manufacturer')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                <input type="number" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', $medicine->reorder_level) }}" min="0"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('reorder_level')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Manage Stock</label>
                <label class="flex items-center h-[42px]">
                    <input 
                        type="checkbox" 
                        name="manage_stock" 
                        value="1"
                        {{ old('manage_stock', $medicine->manage_stock) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue"
                    >
                    <span class="ml-2 text-sm text-gray-600">Enable inventory tracking</span>
                </label>
                @error('manage_stock')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                    <option value="active" {{ old('status', $medicine->status) == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $medicine->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
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
                <i class="fas fa-save mr-2"></i>Update Medicine
            </button>
        </div>
    </form>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px;
        padding-left: 12px;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #3b82f6;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
    }
</style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2-brand').select2({
            placeholder: 'Select Brand',
            allowClear: true,
            width: '100%'
        });
        
        $('.select2-category').select2({
            placeholder: 'Select Category',
            allowClear: true,
            width: '100%'
        });
        
        $('.select2-dosage').select2({
            placeholder: 'Select Dosage Form',
            allowClear: true,
            width: '100%'
        });
    });
</script>
@endpush
@endsection
