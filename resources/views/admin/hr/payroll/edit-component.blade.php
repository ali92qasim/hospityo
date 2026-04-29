@extends('admin.layout')

@section('title', 'Edit Salary Component')
@section('page-title', 'Edit Salary Component')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <i class="fas fa-edit mr-2 text-medical-blue"></i>Edit Salary Component
            </h3>
        </div>
        <div class="p-6">
            <form action="{{ route('hr.payroll.update-component', $salaryComponent) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $salaryComponent->name) }}" placeholder="e.g. House Rent Allowance"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Code -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $salaryComponent->code) }}" placeholder="e.g. HRA"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Type -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                        <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Type</option>
                            <option value="allowance" {{ old('type', $salaryComponent->type) == 'allowance' ? 'selected' : '' }}>Allowance</option>
                            <option value="deduction" {{ old('type', $salaryComponent->type) == 'deduction' ? 'selected' : '' }}>Deduction</option>
                        </select>
                        @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Calculation -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Calculation *</label>
                        <select name="calculation" id="calculation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Calculation</option>
                            <option value="fixed" {{ old('calculation', $salaryComponent->calculation) == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                            <option value="percentage" {{ old('calculation', $salaryComponent->calculation) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                        </select>
                        @error('calculation') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Default Amount -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Amount *</label>
                        <input type="number" name="default_amount" value="{{ old('default_amount', $salaryComponent->default_amount) }}" step="0.01" min="0" placeholder="0.00"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('default_amount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Percentage Of (shown only when calculation=percentage) -->
                    <div id="percentage-of-field" class="{{ old('calculation', $salaryComponent->calculation) == 'percentage' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Percentage Of *</label>
                        <select name="percentage_of" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            <option value="">Select Base</option>
                            <option value="basic_salary" {{ old('percentage_of', $salaryComponent->percentage_of) == 'basic_salary' ? 'selected' : '' }}>Basic Salary</option>
                            <option value="gross_salary" {{ old('percentage_of', $salaryComponent->percentage_of) == 'gross_salary' ? 'selected' : '' }}>Gross Salary</option>
                        </select>
                        @error('percentage_of') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Is Taxable -->
                <div class="mt-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_taxable" value="1" {{ old('is_taxable', $salaryComponent->is_taxable) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue">
                        <span class="ml-2 text-sm text-gray-700">This component is taxable</span>
                    </label>
                    @error('is_taxable') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                    <a href="{{ route('hr.payroll.components') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-save mr-2"></i>Update Component
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('calculation').addEventListener('change', function() {
        const percentageField = document.getElementById('percentage-of-field');
        if (this.value === 'percentage') {
            percentageField.classList.remove('hidden');
        } else {
            percentageField.classList.add('hidden');
        }
    });
</script>
@endpush
