@extends('admin.layout')

@section('title', 'Add Designation')
@section('page-title', 'Add Designation')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Designation Information</h3>
                <a href="{{ route('hr.designations.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Designations
                </a>
            </div>
        </div>

        <form action="{{ route('hr.designations.store') }}" method="POST" class="p-6">
            @csrf

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                    @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                    <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        <option value="">Select Category</option>
                        <option value="medical" {{ old('category') == 'medical' ? 'selected' : '' }}>Medical</option>
                        <option value="nursing" {{ old('category') == 'nursing' ? 'selected' : '' }}>Nursing</option>
                        <option value="admin" {{ old('category') == 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="technical" {{ old('category') == 'technical' ? 'selected' : '' }}>Technical</option>
                        <option value="support" {{ old('category') == 'support' ? 'selected' : '' }}>Support</option>
                    </select>
                    @error('category') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('description') }}</textarea>
                    @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('hr.designations.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Create Designation
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
