@extends('admin.layout')

@section('title', 'Add Medicine')

@section('content')
<div class="mb-4 md:mb-6">
    <h1 class="text-xl md:text-2xl font-bold text-gray-800">Add Medicine</h1>
</div>

<div class="bg-white rounded-lg shadow p-4 md:p-6">
    <form method="POST" action="{{ route('medicines.store') }}">
        @csrf
        
        <div class="responsive-form">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Medicine Name <span class="text-red-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">
                    SKU <span class="text-xs font-normal text-gray-500">(optional)</span>
                </label>
                <input type="text" id="sku" name="sku" value="{{ old('sku') }}" 
                       placeholder="{{ \App\Models\Medicine::skuPlaceholder() }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('sku')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">{{ \App\Models\Medicine::skuProtocolHint() }}</p>
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
                <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
                <select id="brand_id" name="brand_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue select2-brand">
                    <option value="">Select Brand</option>
                    @foreach(\App\Models\MedicineBrand::active()->orderBy('name')->get() as $brand)
                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
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
                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="strength" class="block text-sm font-medium text-gray-700 mb-2">Strength</label>
                <input type="text" id="strength" name="strength" value="{{ old('strength') }}" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                @error('strength')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="reorder_level" class="block text-sm font-medium text-gray-700 mb-2">Reorder Level</label>
                <input type="number" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', 10) }}" min="0"
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
                        {{ old('manage_stock', true) ? 'checked' : '' }}
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
                    <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 mt-6">
            <a href="{{ route('medicines.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 text-center btn-touch">
                Cancel
            </a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 btn-touch">
                <i class="fas fa-save mr-2"></i>Save Medicine
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
    });
</script>
@endpush
@endsection