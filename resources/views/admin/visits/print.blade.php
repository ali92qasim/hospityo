<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - {{ $visit->visit_no }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; font-size: 8.5pt; line-height: 1.25; color: #000; background: white; }
        .container { max-width: 210mm; height: 287mm; margin: 0 auto; padding: 4mm 8mm; display: flex; flex-direction: column; overflow: hidden; }

        /* Header */
        .header { display: grid; grid-template-columns: 1fr auto 1fr; gap: 8px; align-items: start; border-bottom: 2px solid #000; padding-bottom: 4px; margin-bottom: 5px; }
        .hospital-info { text-align: left; }
        .logo { display: flex; justify-content: center; align-items: center; }
        .logo img { max-width: 60px; max-height: 60px; object-fit: contain; }
        .doctor-info { text-align: right; }
        .hospital-name { font-size: 11pt; font-weight: bold; color: #1e40af; margin-bottom: 1px; }
        .hospital-contact { font-size: 7.5pt; color: #6b7280; line-height: 1.2; }
        .doctor-header-name { font-size: 11pt; font-weight: bold; color: #1e40af; margin-bottom: 1px; }
        .doctor-credentials { font-size: 8pt; color: #4b5563; }
        .doctor-header-specialization { font-size: 7.5pt; color: #6b7280; }

        /* Patient Info Bar */
        .patient-info-bar { background: #f3f4f6; padding: 4px 8px; border-radius: 3px; margin-bottom: 5px; border: 1px solid #d1d5db; }
        .patient-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
        .info-item { font-size: 8pt; }
        .info-label { font-weight: 600; color: #374151; display: inline-block; min-width: 65px; }
        .info-value { color: #000; }

        /* Main Content — fills remaining page */
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; margin-bottom: 4px; flex: 1; min-height: 0; }

        /* Sections */
        .prescription-section { border: 1px solid #d1d5db; border-radius: 3px; padding: 5px 6px; display: flex; flex-direction: column; min-height: 0; }
        .right-column { display: flex; flex-direction: column; min-height: 0; }
        .diagnosis-section, .issues-section, .tests-section, .history-section { border: 1px solid #d1d5db; border-radius: 3px; padding: 4px 6px; margin-bottom: 4px; flex: 1; min-height: 0; }
        .instructions-section { border: 1px solid #d1d5db; border-radius: 3px; padding: 4px 6px; margin-bottom: 4px; background: #fffbeb; }

        .section-title { font-size: 8.5pt; font-weight: bold; color: #1e40af; margin-bottom: 3px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }
        .rx-symbol { font-size: 16pt; font-weight: bold; color: #2563eb; margin-bottom: 2px; }
        .vco-label { display: inline-block; background: #dbeafe; color: #1e40af; padding: 1px 6px; border-radius: 3px; font-size: 7.5pt; font-weight: 600; margin-bottom: 4px; }

        /* Medicine items */
        .medicine-item { margin-bottom: 3px; padding: 3px 5px; background: #f9fafb; border-left: 2px solid #2563eb; border-radius: 2px; }
        .medicine-name { font-weight: bold; font-size: 8.5pt; color: #000; }
        .medicine-details { font-size: 8pt; color: #4b5563; margin-top: 1px; }

        /* GPE */
        .gpe-section { margin-top: 5px; padding-top: 4px; border-top: 1px dashed #d1d5db; }
        .gpe-title { font-size: 8.5pt; font-weight: bold; color: #1e40af; margin-bottom: 3px; }
        .gpe-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2px; }
        .gpe-item { font-size: 7.5pt; padding: 1px 3px; background: #f9fafb; border-radius: 2px; }
        .gpe-label { font-weight: 600; color: #374151; display: inline-block; min-width: 50px; }

        .list-item { padding: 1px 0; border-bottom: 1px dashed #e5e7eb; font-size: 8pt; line-height: 1.3; word-wrap: break-word; }
        .list-item:last-child { border-bottom: none; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7.5pt; font-weight: 600; margin-left: 4px; }
        .badge-warning { background: #fef3c7; color: #92400e; }

        /* Writable blank area — prescription gets extra space */
        .writable-area { flex: 1; min-height: 80px; }
        .writable-lines { min-height: 20px; }
        .writable-lines .line { border-bottom: 1px dotted #e5e7eb; height: 18px; }

        /* Footer */
        .footer-section { padding-top: 4px; border-top: 2px solid #000; margin-top: auto; }
        .next-visit { font-size: 8.5pt; text-align: center; }
        .next-visit-label { font-weight: 600; color: #374151; display: block; margin-bottom: 1px; font-size: 9pt; }
        .next-visit-date { font-size: 10pt; font-weight: bold; color: #1e40af; display: block; }

        @media print {
            body { margin: 0; padding: 0; }
            .container { max-width: 100%; height: auto; max-height: 287mm; padding: 4mm 7mm; }
            .no-print { display: none !important; }
            @page { size: A4; margin: 5mm; }
        }
        .no-print { text-align: center; margin: 10px 0; }
        .print-btn { background: #2563eb; color: white; border: none; padding: 8px 20px; font-size: 11pt; border-radius: 5px; cursor: pointer; margin-right: 8px; }
        .print-btn:hover { background: #1d4ed8; }
        .close-btn { background: #6b7280; color: white; border: none; padding: 8px 20px; font-size: 11pt; border-radius: 5px; cursor: pointer; }
        .close-btn:hover { background: #4b5563; }
    </style>
</head>
<body>
    <div class="container">
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">Print Prescription</button>
            <button onclick="window.close()" class="close-btn">Close</button>
        </div>

        <!-- Header -->
        <div class="header">
            <div class="hospital-info">
                <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                @if($settings['hospital_address'])<div class="hospital-contact">{{ $settings['hospital_address'] }}</div>@endif
                @if($settings['hospital_phone'])<div class="hospital-contact">Phone: {{ $settings['hospital_phone'] }}</div>@endif
                @if($settings['hospital_email'])<div class="hospital-contact">Email: {{ $settings['hospital_email'] }}</div>@endif
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

        <!-- Patient Info -->
        <div class="patient-info-bar">
            <div class="patient-info-grid">
                <div class="info-item"><span class="info-label">Patient Name:</span> <span class="info-value">{{ $visit->patient->name }}</span></div>
                <div class="info-item"><span class="info-label">Age/Gender:</span> <span class="info-value">{{ $visit->patient->age }} Years / {{ ucfirst($visit->patient->gender) }}</span></div>
                <div class="info-item"><span class="info-label">Mobile:</span> <span class="info-value">{{ $visit->patient->phone ?? 'N/A' }}</span></div>
                <div class="info-item"><span class="info-label">Patient No:</span> <span class="info-value">{{ $visit->patient->patient_no }}</span></div>
                <div class="info-item"><span class="info-label">Visit No:</span> <span class="info-value">{{ $visit->visit_no }}</span></div>
                <div class="info-item"><span class="info-label">Date & Time:</span> <span class="info-value">{{ $visit->visit_datetime ? $visit->visit_datetime->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</span></div>
            </div>
        </div>

        @php
            $hasPrescription = $visit->prescriptions && $visit->prescriptions->count() > 0;
            $hasConsultation = $visit->consultation;
            $hasDiagnosis = $hasConsultation && $visit->consultation->provisional_diagnosis;
            $hasComplaints = ($hasConsultation && ($visit->consultation->presenting_complaints || $visit->consultation->chief_complaint)) || ($visit->triage && $visit->triage->chief_complaint);
            $hasHistory = false;
            $conditions = [];
            if ($hasConsultation) {
                if ($visit->consultation->diagnosis_dm) $conditions[] = 'DM: ' . $visit->consultation->diagnosis_dm;
                if ($visit->consultation->diagnosis_htn) $conditions[] = 'HTN: ' . $visit->consultation->diagnosis_htn;
                if ($visit->consultation->diagnosis_ihd) $conditions[] = 'IHD: ' . $visit->consultation->diagnosis_ihd;
                if ($visit->consultation->diagnosis_asthma) $conditions[] = 'Asthma: ' . $visit->consultation->diagnosis_asthma;
                if (count($conditions) > 0 || $visit->consultation->history) $hasHistory = true;
            }
            $hasTests = $visit->labOrders && $visit->labOrders->count() > 0;
            $hasGpe = $hasConsultation && ($visit->consultation->gpe_chest || $visit->consultation->gpe_abdomen || $visit->consultation->gpe_cvs || $visit->consultation->gpe_cns || $visit->consultation->gpe_pupils || $visit->consultation->gpe_conjunctiva || $visit->consultation->gpe_nails || $visit->consultation->gpe_throat || $visit->consultation->gpe_sclera || $visit->consultation->gpe_gcs);
            $hasAllergies = $hasConsultation && ($visit->consultation->allergies->count() > 0 || $visit->consultation->allergy_notes);
        @endphp

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Left Column: Prescription -->
            <div class="prescription-section">
                <div class="rx-symbol">℞</div>
                <div class="section-title">Prescription</div>

                @if($hasPrescription)
                    @foreach($visit->prescriptions as $prescription)
                        @foreach($prescription->items as $index => $item)
                        <div class="medicine-item">
                            <div class="medicine-name">{{ $index + 1 }}. {{ $item->medicine->name }}</div>
                            @if($item->medicine->sku)
                            <div class="medicine-details" style="color: #6b7280;">SKU: {{ $item->medicine->sku }}</div>
                            @endif
                            @if($item->prescriptionInstruction)
                            <div class="medicine-details" style="color: #1e40af;">{{ $item->prescriptionInstruction->instruction }}</div>
                            @endif
                        </div>
                        @endforeach
                    @endforeach
                    {{-- Extra space below filled prescriptions for doctor to add more --}}
                    <div style="flex: 1; min-height: 40px;"></div>
                @else
                    {{-- Empty writable area for doctor to write prescriptions manually --}}
                    <div class="writable-area"></div>
                @endif

                @if($hasGpe)
                <div class="gpe-section">
                    <div class="gpe-title">General Physical Examination (GPE)</div>
                    <div class="gpe-grid">
                        @foreach(['chest' => 'Chest', 'abdomen' => 'Abdomen', 'cvs' => 'CVS', 'cns' => 'CNS', 'pupils' => 'Pupils', 'conjunctiva' => 'Conjunctiva', 'nails' => 'Nails', 'throat' => 'Throat', 'sclera' => 'Sclera', 'gcs' => 'GCS'] as $field => $label)
                            @php $val = $visit->consultation->{'gpe_' . $field}; @endphp
                            @if($val)
                            <div class="gpe-item"><span class="gpe-label">{{ $label }}:</span> <span>{{ $val }}</span></div>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <div class="vco-label">V.C.O</div>

                <!-- Provisional Diagnosis -->
                <div class="diagnosis-section">
                    <div class="section-title">Provisional Diagnosis</div>
                    @if($hasDiagnosis)
                        <div class="list-item">{{ $visit->consultation->provisional_diagnosis }}</div>
                    @else
                        <div class="writable-lines">
                            <div class="line"></div><div class="line"></div>
                        </div>
                    @endif
                </div>

                <!-- Allergies -->
                <div class="diagnosis-section">
                    <div class="section-title">Allergies</div>
                    @if($hasAllergies)
                        @if($visit->consultation->allergies->count() > 0)
                            <div class="list-item"><strong>Known:</strong> {{ $visit->consultation->allergies->pluck('name')->join(', ') }}</div>
                        @endif
                        @if($visit->consultation->allergy_notes)
                            <div class="list-item"><strong>Notes:</strong> {{ $visit->consultation->allergy_notes }}</div>
                        @endif
                    @else
                        <div class="writable-lines"><div class="line"></div><div class="line"></div></div>
                    @endif
                </div>

                <!-- Presenting Complaints -->
                <div class="issues-section">
                    <div class="section-title">Presenting Complaints</div>
                    @if($hasComplaints)
                        @if($hasConsultation && $visit->consultation->presenting_complaints)
                            <div class="list-item">{{ $visit->consultation->presenting_complaints }}</div>
                        @endif
                        @if($hasConsultation && $visit->consultation->chief_complaint)
                            <div class="list-item">{{ $visit->consultation->chief_complaint }}</div>
                        @endif
                        @if($visit->triage && $visit->triage->chief_complaint)
                            <div class="list-item">{{ $visit->triage->chief_complaint }}
                                @if($visit->triage->priority_level)<span class="badge badge-warning">{{ strtoupper(str_replace('_', ' ', $visit->triage->priority_level)) }}</span>@endif
                            </div>
                        @endif
                    @else
                        <div class="writable-lines">
                            <div class="line"></div><div class="line"></div>
                        </div>
                    @endif
                </div>

                <!-- Patient History -->
                <div class="history-section">
                    <div class="section-title">Patient History</div>
                    @if($hasHistory)
                        @if(count($conditions) > 0)
                            <div class="list-item"><strong>Conditions:</strong> {{ implode(', ', $conditions) }}</div>
                        @endif
                        @if($visit->consultation->history)
                            <div class="list-item">{{ $visit->consultation->history }}</div>
                        @endif
                    @else
                        <div class="writable-lines">
                            <div class="line"></div><div class="line"></div>
                        </div>
                    @endif
                </div>

                <!-- Investigations -->
                <div class="tests-section">
                    <div class="section-title">Investigations</div>
                    @if($hasTests)
                        <div style="font-size: 10pt; line-height: 1.6;">
                            @php
                                $investigationNames = $visit->labOrders->map(function($order) {
                                    $name = $order->investigation->name;
                                    if (preg_match('/\(([^)]+)\)/', $name, $matches)) return $matches[1];
                                    $parts = explode(' ', $name);
                                    if (count($parts) > 1) {
                                        foreach ($parts as $part) { if (strlen($part) <= 5 && strtoupper($part) === $part && ctype_alpha($part)) return $part; }
                                        return $parts[0];
                                    }
                                    return $parts[0];
                                })->unique()->join(', ');
                            @endphp
                            {{ $investigationNames }}
                        </div>
                    @else
                        <div class="writable-lines">
                            <div class="line"></div><div class="line"></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions-section">
            <div class="section-title">ہدایات</div>
            <div style="font-size: 8.5pt; line-height: 1.4;">
                @if($hasConsultation && ($visit->consultation->treatment_plan || $visit->consultation->follow_up_instructions))
                    @if($visit->consultation->treatment_plan)
                        <strong>Treatment Plan:</strong><br>{{ $visit->consultation->treatment_plan }}<br><br>
                    @endif
                    @if($visit->consultation->follow_up_instructions)
                        <strong>Follow-up:</strong><br>{{ $visit->consultation->follow_up_instructions }}
                    @endif
                @else
                    <div class="writable-lines">
                        <div class="line"></div><div class="line"></div><div class="line"></div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <div class="next-visit">
                <div class="next-visit-label">آئندہ معائنہ کی تاریخ</div>
                <div class="next-visit-date">
                    @if($hasConsultation && $visit->consultation->next_visit_date)
                        {{ \Carbon\Carbon::parse($visit->consultation->next_visit_date)->format('d F Y') }}
                    @else
                        ___________________
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        if (window.location.search.includes('auto=1')) {
            window.onload = function() { window.print(); };
        }
    </script>
</body>
</html>
