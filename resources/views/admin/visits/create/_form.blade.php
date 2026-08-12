@php
    $backLabels = [
        'opd' => 'OPD',
        'ipd' => 'Admitted Patients',
        'emergency' => 'Emergency',
    ];
    $submitLabels = [
        'opd' => 'Register Patient',
        'ipd' => 'Register Admission',
        'emergency' => 'Register Patient',
    ];
    $backLabel = $backLabels[$visitType] ?? 'Visits';
    $submitLabel = $submitLabels[$visitType] ?? 'Register Visit';
@endphp

<form action="{{ route('visits.store') }}" method="POST" class="p-6">
    @csrf
    <input type="hidden" name="visit_type" value="{{ $visitType }}">

    <div class="space-y-6">
        @include('admin.visits.create._patient-picker')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Date & Time *</label>
            <input type="text" name="visit_datetime" value="{{ old('visit_datetime', now()->format('Y-m-d H:i')) }}"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                   placeholder="YYYY-MM-DD HH:MM"
                   required>
        </div>

        @if($visitType === 'ipd')
        <p class="text-sm text-gray-600 bg-blue-50 border border-blue-100 rounded-lg px-4 py-3">
            After registering, you will assign a bed on the next screen.
        </p>
        @endif
    </div>

    <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
        <a href="{{ route('visits.index', ['visit_type' => $visitType]) }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
            Cancel
        </a>
        <button type="submit" name="save_and_add_another" value="1" class="px-6 py-2 border border-medical-blue text-medical-blue rounded-lg hover:bg-blue-50 flex items-center">
            <i class="fas fa-plus mr-2"></i>
            Save & Add Another
        </button>
        <button type="submit" class="px-6 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 flex items-center">
            <i class="fas fa-save mr-2"></i>
            {{ $submitLabel }}
        </button>
    </div>
</form>
