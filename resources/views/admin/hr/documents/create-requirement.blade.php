@extends('admin.layout')

@section('title', 'Add Document Requirement')
@section('page-title', 'Add Document Requirement')

@section('content')
<!-- Back Link -->
<div class="mb-4">
    <a href="{{ route('hr.documents.requirements') }}" class="text-medical-blue hover:text-blue-700 text-sm">
        <i class="fas fa-arrow-left mr-1"></i>Back to Requirements
    </a>
</div>

<div class="max-w-3xl mx-auto">
    <form action="{{ route('hr.documents.store-requirement') }}" method="POST">
        @csrf

        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800 flex items-center">
                    <i class="fas fa-clipboard-list mr-2 text-medical-blue"></i>
                    Document Requirement Details
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Document Type --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Document Type *</label>
                        <input type="text" name="document_type" value="{{ old('document_type') }}" placeholder="e.g. pmdc"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <p class="text-xs text-gray-500 mt-1">Short identifier for the document type (e.g. pmdc, cnic, degree)</p>
                        @error('document_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Label --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Label *</label>
                        <input type="text" name="label" value="{{ old('label') }}" placeholder="e.g. PMDC Registration"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <p class="text-xs text-gray-500 mt-1">Human-readable name for this document requirement</p>
                        @error('label') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Applicable To --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Applicable To *</label>
                        <select name="applicable_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Applicability</option>
                            <optgroup label="Staff Categories">
                                <option value="all" {{ old('applicable_to') == 'all' ? 'selected' : '' }}>All Employees</option>
                                <option value="medical" {{ old('applicable_to') == 'medical' ? 'selected' : '' }}>Medical Staff</option>
                                <option value="nursing" {{ old('applicable_to') == 'nursing' ? 'selected' : '' }}>Nursing Staff</option>
                                <option value="admin" {{ old('applicable_to') == 'admin' ? 'selected' : '' }}>Administrative Staff</option>
                                <option value="technical" {{ old('applicable_to') == 'technical' ? 'selected' : '' }}>Technical Staff</option>
                                <option value="support" {{ old('applicable_to') == 'support' ? 'selected' : '' }}>Support Staff</option>
                            </optgroup>
                            <optgroup label="Specific Designation">
                                @foreach($designations ?? [] as $designation)
                                    <option value="designation:{{ $designation->id }}" {{ old('applicable_to') == 'designation:' . $designation->id ? 'selected' : '' }}>
                                        {{ $designation->name }} {{ $designation->category ? '(' . ucfirst($designation->category) . ')' : '' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                        @error('applicable_to') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Is Mandatory --}}
                    <div>
                        <label class="flex items-center cursor-pointer">
                            <input type="hidden" name="is_mandatory" value="0">
                            <input type="checkbox" name="is_mandatory" value="1"
                                   class="w-4 h-4 text-medical-blue border-gray-300 rounded focus:ring-medical-blue"
                                   {{ old('is_mandatory', true) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm font-medium text-gray-700">Mandatory Document</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">Employees will be flagged as non-compliant if this document is missing</p>
                        @error('is_mandatory') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Has Expiry --}}
                    <div x-data="{ hasExpiry: {{ old('has_expiry') ? 'true' : 'false' }} }">
                        <label class="flex items-center cursor-pointer">
                            <input type="hidden" name="has_expiry" value="0">
                            <input type="checkbox" name="has_expiry" value="1" x-model="hasExpiry"
                                   class="w-4 h-4 text-medical-blue border-gray-300 rounded focus:ring-medical-blue"
                                   {{ old('has_expiry') ? 'checked' : '' }}>
                            <span class="ml-2 text-sm font-medium text-gray-700">Has Expiry Date</span>
                        </label>
                        <p class="text-xs text-gray-500 mt-1 ml-6">This document type expires and needs renewal</p>
                        @error('has_expiry') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror

                        {{-- Expiry Reminder Days (shown when has_expiry is checked) --}}
                        <div x-show="hasExpiry" x-cloak class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Expiry Reminder Days</label>
                            <input type="number" name="expiry_reminder_days" value="{{ old('expiry_reminder_days', 30) }}" min="1" max="365" placeholder="30"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">Number of days before expiry to send a reminder</p>
                            @error('expiry_reminder_days') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" placeholder="Optional description or instructions for this document requirement..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('description') }}</textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('hr.documents.requirements') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>
                Save Requirement
            </button>
        </div>
    </form>
</div>
@endsection
