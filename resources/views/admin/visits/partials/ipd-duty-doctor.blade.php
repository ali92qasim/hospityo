<div class="ipd-panel ipd-panel--duty-doctor">
    <div class="ipd-panel__header">
        <h4 class="ipd-panel__title">
            <i class="fas fa-user-md text-purple-600 mr-2"></i>Duty Doctor
        </h4>
        @if($visit->dutyDoctor)
            <span class="ipd-panel__badge ipd-panel__badge--success">Assigned</span>
        @else
            <span class="ipd-panel__badge ipd-panel__badge--warning">Required</span>
        @endif
    </div>

    @if($visit->dutyDoctor)
        <div class="ipd-duty-doctor-card">
            <div>
                <p class="ipd-duty-doctor-card__name">Dr. {{ $visit->dutyDoctor->name }}</p>
                <p class="ipd-duty-doctor-card__meta">{{ $visit->dutyDoctor->specialization }}</p>
                @if($visit->dutyDoctor->department)
                    <p class="ipd-duty-doctor-card__meta">{{ $visit->dutyDoctor->department->name }}</p>
                @endif
            </div>
            <span class="ipd-duty-doctor-card__label">On duty for this visit</span>
        </div>
    @else
        <div class="ipd-alert ipd-alert--warning">
            <i class="fas fa-exclamation-circle mr-2"></i>
            Assign a duty doctor for this IPD visit before proceeding with clinical documentation.
        </div>
    @endif

    <form action="{{ route('visits.duty-doctor', $visit) }}" method="POST" class="ipd-form ipd-form--inline">
        @csrf
        <div class="ipd-form__field">
            <label for="duty_doctor_id" class="ipd-form__label">
                {{ $visit->dutyDoctor ? 'Change Duty Doctor' : 'Assign Duty Doctor' }}
            </label>
            <select name="duty_doctor_id" id="duty_doctor_id" class="ipd-form__select" required>
                <option value="">Select duty doctor</option>
                @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected(old('duty_doctor_id', $visit->duty_doctor_id) == $doctor->id)>
                        Dr. {{ $doctor->name }} — {{ $doctor->specialization }}
                    </option>
                @endforeach
            </select>
            @error('duty_doctor_id')
                <p class="ipd-form__error">{{ $message }}</p>
            @enderror
        </div>
        <button type="submit" class="ipd-btn ipd-btn--primary">
            <i class="fas fa-save mr-2"></i>{{ $visit->dutyDoctor ? 'Update Duty Doctor' : 'Assign Duty Doctor' }}
        </button>
    </form>
</div>
