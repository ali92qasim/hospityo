@php
    $assignedDoctorIds = $visit->careTeam->pluck('doctor_id')->all();
    $careTeamDoctors = $visit->careTeam;
@endphp

<div id="care-team-content" class="tab-content hidden">
    <div class="space-y-6">
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-800">
                    <i class="fas fa-users text-purple-600 mr-2"></i>Care Team
                    <span class="ml-2 text-xs font-normal text-gray-500">({{ $careTeamDoctors->count() }} active)</span>
                </h4>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Manage who is involved in this admission. The primary doctor is the attending physician of record. Doctor visit notes log when team members actually see the patient.
            </p>

            @if($careTeamDoctors->isNotEmpty())
                <div class="space-y-3 mb-6">
                    @foreach($careTeamDoctors as $member)
                        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="font-medium text-purple-900">Dr. {{ $member->doctor->name }}</p>
                                    @if($member->is_primary)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Primary</span>
                                    @endif
                                </div>
                                <p class="text-sm text-purple-700">{{ $member->doctor->specialization }}</p>
                                <p class="text-xs text-purple-600 mt-1">
                                    Added {{ $member->added_at->format('M d, Y h:i A') }}
                                    @if($member->addedBy)
                                        · by {{ $member->addedBy->name }}
                                    @endif
                                </p>
                            </div>
                            @can('edit visits')
                                <div class="flex flex-wrap items-center gap-3">
                                    @if(! $member->is_primary)
                                        <form action="{{ route('visits.care-team.primary', $visit) }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="doctor_id" value="{{ $member->doctor_id }}">
                                            <button type="submit" class="text-sm text-medical-blue hover:text-blue-700">
                                                <i class="fas fa-star mr-1"></i>Set as primary
                                            </button>
                                        </form>
                                    @endif
                                    <form action="{{ route('visits.care-team.remove', [$visit, $member]) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                            <i class="fas fa-times mr-1"></i>Remove
                                        </button>
                                    </form>
                                </div>
                            @endcan
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6 text-sm text-amber-800">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    No doctors on the care team yet. Add at least one doctor before recording GPE, prescriptions, or visit notes.
                </div>
            @endif

            @can('edit visits')
                <form action="{{ route('visits.care-team.store', $visit) }}" method="POST" data-save-tab="care-team">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Add Doctor to Care Team</label>
                        <select name="doctor_id" class="visit-workflow-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                            <option value="">Select Doctor</option>
                            @foreach($doctors as $doctor)
                                @if(! in_array($doctor->id, $assignedDoctorIds))
                                    <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>
                                        Dr. {{ $doctor->name }} - {{ $doctor->specialization }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('doctor_id')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-user-plus mr-2"></i>Add Doctor
                    </button>
                </form>
            @endcan
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-800">
                    <i class="fas fa-notes-medical text-indigo-600 mr-2"></i>Record Doctor Visit Note
                </h4>
                <span class="text-xs text-gray-500">{{ $visit->doctorVisitNotes->count() }} note(s)</span>
            </div>
            <p class="text-sm text-gray-600 mb-4">
                Log when a care team member sees the patient. Visit time is captured automatically when you submit.
            </p>

            @if($careTeamDoctors->isEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    <i class="fas fa-exclamation-circle mr-1"></i>
                    Add doctors to the care team before recording visit notes.
                </div>
            @else
                <form action="{{ route('visits.doctor-visit-notes.store', $visit) }}" method="POST" data-save-tab="care-team">
                    @csrf

                    @if(empty($authDoctor) || ! in_array($authDoctor->id, $assignedDoctorIds))
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Doctor (from care team)</label>
                            <select name="doctor_id" class="visit-workflow-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" required>
                                <option value="">Select Doctor</option>
                                @foreach($careTeamDoctors as $member)
                                    <option value="{{ $member->doctor_id }}" @selected(old('doctor_id') == $member->doctor_id)>
                                        Dr. {{ $member->doctor->name }} - {{ $member->doctor->specialization }}
                                    </option>
                                @endforeach
                            </select>
                            @error('doctor_id')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-800">
                            <i class="fas fa-info-circle mr-1"></i>
                            Recording visit note as <strong>Dr. {{ $authDoctor->name }}</strong>
                        </div>
                    @endif

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Visit Notes</label>
                        <textarea name="notes" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue" placeholder="Assessment, findings, recommendations..." required>{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-plus-circle mr-2"></i>Record Visit Note
                    </button>
                </form>
            @endif
        </div>

        <div>
            <h4 class="text-lg font-medium text-gray-800 mb-4">Doctor Visit Notes History</h4>
            <div class="space-y-4 max-h-96 overflow-y-auto">
                @forelse($visit->doctorVisitNotes as $visitNote)
                    @php
                        $canManage = empty($authDoctor) || (int) $authDoctor->id === (int) $visitNote->doctor_id;
                    @endphp
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 ipd-visit-note-card">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h5 class="font-medium text-gray-800">Dr. {{ $visitNote->doctor?->name ?? 'Unknown Doctor' }}</h5>
                                <p class="text-sm text-gray-500 mt-1">
                                    Visited {{ $visitNote->visited_at->format('M d, Y h:i A') }}
                                    @if($visitNote->createdBy)
                                        · recorded by {{ $visitNote->createdBy->name }}
                                    @endif
                                </p>
                            </div>
                            @php
                                $statusClasses = match($visitNote->status) {
                                    'completed' => 'bg-green-100 text-green-800',
                                    'cancelled' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-yellow-100 text-yellow-800',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClasses }}">
                                {{ $visitNote->statusLabel() }}
                            </span>
                        </div>

                        @if(filled($visitNote->notes))
                            <div class="mb-3 text-sm text-gray-700">
                                <span class="font-medium text-gray-600">Visit Notes:</span>
                                <p class="mt-1 whitespace-pre-line">{{ $visitNote->notes }}</p>
                            </div>
                        @endif

                        @if(filled($visitNote->orders))
                            <div class="mb-3 text-sm text-gray-700">
                                <span class="font-medium text-gray-600">Orders:</span>
                                <p class="mt-1 whitespace-pre-line">{{ $visitNote->orders }}</p>
                            </div>
                        @endif

                        @if($canManage)
                            <button type="button"
                                    class="text-sm text-medical-blue hover:text-blue-700 ipd-visit-note-toggle"
                                    data-target="visit-note-edit-{{ $visitNote->id }}">
                                <i class="fas fa-edit mr-1"></i>Edit
                            </button>

                            <form action="{{ route('visits.doctor-visit-notes.update', [$visit, $visitNote]) }}"
                                  method="POST"
                                  id="visit-note-edit-{{ $visitNote->id }}"
                                  class="hidden mt-4 pt-4 border-t border-gray-200 ipd-visit-note-edit">
                                @csrf
                                @method('PUT')

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Visit Notes</label>
                                    <textarea name="notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">{{ $visitNote->notes }}</textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <select name="status" class="visit-workflow-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-medical-blue">
                                        <option value="pending" @selected($visitNote->status === 'pending')>Pending</option>
                                        <option value="completed" @selected($visitNote->status === 'completed')>Completed</option>
                                        <option value="cancelled" @selected($visitNote->status === 'cancelled')>Cancelled</option>
                                    </select>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button type="submit" class="bg-medical-blue text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                        <i class="fas fa-save mr-1"></i>Save Changes
                                    </button>
                                    <button type="button" class="text-gray-600 hover:text-gray-800 px-4 py-2 ipd-visit-note-cancel" data-target="visit-note-edit-{{ $visitNote->id }}">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        @else
                            <p class="text-xs text-gray-500 mt-2">
                                <i class="fas fa-lock mr-1"></i>Only Dr. {{ $visitNote->doctor?->name }} can edit this record.
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-4">No doctor visit notes recorded yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
