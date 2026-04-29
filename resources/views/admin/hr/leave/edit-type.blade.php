@extends('admin.layout')

@section('title', 'Edit Leave Type')
@section('page-title', 'Edit Leave Type')

@section('content')
<div class="max-w-3xl mx-auto">
    <form action="{{ route('hr.leave-types.update', $leaveType) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800 flex items-center">
                    <i class="fas fa-edit mr-2 text-medical-blue"></i>
                    Edit Leave Type — {{ $leaveType->name }}
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $leaveType->name) }}" placeholder="e.g. Annual Leave"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $leaveType->code) }}" placeholder="e.g. AL"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Days *</label>
                        <input type="number" name="default_days" value="{{ old('default_days', $leaveType->default_days) }}" min="0" step="0.5"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('default_days') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Carry Forward Days</label>
                        <input type="number" name="max_carry_forward_days" value="{{ old('max_carry_forward_days', $leaveType->max_carry_forward_days) }}" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Leave blank or 0 if carry forward is not enabled</p>
                        @error('max_carry_forward_days') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex flex-wrap gap-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_paid" value="1"
                                       class="w-4 h-4 text-medical-blue border-gray-300 rounded focus:ring-medical-blue"
                                       {{ old('is_paid', $leaveType->is_paid) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Paid Leave</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="is_carry_forward" value="1"
                                       class="w-4 h-4 text-medical-blue border-gray-300 rounded focus:ring-medical-blue"
                                       {{ old('is_carry_forward', $leaveType->is_carry_forward) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Carry Forward</span>
                            </label>
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" name="requires_document" value="1"
                                       class="w-4 h-4 text-medical-blue border-gray-300 rounded focus:ring-medical-blue"
                                       {{ old('requires_document', $leaveType->requires_document) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">Requires Document</span>
                            </label>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" placeholder="Optional description of this leave type..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">{{ old('description', $leaveType->description) }}</textarea>
                        @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('hr.leave-types.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-save mr-2"></i>
                Update Leave Type
            </button>
        </div>
    </form>
</div>
@endsection
