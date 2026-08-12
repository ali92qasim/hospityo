@php
    $admission = $visit->admission;
    $lengthOfStay = $admission?->admitted_at
        ? max(1, $admission->admitted_at->startOfDay()->diffInDays(now()->startOfDay()) + 1)
        : null;
    $episodeSummary = view('admin.visits.workflow.ipd._episode-summary-inline', compact('visit', 'workflowData', 'admission', 'lengthOfStay'))->render();
@endphp

<div id="visit-workflow" data-workflow-layout="ipd" data-landmark="ipd-workflow-layout" class="max-w-7xl mx-auto">
    @include('admin.visits.workflow._shared._header', ['headerExtra' => $episodeSummary])

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        @include('admin.visits.workflow.ipd._episode-sidebar')
        @include('admin.visits.workflow.ipd._clinical-feed')
    </div>
</div>

@include('admin.visits.workflow._shared._scripts')
