@php
    use App\Services\IpdClinicalService;

    $useAccordion = config('visits.workflow_accordion_ui', true);
    $initialSection = $workflowData['initial_section'] ?? 'vitals';
    $hasCareTeam = $visit->hasActiveCareTeam();
    $clinicalTimeline = IpdClinicalService::clinicalTimelineEvents($visit);
    $vitalsState = $visit->allVitalSigns->isNotEmpty() ? 'done' : ($initialSection === 'vitals' ? 'next' : 'idle');
    $consultationState = ! $hasCareTeam
        ? 'locked'
        : ($visit->consultation ? 'done' : ($initialSection === 'consultation' ? 'next' : 'idle'));
    $prescriptionState = ! ($workflowData['can_prescribe'] ?? false)
        ? 'locked'
        : ($initialSection === 'prescription' ? 'next' : 'idle');
    $testsState = ! ($workflowData['can_order_labs'] ?? false)
        ? 'locked'
        : ($initialSection === 'tests' ? 'next' : 'idle');
@endphp

<div data-landmark="ipd-clinical-feed" class="lg:col-span-3 space-y-4">
    @unless($visit->admission)
        <div id="admission-content" class="workflow-panel bg-white rounded-lg shadow-sm p-6">
            @include('admin.visits.workflow.ipd._admission')
        </div>
    @else
        @include('admin.visits.workflow.ipd._clinical-timeline', ['clinicalTimeline' => $clinicalTimeline])

        @if($useAccordion)
            <div class="space-y-4" data-workflow-accordion-root data-initial-section="{{ $initialSection }}">
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
                    title="Write Round Note"
                    icon="fa-stethoscope"
                    icon-color="text-indigo-600"
                    :state="$consultationState"
                    state-label="Add care team first"
                    :open="$initialSection === 'consultation'"
                    :disabled="! $hasCareTeam"
                >
                    @include('admin.visits.workflow._shared._consultation-form')
                </x-workflow-accordion-section>

                <x-workflow-accordion-section
                    id="prescription"
                    title="Add Prescription"
                    icon="fa-prescription"
                    icon-color="text-green-600"
                    :state="$prescriptionState"
                    state-label="Add care team first"
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
                        state-label="Add care team first"
                        :open="$initialSection === 'tests'"
                        :disabled="! ($workflowData['can_order_labs'] ?? false)"
                    >
                        @include('admin.visits.workflow.opd._investigations')
                    </x-workflow-accordion-section>
                @endif
            </div>
        @endif

        <div id="admission-content" class="workflow-panel hidden bg-white rounded-lg shadow-sm p-6">
            @include('admin.visits.workflow.ipd._admission')
        </div>
    @endunless

    @include('admin.visits.partials.ipd-gpe-records')
    @include('admin.visits.partials.ipd-care-team')
</div>
