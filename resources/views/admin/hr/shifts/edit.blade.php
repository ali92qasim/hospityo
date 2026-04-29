@extends('admin.layout')

@section('title', 'Edit Shift')
@section('page-title', 'Edit Shift')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">Edit Shift</h3>
                <a href="{{ route('hr.shifts.index') }}" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Shifts
                </a>
            </div>
        </div>

        <form action="{{ route('hr.shifts.update', $shift) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Name *</label>
                        <input type="text" name="name" value="{{ old('name', $shift->name) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="e.g. Morning Shift" required>
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Code *</label>
                        <input type="text" name="code" value="{{ old('code', $shift->code) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                               placeholder="e.g. MS" required>
                        @error('code') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Time *</label>
                        <input type="time" name="start_time" value="{{ old('start_time', \Carbon\Carbon::parse($shift->start_time)->format('H:i')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('start_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Time *</label>
                        <input type="time" name="end_time" value="{{ old('end_time', \Carbon\Carbon::parse($shift->end_time)->format('H:i')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('end_time') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Break Duration (minutes) *</label>
                        <input type="number" name="break_duration" value="{{ old('break_duration', $shift->break_duration) }}" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('break_duration') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Working Hours *</label>
                        <input type="number" name="working_hours" value="{{ old('working_hours', $shift->working_hours) }}" min="0" step="0.5"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent" required>
                        @error('working_hours') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Grace Minutes</label>
                        <input type="number" name="grace_minutes" value="{{ old('grace_minutes', $shift->grace_minutes) }}" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent">
                        @error('grace_minutes') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Color *</label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="color" value="{{ old('color', $shift->color) }}"
                                   class="w-12 h-10 border border-gray-300 rounded-lg cursor-pointer">
                            <span class="text-sm text-gray-500">Choose a color to identify this shift</span>
                        </div>
                        @error('color') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="is_overnight" value="1" {{ old('is_overnight', $shift->is_overnight) ? 'checked' : '' }}
                                   class="w-5 h-5 text-medical-blue border-gray-300 rounded focus:ring-medical-blue">
                            <div>
                                <span class="text-sm font-medium text-gray-700">Overnight Shift</span>
                                <p class="text-xs text-gray-500">Check if this shift spans across midnight</p>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                <a href="{{ route('hr.shifts.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Update Shift
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
