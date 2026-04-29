@extends('admin.layout')

@section('title', 'New Leave Request')
@section('page-title', 'New Leave Request')

@section('content')
<div class="max-w-3xl mx-auto">
    <form action="{{ route('hr.leave.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="bg-white rounded-lg shadow-sm mb-6">
            <div class="p-6 border-b border-gray-200">
                <h4 class="text-md font-medium text-gray-800 flex items-center">
                    <i class="fas fa-calendar-plus mr-2 text-medical-blue"></i>
                    Leave Request Details
                </h4>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee *</label>
                        <select name="employee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Employee</option>
                            @foreach($employees ?? [] as $employee)
                                <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>
                                    {{ $employee->full_name }} ({{ $employee->employee_no }})
                                </option>
                            @endforeach
                        </select>
                        @error('employee_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Leave Type *</label>
                        <select name="leave_type_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                            <option value="">Select Leave Type</option>
                            @foreach($leaveTypes ?? [] as $type)
                                <option value="{{ $type->id }}" {{ old('leave_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }} ({{ $type->default_days }} days)
                                </option>
                            @endforeach
                        </select>
                        @error('leave_type_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date *</label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('start_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date *</label>
                        <input type="date" name="end_date" value="{{ old('end_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('end_date') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <div class="flex items-center gap-6">
                            <div class="flex items-center" x-data="{ halfDay: {{ old('is_half_day') ? 'true' : 'false' }} }">
                                <label class="flex items-center cursor-pointer">
                                    <input type="checkbox" name="is_half_day" value="1" x-model="halfDay"
                                           class="w-4 h-4 text-medical-blue border-gray-300 rounded focus:ring-medical-blue"
                                           {{ old('is_half_day') ? 'checked' : '' }}>
                                    <span class="ml-2 text-sm text-gray-700">Half Day</span>
                                </label>
                                <div x-show="halfDay" x-cloak class="ml-4">
                                    <select name="half_day_type" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                                        <option value="first_half" {{ old('half_day_type') == 'first_half' ? 'selected' : '' }}>First Half</option>
                                        <option value="second_half" {{ old('half_day_type') == 'second_half' ? 'selected' : '' }}>Second Half</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        @error('is_half_day') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        @error('half_day_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason *</label>
                        <textarea name="reason" rows="4" placeholder="Provide a reason for the leave request..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>{{ old('reason') }}</textarea>
                        @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Supporting Document</label>
                        <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG, DOC, DOCX (max 5MB)</p>
                        @error('document') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end space-x-4">
            <a href="{{ route('hr.leave.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                <i class="fas fa-paper-plane mr-2"></i>
                Submit Request
            </button>
        </div>
    </form>
</div>
@endsection
