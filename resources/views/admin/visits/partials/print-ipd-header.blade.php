{{-- Shared print header for IPD visit report pages --}}
<div class="report-header">
    <div class="header-grid">
        <div class="hospital-info">
            <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
            @if($settings['hospital_address'])<div class="hospital-contact">{{ $settings['hospital_address'] }}</div>@endif
            @if($settings['hospital_phone'])<div class="hospital-contact">Ph: {{ $settings['hospital_phone'] }}</div>@endif
        </div>
        <div class="logo">
            @if($settings['hospital_logo'])<img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Logo">@endif
        </div>
        <div class="doctor-info">
            @php $attendingDoctor = $visit->attendingDoctor(); @endphp
            @if($attendingDoctor)
                <div class="doctor-header-name">Dr. {{ $attendingDoctor->name }}</div>
                <div class="doctor-credentials">{{ $attendingDoctor->qualification }}</div>
                <div class="doctor-header-specialization">{{ $attendingDoctor->specialization }}</div>
                @if($attendingDoctor->pmdc_number)<div class="doctor-header-specialization">PMDC: {{ $attendingDoctor->pmdc_number }}</div>@endif
            @endif
        </div>
    </div>

    <div class="patient-info-bar">
        <div class="patient-info-grid">
            <div class="info-item"><span class="info-label">Patient:</span> {{ $visit->patient->name }}</div>
            <div class="info-item"><span class="info-label">Age/Gender:</span> {{ $visit->patient->age }}Y / {{ ucfirst($visit->patient->gender) }}</div>
            <div class="info-item"><span class="info-label">Mobile:</span> {{ $visit->patient->phone ?? 'N/A' }}</div>
            <div class="info-item"><span class="info-label">Patient #:</span> {{ $visit->patient->patient_no }}</div>
            <div class="info-item"><span class="info-label">Visit #:</span> {{ $visit->visit_no }}</div>
            <div class="info-item"><span class="info-label">Admitted:</span> {{ $visit->visit_datetime?->format('d M Y, h:i A') ?? 'N/A' }}</div>
        </div>
    </div>

    @if(! empty($sectionTitle))
        <div class="section-banner">
            <h1 class="section-banner-title">{{ $sectionTitle }}</h1>
            @if(! empty($sectionSubtitle))
                <p class="section-banner-subtitle">{{ $sectionSubtitle }}</p>
            @endif
        </div>
    @endif
</div>
