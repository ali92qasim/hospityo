@php
    $priorityColors = [
        'critical' => 'bg-red-600 text-white border-red-700',
        'urgent' => 'bg-orange-500 text-white border-orange-600',
        'less_urgent' => 'bg-yellow-400 text-yellow-900 border-yellow-500',
        'non_urgent' => 'bg-green-500 text-white border-green-600',
    ];
@endphp

<div id="emergency-triage-banner" data-landmark="emergency-triage-banner" class="mb-6">
    @if($visit->triage)
        @php
            $level = $visit->triage->priority_level;
            $bannerClass = $priorityColors[$level] ?? 'bg-red-600 text-white border-red-700';
        @endphp
        <div class="rounded-lg border-2 {{ $bannerClass }} p-5 shadow-sm">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide opacity-90 mb-1">Triage Completed</p>
                        <p class="text-2xl font-bold">{{ ucfirst(str_replace('_', ' ', $level)) }}</p>
                        <p class="mt-2 text-sm opacity-95">
                            <span class="font-medium">Chief complaint:</span> {{ $visit->triage->chief_complaint }}
                        </p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-6 text-sm">
                    <div>
                        <span class="opacity-80">Pain scale</span>
                        <p class="text-xl font-semibold">{{ $visit->triage->pain_scale ?? 'N/A' }}/10</p>
                    </div>
                    @if($visit->vitalSigns)
                        <div>
                            <span class="opacity-80">Latest vitals</span>
                            <p class="font-medium">{{ $visit->vitalSigns->updated_at?->diffForHumans() ?? 'Recorded' }}</p>
                        </div>
                    @endif
                    <button type="button" onclick="showTab('triage')" class="px-4 py-2 rounded-lg bg-white/20 hover:bg-white/30 text-sm font-medium">
                        <i class="fas fa-edit mr-1"></i>Review Triage
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="bg-red-50 border-2 border-red-200 rounded-lg p-6">
            <h4 class="text-lg font-semibold text-red-800 mb-4">
                <i class="fas fa-exclamation-triangle mr-2"></i>Triage Required
            </h4>
            @include('admin.visits.workflow.emergency._triage')
        </div>
    @endif
</div>
