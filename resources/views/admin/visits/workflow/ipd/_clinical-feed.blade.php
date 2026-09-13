@php
    use App\Services\IpdClinicalService;

    $initialSection = $workflowData['initial_section'] ?? 'vitals';
    $hasCareTeam = $visit->hasActiveCareTeam();
    $clinicalTimeline = IpdClinicalService::clinicalTimelineEvents($visit);
    $tabAccess = $workflowData['tab_access'] ?? [];
    $consultationUnlocked = $tabAccess['consultation']['unlocked'] ?? $hasCareTeam;
    $prescriptionUnlocked = $tabAccess['prescription']['unlocked'] ?? ($workflowData['can_prescribe'] ?? false);
    $labsUnlocked = $tabAccess['lab']['unlocked'] ?? ($workflowData['can_order_labs'] ?? false);
    $vitalsState = $visit->allVitalSigns->isNotEmpty() ? 'done' : ($initialSection === 'vitals' ? 'next' : 'idle');
    $consultationState = ! $consultationUnlocked
        ? 'locked'
        : ($visit->consultation ? 'done' : ($initialSection === 'consultation' ? 'next' : 'idle'));
    $prescriptionState = ! $prescriptionUnlocked
        ? 'locked'
        : ($initialSection === 'prescription' ? 'next' : 'idle');
    $testsState = ! $labsUnlocked
        ? 'locked'
        : (in_array($initialSection, ['tests', 'lab', 'imaging'], true) ? 'next' : 'idle');
@endphp

<div data-landmark="ipd-clinical-feed" class="lg:col-span-3 space-y-4">
    @unless($visit->admission)
        <div id="admission-content" class="workflow-panel bg-white rounded-lg shadow-sm p-6">
            @include('admin.visits.workflow.ipd._admission')
        </div>
    @else
        @include('admin.visits.workflow.ipd._clinical-timeline', ['clinicalTimeline' => $clinicalTimeline])

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
                :state-label="$tabAccess['consultation']['lock_reason'] ?? 'Add care team first'"
                :open="$initialSection === 'consultation'"
                :disabled="! $consultationUnlocked"
            >
                @include('admin.visits.workflow._shared._consultation-form')
            </x-workflow-accordion-section>

            @if($workflowData['show_prescriptions'] ?? true)
            <x-workflow-accordion-section
                id="prescription"
                title="Add Prescription"
                icon="fa-prescription"
                icon-color="text-green-600"
                :state="$prescriptionState"
                :state-label="$tabAccess['prescription']['lock_reason'] ?? 'Add care team first'"
                :open="$initialSection === 'prescription'"
                :disabled="! $prescriptionUnlocked"
            >
                @include('admin.visits.workflow._shared._prescription-panel')
            </x-workflow-accordion-section>
            @endif

            @if($workflowData['show_lab_investigations'] ?? false)
                <x-workflow-accordion-section
                    id="lab"
                    title="Lab"
                    icon="fa-flask"
                    icon-color="text-teal-600"
                    :state="$testsState"
                    :state-label="$tabAccess['lab']['lock_reason'] ?? 'Add care team first'"
                    :open="$initialSection === 'lab'"
                    :disabled="! $labsUnlocked"
                >
                    @include('admin.visits.workflow.opd._investigations', ['catalog' => 'lab'])
                </x-workflow-accordion-section>
            @endif

            @if($workflowData['show_imaging_investigations'] ?? false)
                <x-workflow-accordion-section
                    id="imaging"
                    title="Imaging"
                    icon="fa-x-ray"
                    icon-color="text-indigo-500"
                    :state="$testsState"
                    :state-label="$tabAccess['imaging']['lock_reason'] ?? 'Add care team first'"
                    :open="$initialSection === 'imaging'"
                    :disabled="! $labsUnlocked"
                >
                    @include('admin.visits.workflow.opd._investigations', ['catalog' => 'imaging'])
                </x-workflow-accordion-section>
            @endif
        </div>

        <div id="admission-content" class="workflow-panel hidden bg-white rounded-lg shadow-sm p-6">
            @include('admin.visits.workflow.ipd._admission')
        </div>
    @endunless

    @include('admin.visits.partials.ipd-gpe-records')
    @include('admin.visits.partials.ipd-care-team')
</div>
