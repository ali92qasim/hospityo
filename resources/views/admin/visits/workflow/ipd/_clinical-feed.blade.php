@php
    $useAccordion = config('visits.workflow_accordion_ui', true);
    $initialSection = $workflowData['initial_section'] ?? 'vitals';
    $hasCareTeam = $visit->hasActiveCareTeam();
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
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h4 class="text-lg font-medium text-gray-800 mb-4">Clinical Timeline</h4>

            <div class="space-y-3 max-h-[32rem] overflow-y-auto">
                @forelse($visit->allVitalSigns->sortByDesc('created_at') as $vital)
                    <div class="border border-blue-100 bg-blue-50 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold uppercase text-blue-700">Vitals</span>
                            <span class="text-xs text-blue-600">{{ $vital->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm text-blue-900">
                            @if($vital->blood_pressure)<span>BP: {{ $vital->blood_pressure }}</span>@endif
                            @if($vital->temperature)<span>Temp: {{ $vital->temperature }}°F</span>@endif
                            @if($vital->pulse_rate)<span>Pulse: {{ $vital->pulse_rate }}</span>@endif
                            @if($vital->spo2)<span>SpO₂: {{ $vital->spo2 }}%</span>@endif
                        </div>
                    </div>
                @empty
                @endforelse

                @foreach($visit->doctorVisitNotes->sortByDesc('created_at') as $note)
                    <div class="border border-indigo-100 bg-indigo-50 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold uppercase text-indigo-700">Round · Dr. {{ $note->doctor->name }}</span>
                            <span class="text-xs text-indigo-600">{{ ($note->visited_at ?? $note->created_at)->format('M d, Y h:i A') }}</span>
                        </div>
                        <p class="text-sm text-indigo-900">{{ $note->notes }}</p>
                    </div>
                @endforeach

                @foreach($visit->ipdGpeRecords->sortByDesc('created_at') as $gpe)
                    <div class="border border-purple-100 bg-purple-50 rounded-lg p-4">
                        <div class="flex justify-between items-start mb-2">
                            <span class="text-xs font-semibold uppercase text-purple-700">GPE · Dr. {{ $gpe->doctor->name }}</span>
                            <span class="text-xs text-purple-600">{{ $gpe->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        <p class="text-sm text-purple-900">{{ Str::limit($gpe->remarks ?: collect($gpe->systemFindings())->filter()->values()->join(', ') ?: 'GPE recorded', 120) }}</p>
                    </div>
                @endforeach

                @if($visit->allVitalSigns->isEmpty() && $visit->doctorVisitNotes->isEmpty() && $visit->ipdGpeRecords->isEmpty())
                    <p class="text-center text-gray-500 py-8">No clinical events recorded yet. Open a section below to begin.</p>
                @endif
            </div>
        </div>

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
                    <div class="mt-6">
                        @include('admin.visits.workflow.ipd._vitals-history')
                    </div>
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
