@extends('settings.shell')

@section('settings-section')
<div class="space-y-6">
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

            <div class="mb-6 pt-4 border-t border-gray-100">
                <p class="text-sm font-medium text-gray-800 mb-1">Previous results per parameter</p>
                <p class="text-xs text-gray-500 mb-3">How many prior values (with dates) to show under each parameter on the printed report.</p>
                @php
                    $previousCount = (int) old('previous_values_count', $toggles['previous_values_count'] ?? 3);
                @endphp
                <div class="flex flex-wrap gap-4">
                    @foreach(range(1, 5) as $count)
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio"
                                   name="previous_values_count"
                                   value="{{ $count }}"
                                   class="border-gray-300 text-medical-blue focus:ring-medical-blue"
                                   @checked($previousCount === $count)>
                            <span>{{ $count }}</span>
                        </label>
                    @endforeach
                </div>
                @error('previous_values_count')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                Save print settings
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Consultant roster</h2>
        <p class="text-sm text-gray-600 mb-6">Doctors listed here appear as optional reviewing consultants when verifying lab results. Order is preserved.</p>

        <form
            id="lab-report-roster-form"
            method="POST"
            action="{{ route('settings.lab-report-print.roster') }}"
            data-added-ids="{{ $rosterRows->pluck('doctor_id')->values()->toJson() }}"
        >
            @csrf
            @method('PUT')

            <input type="hidden" name="doctor_ids" value="">

            <div class="flex flex-wrap gap-3 items-end mb-6">
                <div class="min-w-64">
                    <label for="lab-report-roster-doctor-select" class="block text-sm font-medium text-gray-700 mb-1">
                        Add Doctor
                    </label>
                    <select
                        id="lab-report-roster-doctor-select"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue focus:border-transparent"
                    >
                        <option value="">Select a doctor</option>
                        @foreach($doctors as $doctor)
                            <option
                                value="{{ $doctor->id }}"
                                @disabled($rosterRows->contains('doctor_id', $doctor->id))
                            >{{ $doctor->name }}@if($doctor->qualification) — {{ $doctor->qualification }}@endif</option>
                        @endforeach
                    </select>
                </div>
                <button
                    id="lab-report-roster-add-row"
                    type="button"
                    class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <i class="fas fa-plus mr-2"></i>Add
                </button>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody id="lab-report-roster-rows" class="divide-y divide-gray-200">
                        @foreach($rosterRows as $rowIndex => $row)
                            <tr data-roster-row class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <span data-doctor-name>{{ $row->doctor?->name }}</span>
                                    <input data-doctor-id type="hidden" name="doctor_ids[{{ $rowIndex }}]" value="{{ $row->doctor_id }}">
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" data-remove-row class="text-red-600 hover:text-red-800 text-sm">
                                        Remove
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        <tr data-empty-row class="{{ $rosterRows->isEmpty() ? '' : 'hidden' }}">
                            <td colspan="2" class="px-4 py-6 text-sm text-gray-500 text-center">
                                No consultants on the roster yet.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <button type="submit"
                    class="inline-flex items-center px-4 py-2 bg-medical-blue text-white rounded-lg hover:bg-blue-700 transition-colors">
                Save consultant roster
            </button>
        </form>
    </div>
</div>

<template id="lab-report-roster-row-template">
    <tr data-roster-row class="hover:bg-gray-50">
        <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
            <span data-doctor-name></span>
            <input data-doctor-id type="hidden" value="">
        </td>
        <td class="px-4 py-3 text-right">
            <button type="button" data-remove-row class="text-red-600 hover:text-red-800 text-sm">
                Remove
            </button>
        </td>
    </tr>
</template>

@vite(['resources/js/lab-report-roster-form.js'])
@endsection
