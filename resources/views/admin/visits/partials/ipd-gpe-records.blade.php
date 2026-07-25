<div id="gpe-records-content" class="tab-content hidden">
    <div class="ipd-panel">
        <div class="ipd-panel__header">
            <h4 class="ipd-panel__title">
                <i class="fas fa-stethoscope text-medical-blue mr-2"></i>General Physical Examination (GPE)
            </h4>
            <span class="ipd-panel__badge">{{ $visit->ipdGpeRecords->count() }} record(s)</span>
        </div>

        <p class="ipd-panel__hint">
            Add a new GPE entry each time an examination is performed. Each record is stored separately with timestamp and author.
        </p>

        <form action="{{ route('visits.gpe-records.store', $visit) }}" method="POST" class="ipd-form ipd-gpe-form">
            @csrf

            @if(empty($authDoctor))
                <div class="ipd-form__field">
                    <label for="gpe_doctor_id" class="ipd-form__label">Examining Doctor</label>
                    <select name="doctor_id" id="gpe_doctor_id" class="ipd-form__select" required>
                        <option value="">Select doctor</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(old('doctor_id') == $doctor->id)>
                                Dr. {{ $doctor->name }} — {{ $doctor->specialization }}
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')
                        <p class="ipd-form__error">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div class="ipd-gpe-grid">
                @foreach([
                    'gpe_chest' => 'Chest',
                    'gpe_abdomen' => 'Abdomen',
                    'gpe_cvs' => 'CVS',
                    'gpe_cns' => 'CNS',
                    'gpe_pupils' => 'Pupils',
                    'gpe_conjunctiva' => 'Conjunctiva',
                    'gpe_nails' => 'Nails',
                    'gpe_throat' => 'Throat',
                    'gpe_sclera' => 'Sclera',
                    'gpe_gcs' => 'GCS',
                ] as $field => $label)
                    <div class="ipd-form__field">
                        <label for="{{ $field }}" class="ipd-form__label">{{ $label }}</label>
                        <input type="text"
                               name="{{ $field }}"
                               id="{{ $field }}"
                               value="{{ old($field) }}"
                               class="ipd-form__input"
                               placeholder="Enter {{ strtolower($label) }} findings">
                        @error($field)
                            <p class="ipd-form__error">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="ipd-form__field">
                <label for="gpe_remarks" class="ipd-form__label">Remarks <span class="ipd-form__optional">(optional, IPD only)</span></label>
                <textarea name="remarks"
                          id="gpe_remarks"
                          rows="3"
                          class="ipd-form__textarea"
                          placeholder="Additional examination remarks...">{{ old('remarks') }}</textarea>
                @error('remarks')
                    <p class="ipd-form__error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="ipd-btn ipd-btn--primary">
                <i class="fas fa-plus-circle mr-2"></i>Add GPE Record
            </button>
        </form>
    </div>

    <div class="ipd-panel ipd-panel--history">
        <h4 class="ipd-panel__title mb-4">GPE History</h4>
        <div class="ipd-history-list">
            @forelse($visit->ipdGpeRecords as $record)
                <article class="ipd-history-card">
                    <header class="ipd-history-card__header">
                        <div>
                            <h5 class="ipd-history-card__title">{{ $record->created_at->format('M d, Y h:i A') }}</h5>
                            <p class="ipd-history-card__subtitle">
                                @if($record->doctor)
                                    Dr. {{ $record->doctor->name }}
                                @endif
                                @if($record->recordedBy)
                                    <span class="ipd-history-card__author">· recorded by {{ $record->recordedBy->name }}</span>
                                @endif
                            </p>
                        </div>
                    </header>

                    <div class="ipd-gpe-findings">
                        @foreach($record->systemFindings() as $label => $value)
                            @if(filled($value))
                                <div class="ipd-gpe-finding">
                                    <span class="ipd-gpe-finding__label">{{ $label }}</span>
                                    <span class="ipd-gpe-finding__value">{{ $value }}</span>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    @if(filled($record->remarks))
                        <div class="ipd-history-card__remarks">
                            <span class="ipd-history-card__remarks-label">Remarks</span>
                            <p>{{ $record->remarks }}</p>
                        </div>
                    @endif
                </article>
            @empty
                <div class="ipd-empty-state">
                    <i class="fas fa-file-medical"></i>
                    <p>No GPE records yet for this admission.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
