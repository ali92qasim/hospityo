@extends('admin.layout')

@section('title', 'New Sterilization Log')
@section('page-title', 'Schedule Sterilization')

@push('scripts')
@vite(['resources/js/sterilization-form.js'])
@endpush

@section('content')
<div class="max-w-2xl mx-auto">
    <form action="{{ route('ot.sterilization.store') }}" method="POST" id="sterilization-form">
        @csrf

        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
        </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Target Type *</label>
                    <select name="target_type" id="target-type" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select what is being sterilized</option>
                        @foreach(\App\Models\SterilizationLog::TARGET_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('target_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('target_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div id="theatre-field" class="md:col-span-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Operation Theatre</label>
                    <select name="operation_theatre_id" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select Theatre</option>
                        @foreach($theatres as $theatre)
                            <option value="{{ $theatre->id }}" {{ old('operation_theatre_id') == $theatre->id ? 'selected' : '' }}>{{ $theatre->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="instrument-field" class="md:col-span-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instrument</label>
                    <select name="ot_consumable_id" class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select Instrument</option>
                        @foreach($instruments as $inst)
                            <option value="{{ $inst->id }}" {{ old('ot_consumable_id') == $inst->id ? 'selected' : '' }}>{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div id="set-name-field" class="md:col-span-2 hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Instrument Set Name</label>
                    <input type="text" name="instrument_set_name" value="{{ old('instrument_set_name') }}"
                        class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. General Surgery Tray #2">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Method *</label>
                    <select name="method" required class="w-full border-gray-300 rounded-lg text-sm">
                        <option value="">Select</option>
                        @foreach(\App\Models\SterilizationLog::METHODS as $key => $label)
                            <option value="{{ $key }}" {{ old('method') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('method') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Cycle Number</label>
                    <input type="text" name="cycle_number" value="{{ old('cycle_number') }}"
                        class="w-full border-gray-300 rounded-lg text-sm" placeholder="Autoclave cycle #">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Temperature (°C)</label>
                    <input type="number" name="temperature" value="{{ old('temperature') }}" min="0" max="300"
                        class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. 134">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                    <input type="number" name="duration_minutes" value="{{ old('duration_minutes') }}" min="1" max="600"
                        class="w-full border-gray-300 rounded-lg text-sm" placeholder="e.g. 18">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Schedule For (leave empty to start now)</label>
                    <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                        class="w-full border-gray-300 rounded-lg text-sm">
                    <p class="text-xs text-gray-500 mt-1">If left empty, sterilization starts immediately.</p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full border-gray-300 rounded-lg text-sm">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-4">
            <a href="{{ route('ot.sterilization.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 text-sm">
                <i class="fas fa-shield-virus mr-2"></i>Create Log
            </button>
        </div>
    </form>
</div>
@endsection
