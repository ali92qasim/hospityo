@php
    $eventCount = $clinicalTimeline->count();
    $vitalsCount = $clinicalTimeline->where('type', 'vitals')->count();
@endphp

<div class="bg-white rounded-lg shadow-sm p-6" data-landmark="ipd-clinical-timeline">
    <div class="flex flex-wrap items-start justify-between gap-2 mb-1">
        <h4 class="text-lg font-medium text-gray-800">Clinical Timeline</h4>
        @if($eventCount > 0)
            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                {{ $eventCount }} {{ Str::plural('event', $eventCount) }}
            </span>
        @endif
    </div>

    @if($eventCount > 0)
        <p class="text-sm text-gray-500 mb-3">
            Newest first.
            @if($eventCount > 4)
                Scroll inside the box below for older records.
            @endif
        </p>
    @endif

    <div
        data-clinical-timeline-scroll
        @class([
            'space-y-3 rounded-lg border border-gray-200 bg-gray-50/50 p-3',
            'max-h-80 overflow-y-auto overscroll-y-contain' => $eventCount > 0,
        ])
        @if($eventCount > 4) tabindex="0" aria-label="Clinical timeline, scroll for older events" @endif
    >
        @forelse($clinicalTimeline as $event)
            @if($event['type'] === 'vitals')
                @include('admin.visits.workflow.ipd._vitals-entry', ['vital' => $event['model'], 'compact' => true])
            @elseif($event['type'] === 'round_note')
                @php $note = $event['model']; @endphp
                <div class="border border-indigo-100 bg-indigo-50 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold uppercase text-indigo-700">Round · Dr. {{ $note->doctor->name }}</span>
                        <span class="text-xs text-indigo-600">{{ ($note->visited_at ?? $note->created_at)->format('M d, Y h:i A') }}</span>
                    </div>
                    <p class="text-sm text-indigo-900">{{ $note->notes }}</p>
                </div>
            @elseif($event['type'] === 'gpe')
                @php $gpe = $event['model']; @endphp
                <div class="border border-purple-100 bg-purple-50 rounded-lg p-4">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-semibold uppercase text-purple-700">GPE · Dr. {{ $gpe->doctor->name }}</span>
                        <span class="text-xs text-purple-600">{{ $gpe->created_at->format('M d, Y h:i A') }}</span>
                    </div>
                    <p class="text-sm text-purple-900">{{ Str::limit($gpe->remarks ?: collect($gpe->systemFindings())->filter()->values()->join(', ') ?: 'GPE recorded', 120) }}</p>
                </div>
            @endif
        @empty
            <p class="text-center text-gray-500 py-8">No clinical events recorded yet. Open a section below to begin.</p>
        @endforelse
    </div>

    @if($vitalsCount > 0)
        <p class="mt-3 text-xs text-gray-500">
            <i class="fas fa-info-circle mr-1" aria-hidden="true"></i>
            Vital signs you save appear here automatically.
        </p>
    @endif
</div>
