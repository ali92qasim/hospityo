@extends('settings.shell')

@section('settings-section')
<div class="bg-white rounded-lg shadow p-6">
    <h2 class="text-lg font-semibold text-gray-800 mb-2">
        <i class="fas fa-flask mr-2 text-medical-blue"></i>Lab Report Print
    </h2>
    <p class="text-sm text-gray-600 mb-6">Choose which header, patient, and footer elements appear on printed laboratory reports.</p>

    <form method="POST" action="{{ route('settings.lab-report-print.update') }}">
        @csrf
        @method('PUT')

        <div class="space-y-3 mb-6">
            @php
                $labels = [
                    'show_logo' => 'Show hospital logo',
                    'show_qr' => 'Show public report QR code',
                    'show_hospital_address' => 'Show hospital address',
                    'show_hospital_phone' => 'Show hospital phone',
                    'show_hospital_email' => 'Show hospital email',
                    'show_hospital_website' => 'Show hospital website',
                    'show_patient_band' => 'Show patient detail band',
                    'show_reviewers' => 'Show reviewing consultants',
                    'show_page_numbers' => 'Show page numbers',
                ];
            @endphp

            @foreach($labels as $key => $label)
                <label class="flex items-center gap-3 text-sm text-gray-700">
                    <input type="hidden" name="{{ $key }}" value="0">
                    <input type="checkbox"
                           name="{{ $key }}"
                           value="1"
                           class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue"
                           @checked(old($key, $toggles[$key] ?? false))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>

        <button type="submit"
                class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
            Save print settings
        </button>
    </form>
</div>
@endsection
