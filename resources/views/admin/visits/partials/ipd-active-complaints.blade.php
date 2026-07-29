<div class="border border-gray-200 rounded-lg mb-4">
    <button type="button" onclick="toggleAccordion('active-complaints')" class="w-full px-4 py-3 text-left bg-gray-50 hover:bg-gray-100 flex justify-between items-center">
        <span class="font-medium text-gray-800">
            <i class="fas fa-notes-medical text-medical-blue mr-2"></i>Active Complaints
            <span class="ml-2 text-xs font-normal text-gray-500">({{ $activeComplaints->count() }} active)</span>
        </span>
        <i id="active-complaints-icon" class="fas fa-chevron-down text-gray-500"></i>
    </button>
    <div id="active-complaints-content" class="hidden p-4">
        <p class="text-xs text-gray-500 mb-4">
            <i class="fas fa-info-circle mr-1"></i>
            Read-only list of the patient's current active complaints. Updated when presenting complaints are saved below.
        </p>

        <div class="space-y-3 max-h-80 overflow-y-auto">
            @forelse($activeComplaints as $complaint)
                <div class="bg-red-50 border border-red-100 rounded-lg p-4">
                    <p class="text-gray-800 font-medium">{{ $complaint->complaint }}</p>
                    <div class="flex flex-wrap gap-3 text-xs text-gray-500 mt-2">
                        <span><i class="fas fa-clock mr-1"></i>{{ $complaint->created_at->format('M d, Y h:i A') }}</span>
                        @if($complaint->recordedBy)
                            <span><i class="fas fa-user mr-1"></i>{{ $complaint->recordedBy->name }}</span>
                        @endif
                        @if($complaint->visit_id && $complaint->visit_id !== $visit->id)
                            <span><i class="fas fa-link mr-1"></i>Visit #{{ $complaint->visit?->visit_no ?? $complaint->visit_id }}</span>
                        @endif
                    </div>
                    @can('edit visits')
                        @if($complaint->visit_id === $visit->id || ! $complaint->visit_id)
                            <form action="{{ route('visits.complaints.resolve', [$visit, $complaint]) }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="text-sm text-green-700 hover:text-green-900">
                                    <i class="fas fa-check-circle mr-1"></i>Mark as resolved
                                </button>
                            </form>
                        @endif
                    @endcan
                </div>
            @empty
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-clipboard-check text-3xl text-gray-300 mb-2"></i>
                    <p>No active complaints recorded for this patient.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
