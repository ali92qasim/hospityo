@php
    $useAccordion = config('visits.workflow_accordion_ui', true);
    $initialSection = $workflowData['initial_section'] ?? 'vitals';
    $vitalsState = $visit->vitalSigns ? 'done' : ($initialSection === 'vitals' ? 'next' : 'idle');
    $consultationState = ! $visit->doctor_id
        ? 'locked'
        : ($visit->consultation ? 'done' : ($initialSection === 'consultation' ? 'next' : 'idle'));
    $prescriptionState = ! ($workflowData['can_prescribe'] ?? false)
        ? 'locked'
        : (($visit->prescriptions->isNotEmpty() ?? false) ? 'done' : ($initialSection === 'prescription' ? 'next' : 'idle'));
    $testsState = ! ($workflowData['can_order_labs'] ?? false)
        ? 'locked'
        : ($initialSection === 'tests' ? 'next' : 'idle');
@endphp

<div id="visit-workflow" data-workflow-layout="opd" data-landmark="opd-workflow-layout" class="max-w-4xl mx-auto">
    @include('admin.visits.workflow._shared._header')

    @if($useAccordion)
        <div class="space-y-4" data-workflow-accordion-root data-initial-section="{{ $initialSection }}">
            @include('admin.visits.workflow._shared._doctor-assignment')

            <x-workflow-accordion-section
                id="vitals"
                title="Record Vital Signs"
                icon="fa-heartbeat"
                icon-color="text-red-500"
                :state="$vitalsState"
                :open="$initialSection === 'vitals'"
            >
                @include('admin.visits.workflow._shared._session-warning')
                @include('admin.visits.workflow._shared._vitals-form', ['compact' => false])
            </x-workflow-accordion-section>

            <x-workflow-accordion-section
                id="consultation"
                title="Write Consultation"
                icon="fa-stethoscope"
                icon-color="text-medical-blue"
                :state="$consultationState"
                state-label="Assign doctor first"
                :open="$initialSection === 'consultation'"
                :disabled="! $visit->doctor_id"
            >
                @include('admin.visits.workflow._shared._consultation-form', ['expandPrimary' => true])
            </x-workflow-accordion-section>

            <x-workflow-accordion-section
                id="prescription"
                title="Add Prescription"
                icon="fa-prescription"
                icon-color="text-green-600"
                :state="$prescriptionState"
                state-label="Assign doctor first"
                :open="$initialSection === 'prescription'"
                :disabled="! ($workflowData['can_prescribe'] ?? false)"
            >
                @include('admin.visits.workflow._shared._prescription-panel')
            </x-workflow-accordion-section>

            @if($workflowData['show_investigations'])
                <x-workflow-accordion-section
                    id="tests"
                    title="Order Investigations"
                    icon="fa-flask"
                    icon-color="text-teal-600"
                    :state="$testsState"
                    state-label="Assign doctor first"
                    :open="$initialSection === 'tests'"
                    :disabled="! ($workflowData['can_order_labs'] ?? false)"
                >
                    @include('admin.visits.workflow.opd._investigations')
                </x-workflow-accordion-section>
            @endif
        </div>

        @if(in_array($visit->status, ['with_doctor', 'vitals_recorded']) && ($workflowData['show_complete_visit_button'] ?? false))
            <div class="mt-6 flex justify-end">
                <a href="{{ route('visits.complete', $visit) }}" class="inline-flex items-center px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">
                    <i class="fas fa-check mr-2"></i>Complete Visit
                </a>
            </div>
        @endif
    @endif
</div>

@include('admin.visits.workflow._shared._scripts')
