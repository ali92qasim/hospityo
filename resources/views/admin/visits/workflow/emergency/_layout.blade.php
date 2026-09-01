@php
    $useAccordion = config('visits.workflow_accordion_ui', true);
    $careDisabled = ! $visit->triage;
    $initialSection = $workflowData['initial_section'] ?? 'vitals';
    $vitalsState = $careDisabled
        ? 'locked'
        : ($visit->vitalSigns ? 'done' : ($initialSection === 'vitals' ? 'next' : 'idle'));
    $consultationState = $careDisabled || ! $visit->doctor_id
        ? 'locked'
        : ($visit->consultation ? 'done' : ($initialSection === 'consultation' ? 'next' : 'idle'));
    $prescriptionState = $careDisabled || ! ($workflowData['can_prescribe'] ?? false)
        ? 'locked'
        : ($initialSection === 'prescription' ? 'next' : 'idle');
@endphp

<div id="visit-workflow" data-workflow-layout="emergency" data-landmark="emergency-workflow-layout" class="max-w-4xl mx-auto">
    @include('admin.visits.workflow._shared._header')

    @include('admin.visits.workflow.emergency._triage-banner')

    @if($useAccordion)
        <div class="space-y-4" data-workflow-accordion-root data-initial-section="{{ $careDisabled ? '' : $initialSection }}">
            @include('admin.visits.workflow._shared._doctor-assignment', ['disabled' => $careDisabled])

            <x-workflow-accordion-section
                id="vitals"
                title="Record Vital Signs"
                icon="fa-heartbeat"
                icon-color="text-red-500"
                :state="$vitalsState"
                state-label="Complete triage first"
                :open="! $careDisabled && $initialSection === 'vitals'"
                :disabled="$careDisabled"
            >
                @include('admin.visits.workflow._shared._session-warning')
                @include('admin.visits.workflow._shared._vitals-form', ['compact' => false])
            </x-workflow-accordion-section>

            <x-workflow-accordion-section
                id="consultation"
                title="{{ $workflowData['consultation_label'] }}"
                icon="fa-ambulance"
                icon-color="text-red-600"
                :state="$consultationState"
                state-label="{{ $careDisabled ? 'Complete triage first' : 'Assign doctor first' }}"
                :open="! $careDisabled && $initialSection === 'consultation'"
                :disabled="$careDisabled || ! $visit->doctor_id"
            >
                @if($careDisabled)
                    <p class="text-sm text-gray-500 mb-4">Complete triage to unlock emergency care documentation.</p>
                @endif
                @include('admin.visits.workflow._shared._consultation-form')
            </x-workflow-accordion-section>

            @if($workflowData['show_prescriptions'] ?? true)
            <x-workflow-accordion-section
                id="prescription"
                title="Add Prescription"
                icon="fa-prescription"
                icon-color="text-green-600"
                :state="$prescriptionState"
                state-label="{{ $careDisabled ? 'Complete triage first' : 'Assign doctor first' }}"
                :open="! $careDisabled && $initialSection === 'prescription'"
                :disabled="$careDisabled || ! ($workflowData['can_prescribe'] ?? false)"
            >
                @include('admin.visits.workflow._shared._prescription-panel')
            </x-workflow-accordion-section>
            @endif
        </div>

        @if(in_array($visit->status, ['with_doctor', 'triaged']) && ($workflowData['show_complete_visit_button'] ?? false))
            <div class="mt-6 flex justify-end">
                <a href="{{ route('visits.complete', $visit) }}"
                   class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 {{ $careDisabled ? 'pointer-events-none opacity-50' : '' }}">
                    <i class="fas fa-check mr-2"></i>Complete Visit
                </a>
            </div>
        @endif

        @if($visit->triage)
            <div id="triage-content" class="workflow-panel hidden mt-4 bg-white rounded-lg shadow-sm p-6">
                @include('admin.visits.workflow.emergency._triage')
            </div>
        @endif
    @endif
</div>

@include('admin.visits.workflow._shared._scripts')
