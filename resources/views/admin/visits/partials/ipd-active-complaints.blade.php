<div class="ipd-panel ipd-panel--complaints">
    <div class="ipd-panel__header">
        <h4 class="ipd-panel__title">
            <i class="fas fa-notes-medical text-medical-blue mr-2"></i>Active Complaints
        </h4>
        <span class="ipd-panel__badge">{{ $activeComplaints->count() }} active</span>
    </div>

    <p class="ipd-panel__hint">
        Read-only list of the patient's current active complaints. Updated automatically when complaints are recorded during consultation or admission care.
    </p>

    <div class="ipd-complaints-list">
        @forelse($activeComplaints as $complaint)
            <div class="ipd-complaint-card">
                <div class="ipd-complaint-card__body">
                    <p class="ipd-complaint-card__text">{{ $complaint->complaint }}</p>
                    <div class="ipd-complaint-card__meta">
                        <span>
                            <i class="fas fa-clock mr-1"></i>
                            {{ $complaint->created_at->format('M d, Y h:i A') }}
                        </span>
                        @if($complaint->recordedBy)
                            <span>
                                <i class="fas fa-user mr-1"></i>
                                {{ $complaint->recordedBy->name }}
                            </span>
                        @endif
                        @if($complaint->visit_id && $complaint->visit_id !== $visit->id)
                            <span>
                                <i class="fas fa-link mr-1"></i>
                                Visit #{{ $complaint->visit?->visit_no ?? $complaint->visit_id }}
                            </span>
                        @endif
                    </div>
                </div>
                @can('edit visits')
                    @if($complaint->visit_id === $visit->id || ! $complaint->visit_id)
                        <form action="{{ route('visits.complaints.resolve', [$visit, $complaint]) }}" method="POST" class="ipd-complaint-card__action">
                            @csrf
                            <button type="submit" class="ipd-btn ipd-btn--ghost" title="Mark as resolved">
                                <i class="fas fa-check-circle mr-1"></i>Resolve
                            </button>
                        </form>
                    @endif
                @endcan
            </div>
        @empty
            <div class="ipd-empty-state">
                <i class="fas fa-clipboard-check"></i>
                <p>No active complaints recorded for this patient.</p>
            </div>
        @endforelse
    </div>
</div>
