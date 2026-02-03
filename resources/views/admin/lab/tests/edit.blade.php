@extends('admin.layout')

@section('title', 'Edit Lab Test - Hospital Management System')
@section('page-title', 'Edit Lab Test')
@section('page-description', 'Update laboratory test information')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Edit Lab Test</h3>
                <a href="{{ route('lab-tests.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Lab Tests
                </a>
            </div>
        </div>

        <form action="{{ route('lab-tests.update', $labTest) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Code *</label>
                        <input type="text" name="code" value="{{ old('code', $labTest->code) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Name *</label>
                        <input type="text" name="name" value="{{ old('name', $labTest->name) }}" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('description', $labTest->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            <option value="">Select Category</option>
                            <option value="hematology" {{ old('category', $labTest->category) == 'hematology' ? 'selected' : '' }}>Hematology</option>
                            <option value="biochemistry" {{ old('category', $labTest->category) == 'biochemistry' ? 'selected' : '' }}>Biochemistry</option>
                            <option value="microbiology" {{ old('category', $labTest->category) == 'microbiology' ? 'selected' : '' }}>Microbiology</option>
                            <option value="immunology" {{ old('category', $labTest->category) == 'immunology' ? 'selected' : '' }}>Immunology</option>
                            <option value="pathology" {{ old('category', $labTest->category) == 'pathology' ? 'selected' : '' }}>Pathology</option>
                            <option value="radiology" {{ old('category', $labTest->category) == 'radiology' ? 'selected' : '' }}>Radiology</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Sample Type</label>
                        <select name="sample_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            <option value="">Select Sample Type</option>
                            <option value="blood" {{ old('sample_type', $labTest->sample_type) == 'blood' ? 'selected' : '' }}>Blood</option>
                            <option value="urine" {{ old('sample_type', $labTest->sample_type) == 'urine' ? 'selected' : '' }}>Urine</option>
                            <option value="stool" {{ old('sample_type', $labTest->sample_type) == 'stool' ? 'selected' : '' }}>Stool</option>
                            <option value="sputum" {{ old('sample_type', $labTest->sample_type) == 'sputum' ? 'selected' : '' }}>Sputum</option>
                            <option value="swab" {{ old('sample_type', $labTest->sample_type) == 'swab' ? 'selected' : '' }}>Swab</option>
                            <option value="tissue" {{ old('sample_type', $labTest->sample_type) == 'tissue' ? 'selected' : '' }}>Tissue</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Price (₨) *</label>
                        <input type="number" name="price" value="{{ old('price', $labTest->price) }}" 
                               step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Turnaround Time (hours)</label>
                        <input type="number" name="turnaround_time" value="{{ old('turnaround_time', $labTest->turnaround_time) }}" 
                               min="1"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                    <textarea name="instructions" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" 
                              placeholder="Special instructions for sample collection or preparation">{{ old('instructions', $labTest->instructions) }}</textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" 
                           {{ old('is_active', $labTest->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-medical-blue focus:ring-medical-blue border-gray-300 rounded">
                    <label class="ml-2 block text-sm text-gray-700">Active</label>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('lab-tests.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Update Lab Test
                </button>
            </div>
        </form>
    </div>
</div>
@endsection