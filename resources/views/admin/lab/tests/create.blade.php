@extends('admin.layout')

@section('title', 'Create Investigation - Laboratory Information System')
@section('page-title', 'Create Investigation')
@section('page-description', 'Add new investigation definition')

@section('content')
<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('lab-tests.store') }}" method="POST">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Test Code</label>
                <input type="text" name="code" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Test Name</label>
                <input type="text" name="name" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Category</option>
                    <option value="hematology">Hematology</option>
                    <option value="biochemistry">Biochemistry</option>
                    <option value="microbiology">Microbiology</option>
                    <option value="immunology">Immunology</option>
                    <option value="pathology">Pathology</option>
                    <option value="molecular">Molecular</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Sample Type</label>
                <select name="sample_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                    <option value="">Select Sample Type</option>
                    <option value="blood">Blood</option>
                    <option value="urine">Urine</option>
                    <option value="stool">Stool</option>
                    <option value="sputum">Sputum</option>
                    <option value="csf">CSF</option>
                    <option value="tissue">Tissue</option>
                    <option value="swab">Swab</option>
                    <option value="other">Other</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (₨)</label>
                <input type="number" name="price" min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Turnaround Time (hours)</label>
                <input type="number" name="turnaround_time" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Test description..."></textarea>
            </div>
            
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Instructions</label>
                <textarea name="instructions" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Collection and handling instructions..."></textarea>
            </div>
        </div>
        
        <!-- Test Parameters Section -->
        <div class="border-t border-gray-200 pt-6 mt-6">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-medium text-gray-900">Test Parameters</h4>
                <button type="button" onclick="addParameter()" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700">
                    <i class="fas fa-plus mr-1"></i>Add Parameter
                </button>
            </div>
            
            <div id="parameters-container"></div>
            
            <p class="text-sm text-gray-500 mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                Leave parameters empty if this test uses free-text results instead of measured values.
            </p>
        </div>
        
        <div class="flex justify-end space-x-4 mt-6">
            <a href="{{ route('lab-tests.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>Create Test
            </button>
        </div>
    </form>
</div>

<script>
let parameterIndex = 0;

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