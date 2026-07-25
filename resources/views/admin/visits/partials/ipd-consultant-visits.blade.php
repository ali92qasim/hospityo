<div id="consultant-visits-content" class="tab-content hidden">
    <div class="ipd-panel">
        <div class="ipd-panel__header">
            <h4 class="ipd-panel__title">
                <i class="fas fa-user-md text-indigo-600 mr-2"></i>Consultant Visits
            </h4>
            <span class="ipd-panel__badge">{{ $visit->ipdConsultantVisits->count() }} visit(s)</span>
        </div>

        <p class="ipd-panel__hint">
            Multiple consultants may visit during the same admission. Each consultant manages only their own visit notes and orders.
        </p>

        <form action="{{ route('visits.consultant-visits.store', $visit) }}" method="POST" class="ipd-form">
            @csrf

            @if(empty($authDoctor))
                <div class="ipd-form__field">
                    <label for="consultant_doctor_id" class="ipd-form__label">Consultant</label>
                    <select name="consultant_doctor_id" id="consultant_doctor_id" class="ipd-form__select" required>
                        <option value="">Select consultant</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(old('consultant_doctor_id') == $doctor->id)>
                                Dr. {{ $doctor->name }} — {{ $doctor->specialization }}
                            </option>
                        @endforeach
                    </select>
                    @error('consultant_doctor_id')
                        <p class="ipd-form__error">{{ $message }}</p>
                    @enderror
                </div>
            @else
                <div class="ipd-alert ipd-alert--info">
                    <i class="fas fa-info-circle mr-2"></i>
                    Recording as <strong>Dr. {{ $authDoctor->name }}</strong>
                </div>
            @endif

            <div class="ipd-form__field">
                <label for="consultant_seen_at" class="ipd-form__label">Visit Date &amp; Time</label>
                <input type="datetime-local"
                       name="consultant_seen_at"
                       id="consultant_seen_at"
                       value="{{ old('consultant_seen_at', now()->format('Y-m-d\TH:i')) }}"
                       class="ipd-form__input">
                @error('consultant_seen_at')
                    <p class="ipd-form__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="ipd-form__field">
                <label for="consultant_visit_notes" class="ipd-form__label">Visit Notes</label>
                <textarea name="visit_notes"
                          id="consultant_visit_notes"
                          rows="4"
                          class="ipd-form__textarea"
                          placeholder="Consultant assessment, findings, recommendations...">{{ old('visit_notes') }}</textarea>
                @error('visit_notes')
                    <p class="ipd-form__error">{{ $message }}</p>
                @enderror
            </div>

            <div class="ipd-form__field">
                <label for="consultant_orders" class="ipd-form__label">Orders</label>
                <textarea name="orders"
                          id="consultant_orders"
                          rows="4"
                          class="ipd-form__textarea"
                          placeholder="Medications, investigations, procedures, nursing orders...">{{ old('orders') }}</textarea>
                @error('orders')
                    <p class="ipd-form__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="ipd-btn ipd-btn--primary">
                <i class="fas fa-plus-circle mr-2"></i>Record Consultant Visit
            </button>
        </form>
    </div>

    <div class="ipd-panel ipd-panel--history">
        <h4 class="ipd-panel__title mb-4">Consultant Visit History</h4>
        <div class="ipd-history-list">
            @forelse($visit->ipdConsultantVisits as $consultantVisit)
                @php
                    $canManage = empty($authDoctor) || (int) $authDoctor->id === (int) $consultantVisit->consultant_doctor_id;
                @endphp
                <article class="ipd-history-card ipd-consultant-card" data-consultant-id="{{ $consultantVisit->id }}">
                    <header class="ipd-history-card__header">
                        <div>
                            <h5 class="ipd-history-card__title">
                                Dr. {{ $consultantVisit->consultantDoctor?->name ?? 'Unknown Consultant' }}
                            </h5>
                            <p class="ipd-history-card__subtitle">
                                {{ ($consultantVisit->consultant_seen_at ?? $consultantVisit->created_at)->format('M d, Y h:i A') }}
                                @if($consultantVisit->recordedBy)
                                    <span class="ipd-history-card__author">· recorded by {{ $consultantVisit->recordedBy->name }}</span>
                                @endif
                            </p>
                        </div>
                        <span class="ipd-status-badge ipd-status-badge--{{ $consultantVisit->status }}">
                            {{ $consultantVisit->statusLabel() }}
                        </span>
                    </header>

                    @if(filled($consultantVisit->visit_notes))
                        <div class="ipd-consultant-section">
                            <span class="ipd-consultant-section__label">Visit Notes</span>
                            <p>{{ $consultantVisit->visit_notes }}</p>
                        </div>
                    @endif

                    @if(filled($consultantVisit->orders))
                        <div class="ipd-consultant-section">
                            <span class="ipd-consultant-section__label">Orders</span>
                            <p>{{ $consultantVisit->orders }}</p>
                        </div>
                    @endif

                    @if($canManage)
                        <button type="button"
                                class="ipd-btn ipd-btn--ghost ipd-consultant-toggle"
                                data-target="consultant-edit-{{ $consultantVisit->id }}">
                            <i class="fas fa-edit mr-1"></i>Edit
                        </button>

                        <form action="{{ route('visits.consultant-visits.update', [$visit, $consultantVisit]) }}"
                              method="POST"
                              id="consultant-edit-{{ $consultantVisit->id }}"
                              class="ipd-form ipd-consultant-edit hidden">
                            @csrf
                            @method('PUT')

                            <div class="ipd-form__field">
                                <label class="ipd-form__label">Visit Date &amp; Time</label>
                                <input type="datetime-local"
                                       name="consultant_seen_at"
                                       value="{{ ($consultantVisit->consultant_seen_at ?? $consultantVisit->created_at)->format('Y-m-d\TH:i') }}"
                                       class="ipd-form__input">
                            </div>

                            <div class="ipd-form__field">
                                <label class="ipd-form__label">Visit Notes</label>
                                <textarea name="visit_notes" rows="3" class="ipd-form__textarea">{{ $consultantVisit->visit_notes }}</textarea>
                            </div>

                            <div class="ipd-form__field">
                                <label class="ipd-form__label">Orders</label>
                                <textarea name="orders" rows="3" class="ipd-form__textarea">{{ $consultantVisit->orders }}</textarea>
                            </div>

                            <div class="ipd-form__field">
                                <label class="ipd-form__label">Status</label>
                                <select name="status" class="ipd-form__select">
                                    <option value="pending" @selected($consultantVisit->status === 'pending')>Pending</option>
                                    <option value="completed" @selected($consultantVisit->status === 'completed')>Completed</option>
                                    <option value="cancelled" @selected($consultantVisit->status === 'cancelled')>Cancelled</option>
                                </select>
                            </div>

                            <div class="ipd-form__actions">
                                <button type="submit" class="ipd-btn ipd-btn--primary">
                                    <i class="fas fa-save mr-1"></i>Save Changes
                                </button>
                                <button type="button" class="ipd-btn ipd-btn--ghost ipd-consultant-cancel" data-target="consultant-edit-{{ $consultantVisit->id }}">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    @else
                        <p class="ipd-consultant-locked">
                            <i class="fas fa-lock mr-1"></i>Only Dr. {{ $consultantVisit->consultantDoctor?->name }} can edit this record.
                        </p>
                    @endif
                </article>
            @empty
                <div class="ipd-empty-state">
                    <i class="fas fa-user-md"></i>
                    <p>No consultant visits recorded yet.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
