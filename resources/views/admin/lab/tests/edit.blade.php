@extends('admin.layout')

@section('title', 'Edit Investigation - Hospital Management System')
@section('page-title', 'Edit Investigation')
@section('page-description', 'Update investigation information')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Edit Investigation</h3>
                <a href="{{ route('investigations.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Investigations
                </a>
            </div>
        </div>

        <form action="{{ route('investigations.update', $labTest->id) }}" method="POST" class="p-6">
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

                <!-- Test Parameters Section -->
                <div class="border-t border-gray-200 pt-6">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-lg font-medium text-gray-900">Test Parameters</h4>
                        <button type="button" onclick="addParameter()" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                            <i class="fas fa-plus mr-1"></i>Add Parameter
                        </button>
                    </div>
                    
                    <div id="parameters-container">
                        @if($labTest->parameters && $labTest->parameters->count() > 0)
                            @foreach($labTest->parameters as $index => $parameter)
                                <div class="parameter-row bg-gray-50 p-4 rounded-lg mb-3">
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Name *</label>
                                            <input type="text" name="parameters[{{ $index }}][name]" value="{{ $parameter->parameter_name }}" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                                            <input type="text" name="parameters[{{ $index }}][unit]" value="{{ $parameter->unit }}" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference Range</label>
                                            <input type="text" name="parameters[{{ $index }}][reference_range]" value="{{ is_array($parameter->reference_ranges) ? ($parameter->reference_ranges['range'] ?? '') : $parameter->reference_ranges }}" 
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="e.g. 4.5-11.0">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" onclick="removeParameter(this)" class="px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    
                    <p class="text-sm text-gray-500 mt-2">
                        <i class="fas fa-info-circle mr-1"></i>
                        Leave parameters empty if this test uses free-text results instead of measured values.
                    </p>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('investigations.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Update Investigation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let parameterIndex = {{ $labTest->parameters ? $labTest->parameters->count() : 0 }};

function addParameter() {
    const container = document.getElementById('parameters-container');
    const parameterRow = document.createElement('div');
    parameterRow.className = 'parameter-row bg-gray-50 p-4 rounded-lg mb-3';
    parameterRow.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parameter Name *</label>
                <input type="text" name="parameters[${parameterIndex}][name]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <input type="text" name="parameters[${parameterIndex}][unit]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Reference Range</label>
                <input type="text" name="parameters[${parameterIndex}][reference_range]" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="e.g. 4.5-11.0">
            </div>
            <div class="flex items-end">
                <button type="button" onclick="removeParameter(this)" class="px-3 py-2 bg-red-600 text-white text-sm rounded hover:bg-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(parameterRow);
    parameterIndex++;
}

function removeParameter(button) {
    button.closest('.parameter-row').remove();
}
</script>
@endsection