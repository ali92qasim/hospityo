<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - {{ $visit->visit_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 8.5pt; line-height: 1.25; color: #000; background: white; }

        /* ── Page: exactly 960px = 10 inches ── */
        .page { width: 750px; height: 960px; margin: 0 auto; padding: 8px 12px; display: flex; flex-direction: column; overflow: hidden; }

        /* ── Header: 75px ── */
        .header { height: 75px; display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; align-items: start; border-bottom: 2px solid #000; padding-bottom: 4px; }
        .hospital-info { text-align: left; }
        .logo { display: flex; justify-content: center; align-items: center; }
        .logo img { max-width: 70px; max-height: 70px; object-fit: contain; }
        .doctor-info { text-align: right; }
        .hospital-name { font-size: 12pt; font-weight: bold; color: #1e40af; }
        .hospital-contact { font-size: 9pt; color: #6b7280; line-height: 1.2; }
        .doctor-header-name { font-size: 12pt; font-weight: bold; color: #1e40af; }
        .doctor-credentials { font-size: 9pt; color: #4b5563; }
        .doctor-header-specialization { font-size: 9pt; color: #6b7280; }

        /* ── Patient Info: 45px ── */
        .patient-info-bar { height: 45px; background: #f3f4f6; padding: 4px 8px; border-radius: 3px; margin: 5px 0; border: 1px solid #d1d5db; overflow: hidden; }
        .patient-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
        .info-item { font-size: 9pt; }
        .info-label { font-weight: 600; color: #374151; display: inline-block; min-width: 62px; }

        /* ── Content Grid: 720px (flex: 1 fills remaining) ── */
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; flex: 1; margin: 4px 0; min-height: 0; }

        /* Left: Prescription */
        .prescription-section { border: 1px solid #d1d5db; border-radius: 3px; padding: 5px 6px; display: flex; flex-direction: column; overflow: hidden; }

        /* Right column */
        .right-column { display: flex; flex-direction: column; overflow: hidden; }
        .right-section { border: 1px solid #d1d5db; border-radius: 3px; padding: 4px 6px; margin-bottom: 3px; flex: 1; overflow: hidden; }

        .section-title { font-size: 10pt; font-weight: bold; color: #000; padding: 5px 0; }
        .rx-symbol { font-size: 14pt; font-weight: bold; color: #000; margin-bottom: 2px; }
        .vco-label { display: inline-block; color: #000; padding: 5px; font-size: 9pt; font-weight: 600;  }

        .medicine-item { margin-bottom: 2px; padding: 2px 4px; background: #f9fafb; border-left: 2px solid #2563eb; border-radius: 2px; display: flex; justify-content: space-between; align-items: flex-start; }
        .medicine-info { flex: 1; }
        .medicine-name { font-weight: bold; font-size: 10pt; }
        .medicine-qty { font-size: 8pt; font-weight: 600; white-space: nowrap; padding-left: 8px; min-width: 30px; text-align: right; }
        .medicine-details { font-size: 10pt; padding: 2px; }

        .gpe-section { margin-top: 4px; padding: 5px; border-top: 1px dashed #d1d5db; }
        .gpe-title { font-size: 10pt; font-weight: bold; color: #1e40af; padding: 5px 0; }
        .gpe-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; }
        .gpe-item { font-size: 9pt; padding: 1px 3px; background: #f9fafb; border-radius: 2px; }
        .gpe-label { font-weight: 600; color: #374151; display: inline-block; min-width: 45px; }

        .list-item { padding: 1px 0; border-bottom: 1px dashed #e5e7eb; font-size: 9pt; line-height: 1.3; }
        .list-item:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 8px; font-size: 7pt; font-weight: 600; margin-left: 3px; }
        .badge-warning { background: #fef3c7; color: #92400e; }

        /* Writable blank space */
        .writable-fill { flex: 1; }
        .writable-lines .line { border-bottom: 1px dotted #d1d5db; height: 16px; }

        /* ── Instructions: 60px ── */
        .instructions-section { height: 100px; border: 1px solid #d1d5db; border-radius: 3px; padding: 4px 6px; overflow: hidden; }
        .section-urdu { font-size: 20px; display: flex; justify-content: flex-end; padding: 5px}
        /* ── Footer: 35px ── */
        .footer-section { height: 35px; padding-top: 4px; border-top: 2px solid #000; }
        .next-visit { display: flex; align-items: center; gap: 6px; direction: rtl; }

        .next-visit-label { font-weight: 600; color: #374151; font-size: 12pt; padding: 5px; white-space: nowrap; }

        .next-visit-date { font-size: 9.5pt; font-weight: bold; color: #1e40af; direction: ltr; white-space: nowrap; }

        @media print {
            body { margin: 0; padding: 0; }
            .page { width: 100%; height: 100vh; padding: 4px 10px; }
            .no-print { display: none !important; }
            @page { size: A4; margin: 5mm; }
        }
        .no-print { text-align: center; margin: 10px 0; }
        .print-btn { background: #2563eb; color: white; border: none; padding: 8px 20px; font-size: 11pt; border-radius: 5px; cursor: pointer; margin-right: 8px; }
        .close-btn { background: #6b7280; color: white; border: none; padding: 8px 20px; font-size: 11pt; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="page">
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">Print Prescription</button>
            <button onclick="window.close()" class="close-btn">Close</button>
        </div>

        <!-- HEADER: 75px -->
        <div class="header">
            <div class="hospital-info">
                <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                @if($settings['hospital_address'])<div class="hospital-contact">{{ $settings['hospital_address'] }}</div>@endif
                @if($settings['hospital_phone'])<div class="hospital-contact">Ph: {{ $settings['hospital_phone'] }}</div>@endif
            </div>
            <div class="logo">
                @if($settings['hospital_logo'])<img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Logo">@endif
            </div>
            <div class="doctor-info">
                @if($visit->doctor)
                    <div class="doctor-header-name">Dr. {{ $visit->doctor->name }}</div>
                    <div class="doctor-credentials">{{ $visit->doctor->qualification }}</div>
                    <div class="doctor-header-specialization">{{ $visit->doctor->specialization }}</div>
                    @if($visit->doctor->pmdc_number)<div class="doctor-header-specialization">PMDC: {{ $visit->doctor->pmdc_number }}</div>@endif
                @endif
            </div>
        </div>

        <!-- PATIENT INFO: 45px -->
        <div class="patient-info-bar">
            <div class="patient-info-grid">
                <div class="info-item"><span class="info-label">Patient:</span> {{ $visit->patient->name }}</div>
                <div class="info-item"><span class="info-label">Age/Gender:</span> {{ $visit->patient->age }}Y / {{ ucfirst($visit->patient->gender) }}</div>
                <div class="info-item"><span class="info-label">Mobile:</span> {{ $visit->patient->phone ?? 'N/A' }}</div>
                <div class="info-item"><span class="info-label">Patient #:</span> {{ $visit->patient->patient_no }}</div>
                <div class="info-item"><span class="info-label">Visit #:</span> {{ $visit->visit_no }}</div>
                <div class="info-item"><span class="info-label">Date:</span> {{ $visit->visit_datetime ? $visit->visit_datetime->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</div>
            </div>
        </div>

        @php
            $hasPrescription = $visit->prescriptions && $visit->prescriptions->count() > 0;
            $c = $visit->consultation;
            $hasDiagnosis = $c && $c->provisional_diagnosis;
            $hasComplaints = ($c && ($c->presenting_complaints || $c->chief_complaint)) || ($visit->triage && $visit->triage->chief_complaint);
            $conditions = [];
            if ($c) {
                if ($c->diagnosis_dm) $conditions[] = 'DM: ' . $c->diagnosis_dm;
                if ($c->diagnosis_htn) $conditions[] = 'HTN: ' . $c->diagnosis_htn;
                if ($c->diagnosis_ihd) $conditions[] = 'IHD: ' . $c->diagnosis_ihd;
                if ($c->diagnosis_asthma) $conditions[] = 'Asthma: ' . $c->diagnosis_asthma;
            }
            $hasHistory = count($conditions) > 0 || ($c && $c->history);
            $hasTests = $visit->labOrders && $visit->labOrders->count() > 0;
            $hasGpe = $c && ($c->gpe_chest || $c->gpe_abdomen || $c->gpe_cvs || $c->gpe_cns || $c->gpe_pupils || $c->gpe_conjunctiva || $c->gpe_nails || $c->gpe_throat || $c->gpe_sclera || $c->gpe_gcs);
            $hasAllergies = $c && ($c->allergies->count() > 0 || $c->allergy_notes);
            $hasInstructions = $c && ($c->treatment_plan || $c->follow_up_instructions);
        @endphp

        <!-- CONTENT GRID: fills remaining ~720px -->
        <div class="content-grid">
            <!-- LEFT: Prescription -->
            <div class="prescription-section">
                <div class="rx-symbol">℞</div>
                <div class="section-title">Prescription</div>
                @if($hasPrescription)
                    @foreach($visit->prescriptions as $prescription)
                        @foreach($prescription->items as $i => $item)
                        <div class="medicine-item">
                            <div class="medicine-info">
                                <div class="medicine-name">{{ $i + 1 }}. {{ $item->medicine->name }}</div>
                                @if($item->prescriptionInstruction)
                                <div class="medicine-details">{{ $item->prescriptionInstruction->instruction }}</div>
                                @endif
                            </div>
                            @if($item->quantity && $item->quantity > 1)
                            <div class="medicine-qty"> {{ $item->quantity }}</div>
                            @endif
                        </div>
                        @endforeach
                    @endforeach
                @endif
                {{-- Always leave remaining space for handwriting --}}
                <div class="writable-fill"></div>

                {{-- Vitals --}}
                @if($visit->vitalSigns)
                <div class="gpe-section">
                    <div class="gpe-title">Vital Signs</div>
                    <div class="gpe-grid">
                        @if($visit->vitalSigns->blood_pressure)<div class="gpe-item"><span class="gpe-label">BP:</span> {{ $visit->vitalSigns->blood_pressure }}</div>@endif
                        @if($visit->vitalSigns->temperature)<div class="gpe-item"><span class="gpe-label">Temp:</span> {{ $visit->vitalSigns->temperature }}°F</div>@endif
                        @if($visit->vitalSigns->pulse_rate)<div class="gpe-item"><span class="gpe-label">Pulse:</span> {{ $visit->vitalSigns->pulse_rate }} bpm</div>@endif
                        @if($visit->vitalSigns->spo2)<div class="gpe-item"><span class="gpe-label">SpO₂:</span> {{ $visit->vitalSigns->spo2 }}%</div>@endif
                        @if($visit->vitalSigns->bsr)<div class="gpe-item"><span class="gpe-label">BSR:</span> {{ $visit->vitalSigns->bsr }}</div>@endif
                        @if($visit->vitalSigns->weight)<div class="gpe-item"><span class="gpe-label">Weight:</span> {{ $visit->vitalSigns->weight }} kg</div>@endif
                        @if($visit->vitalSigns->height)<div class="gpe-item"><span class="gpe-label">Height:</span> {{ $visit->vitalSigns->height }} ft</div>@endif
                    </div>
                </div>
                @endif

                @if($hasGpe)
                <div class="gpe-section">
                    <div class="gpe-title">GPE</div>
                    <div class="gpe-grid">
                        @foreach(['chest'=>'Chest','abdomen'=>'Abd','cvs'=>'CVS','cns'=>'CNS','pupils'=>'Pupils','conjunctiva'=>'Conj','nails'=>'Nails','throat'=>'Throat','sclera'=>'Sclera','gcs'=>'GCS'] as $f => $l)
                            @if($c->{'gpe_'.$f})
                            <div class="gpe-item"><span class="gpe-label">{{ $l }}:</span> {{ $c->{'gpe_'.$f} }}</div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- RIGHT: Diagnosis, Allergies, Complaints, History, Investigations -->
            <div class="right-column">
                <div class="vco-label">V.C.O</div>

                <div class="right-section">
                    <div class="section-title">Provisional Diagnosis</div>
                    @if($hasDiagnosis)
                        <div class="list-item">{{ $c->provisional_diagnosis }}</div>
                    @else
                        <div class="writable-lines"><div class="line"></div><div class="line"></div></div>
                    @endif
                </div>

                <div class="right-section">
                    <div class="section-title">Allergies</div>
                    @if($hasAllergies)
                        @if($c->allergies->count() > 0)<div class="list-item">{{ $c->allergies->pluck('name')->join(', ') }}</div>@endif
                        @if($c->allergy_notes)<div class="list-item">{{ $c->allergy_notes }}</div>@endif
                    @else
                        <div class="writable-lines"><div class="line"></div></div>
                    @endif
                </div>

                <div class="right-section">
                    <div class="section-title">Presenting Complaints</div>
                    @if($hasComplaints)
                        @if($c && $c->presenting_complaints)<div class="list-item">{{ $c->presenting_complaints }}</div>@endif
                        @if($c && $c->chief_complaint)<div class="list-item">{{ $c->chief_complaint }}</div>@endif
                        @if($visit->triage && $visit->triage->chief_complaint)<div class="list-item">{{ $visit->triage->chief_complaint }}</div>@endif
                    @else
                        <div class="writable-lines"><div class="line"></div><div class="line"></div></div>
                    @endif
                </div>

                <div class="right-section">
                    <div class="section-title">Patient History</div>
                    @if($hasHistory)
                        @if(count($conditions) > 0)<div class="list-item">{{ implode(', ', $conditions) }}</div>@endif
                        @if($c && $c->history)<div class="list-item">{{ $c->history }}</div>@endif
                    @else
                        <div class="writable-lines"><div class="line"></div><div class="line"></div></div>
                    @endif
                </div>

                <div class="right-section">
                    <div class="section-title">Investigations</div>
                    @if($hasTests)
                        <div style="font-size: 8.5pt;">
                        @php
                            echo $visit->labOrders->map(fn($o) => preg_match('/\(([^)]+)\)/', $o->investigation->name, $m) ? $m[1] : explode(' ', $o->investigation->name)[0])->unique()->join(', ');
                        @endphp
                        </div>
                    @else
                        <div class="writable-lines"><div class="line"></div><div class="line"></div></div>
                    @endif
                </div>
            </div>
        </div>

        <!-- INSTRUCTIONS: 60px -->
        <div class="instructions-section">
            <div class="section-title section-urdu">ہدایات</div>
            @if($hasInstructions)
                <div style="font-size: 7.5pt;">
                    @if($c->treatment_plan){{ $c->treatment_plan }}@endif
                    @if($c->follow_up_instructions) | {{ $c->follow_up_instructions }}@endif
                </div>
            @else
                <div class="writable-lines"><div class="line"></div><div class="line"></div></div>
            @endif
        </div>

        <!-- FOOTER: 35px -->
        <div class="footer-section">
            <div class="next-visit">
                <span class="next-visit-label">آئندہ معائنہ کی تاریخ: </span>
                <span class="next-visit-date">
                    @if($c && $c->next_visit_date)
                        {{ \Carbon\Carbon::parse($c->next_visit_date)->format('d F Y') }}
                    @else
                        ___________________
                    @endif
                </span>
            </div>
        </div>
    </div>

    <script>
        if (window.location.search.includes('auto=1')) { window.onload = function() { window.print(); }; }
    </script>
</body>
</html>
