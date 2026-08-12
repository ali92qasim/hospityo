@if(session('warning'))
    <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg flex items-start">
        <i class="fas fa-exclamation-triangle mr-3 mt-0.5"></i>
        <span>{{ session('warning') }}</span>
    </div>
@endif

@php
    $compact = $compact ?? false;
    $appendOnly = $workflowData['append_only_vitals'] ?? false;
@endphp

<form action="{{ route('visits.vitals', $visit) }}" method="POST" @if($appendOnly) data-save-tab="vitals" @endif>
    @csrf
    <div class="{{ $compact ? 'grid grid-cols-2 md:grid-cols-4 gap-3' : 'grid grid-cols-2 gap-4' }}">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Blood Pressure</label>
            <input type="text" name="blood_pressure" value="{{ $appendOnly ? '' : old('blood_pressure', $visit->vitalSigns?->blood_pressure) }}"
                   placeholder="120/80" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Temperature (°F)</label>
            <input type="number" name="temperature" value="{{ $appendOnly ? '' : old('temperature', $visit->vitalSigns?->temperature) }}"
                   step="0.1" placeholder="98.6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Pulse Rate (bpm)</label>
            <input type="number" name="pulse_rate" value="{{ $appendOnly ? '' : old('pulse_rate', $visit->vitalSigns?->pulse_rate) }}"
                   placeholder="72" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
        </div>
        @unless($compact)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">SpO<sub>2</sub> (%)</label>
                <input type="number" name="spo2" value="{{ $appendOnly ? '' : old('spo2', $visit->vitalSigns?->spo2) }}"
                       min="0" max="100" placeholder="98" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">BSR (%)</label>
                <input type="number" name="bsr" value="{{ $appendOnly ? '' : old('bsr', $visit->vitalSigns?->bsr) }}"
                       step="0.01" min="0" placeholder="120" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Weight (kg)</label>
                <input type="number" name="weight" value="{{ $appendOnly ? '' : old('weight', $visit->vitalSigns?->weight) }}"
                       step="0.1" placeholder="70.5" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Height (ft)</label>
                <input type="number" name="height" value="{{ $appendOnly ? '' : old('height', $visit->vitalSigns?->height) }}"
                       step="0.01" placeholder="5.6" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                <p class="text-xs text-gray-500 mt-1">Example: 5.6 for 5'6"</p>
            </div>
        @endunless
    </div>
    <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
        <textarea name="notes" rows="{{ $compact ? 2 : 3 }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ $appendOnly ? '' : old('notes', $visit->vitalSigns?->notes) }}</textarea>
    </div>
    <button type="submit" class="mt-4 bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
        <i class="fas fa-save mr-2"></i>{{ $appendOnly ? 'Add Vital Signs' : 'Save Vital Signs' }}
    </button>
</form>
