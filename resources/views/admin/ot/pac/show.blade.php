@extends('admin.layout')

@section('title', 'PAC — ' . $pac->surgery?->surgery_number)
@section('page-title', 'PAC Details')

@push('scripts')
@vite(['resources/js/pac-form.js'])
@endpush

@section('content')
<div class="max-w-4xl mx-auto">

    @if(session('success'))
    <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
        <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
    </div>
    @endif

    {{-- Header --}}
    <div class="bg-white rounded-lg shadow-sm mb-6">
        <div class="p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">Pre-Anaesthesia Checkup</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $pac->surgery?->surgery_number }} — {{ $pac->surgery?->procedure_name }}</p>
            </div>
            @php
                $statusColors = [
                    'pending'    => 'bg-yellow-100 text-yellow-800',
                    'cleared'    => 'bg-green-100 text-green-800',
                    'not_cleared'=> 'bg-red-100 text-red-800',
                    'requires_further_evaluation' => 'bg-orange-100 text-orange-800',
                ];
            @endphp
            <span class="px-3 py-1 text-sm rounded-full {{ $statusColors[$pac->status] ?? 'bg-gray-100 text-gray-800' }}">
                {{ ucfirst(str_replace('_', ' ', $pac->status)) }}
            </span>
        </div>
    </div>

    {{-- Patient & Surgery Info --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Patient</h4>
            <p class="text-lg font-medium text-gray-900">{{ $pac->patient?->name ?? '—' }}</p>
            <p class="text-sm text-gray-600">{{ $pac->patient?->patient_no }} · {{ $pac->patient?->phone }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase mb-3">Anaesthetist</h4>
            <p class="text-lg font-medium text-gray-900">{{ $pac->anaesthetist?->name ?? 'Unassigned' }}</p>
            <p class="text-sm text-gray-600">Requested by: {{ $pac->requestedBy?->name }} · {{ $pac->created_at?->format('d M Y H:i') }}</p>
        </div>
    </div>

    {{-- Clinical Details --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-4">Clinical Assessment</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-medium text-gray-500">ASA Grade</p>
                <p class="text-gray-800">{{ $pac->asa_grade ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Mallampati Class</p>
                <p class="text-gray-800">{{ $pac->mallampati_class ? 'Class ' . $pac->mallampati_class : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Medical History</p>
                <p class="text-gray-800">{{ $pac->medical_history ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Current Medications</p>
                <p class="text-gray-800">{{ $pac->current_medications ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Allergies</p>
                <p class="text-gray-800 {{ $pac->allergies ? 'text-red-700 font-medium' : '' }}">{{ $pac->allergies ?? 'None reported' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Airway Assessment</p>
                <p class="text-gray-800">{{ $pac->airway_assessment ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Cardiovascular Status</p>
                <p class="text-gray-800">{{ $pac->cardiovascular_status ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Respiratory Status</p>
                <p class="text-gray-800">{{ $pac->respiratory_status ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Renal / Hepatic Status</p>
                <p class="text-gray-800">{{ $pac->renal_hepatic_status ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Vitals --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-4">Vitals</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">BP</p>
                <p class="text-lg font-semibold text-gray-800">{{ $pac->blood_pressure ?? '—' }}</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">Heart Rate</p>
                <p class="text-lg font-semibold text-gray-800">{{ $pac->heart_rate ? $pac->heart_rate . ' bpm' : '—' }}</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">SpO2</p>
                <p class="text-lg font-semibold text-gray-800">{{ $pac->spo2 ? $pac->spo2 . '%' : '—' }}</p>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500">Weight</p>
                <p class="text-lg font-semibold text-gray-800">{{ $pac->weight_kg ? $pac->weight_kg . ' kg' : '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Anaesthesia Plan --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-4">Anaesthesia Plan</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <p class="text-xs font-medium text-gray-500">Proposed Anaesthesia Type</p>
                <p class="text-gray-800">{{ ucfirst($pac->proposed_anaesthesia_type ?? '—') }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Fasting Instructions</p>
                <p class="text-gray-800">{{ $pac->fasting_instructions ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Premedication</p>
                <p class="text-gray-800">{{ $pac->premedication ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500">Investigations Reviewed</p>
                <p class="text-gray-800">{{ $pac->investigations_reviewed ?? '—' }}</p>
            </div>
            <div class="md:col-span-2">
                <p class="text-xs font-medium text-gray-500">Special Precautions</p>
                <p class="text-gray-800">{{ $pac->special_precautions ?? '—' }}</p>
            </div>
        </div>
    </div>

    {{-- Clearance Notes (if decided) --}}
    @if($pac->clearance_notes)
    <div class="bg-{{ $pac->status === 'cleared' ? 'green' : ($pac->status === 'not_cleared' ? 'red' : 'orange') }}-50 border border-{{ $pac->status === 'cleared' ? 'green' : ($pac->status === 'not_cleared' ? 'red' : 'orange') }}-200 rounded-lg p-6 mb-6">
        <h4 class="text-sm font-semibold uppercase mb-2">Clearance Notes</h4>
        <p class="text-sm">{{ $pac->clearance_notes }}</p>
        @if($pac->cleared_at)
        <p class="text-xs mt-2 opacity-75">Cleared at: {{ $pac->cleared_at->format('d M Y H:i') }}</p>
        @endif
    </div>
    @endif

    {{-- Action Forms (only for pending PACs) --}}
    @if($pac->status === 'pending')
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <h4 class="text-sm font-semibold text-gray-500 uppercase mb-4">Decision</h4>
        <div class="flex flex-wrap gap-2 mb-4">
            <button type="button" id="btn-clear" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm">
                <i class="fas fa-check-circle mr-1"></i>Clear for Surgery
            </button>
            <button type="button" id="btn-decline" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm">
                <i class="fas fa-times-circle mr-1"></i>Not Cleared
            </button>
            <button type="button" id="btn-further" class="bg-orange-500 text-white px-4 py-2 rounded-lg hover:bg-orange-600 text-sm">
                <i class="fas fa-exclamation-circle mr-1"></i>Needs Further Evaluation
            </button>
        </div>

        {{-- Clear form --}}
        <div id="form-clear" class="hidden border-t border-gray-200 pt-4 mt-4">
            <form action="{{ route('ot.pac.clear', $pac) }}" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Notes (optional)</label>
                <textarea name="clearance_notes" rows="2" class="w-full border-gray-300 rounded-lg text-sm mb-3" placeholder="Any notes for the surgical team"></textarea>
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 text-sm">
                    <i class="fas fa-check mr-2"></i>Confirm Clearance
                </button>
            </form>
        </div>

        {{-- Decline form --}}
        <div id="form-decline" class="hidden border-t border-gray-200 pt-4 mt-4">
            <form action="{{ route('ot.pac.decline', $pac) }}" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason for not clearing *</label>
                <textarea name="clearance_notes" rows="2" required class="w-full border-gray-300 rounded-lg text-sm mb-3" placeholder="Why is the patient not fit for surgery?"></textarea>
                <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 text-sm">
                    <i class="fas fa-times mr-2"></i>Confirm Not Cleared
                </button>
            </form>
        </div>

        {{-- Further eval form --}}
        <div id="form-further" class="hidden border-t border-gray-200 pt-4 mt-4">
            <form action="{{ route('ot.pac.further-eval', $pac) }}" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">What further evaluation is needed? *</label>
                <textarea name="clearance_notes" rows="2" required class="w-full border-gray-300 rounded-lg text-sm mb-3" placeholder="e.g., Cardiology opinion needed, repeat ECG, etc."></textarea>
                <button type="submit" class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>Submit
                </button>
            </form>
        </div>
    </div>
    @endif

    <div class="flex justify-between">
        <a href="{{ route('ot.pac.index') }}" class="text-gray-600 hover:text-gray-800 text-sm">← Back to PAC List</a>
        <a href="{{ route('ot.surgeries.show', $pac->surgery_id) }}" class="text-medical-blue hover:text-blue-700 text-sm">View Surgery →</a>
    </div>
</div>
@endsection
