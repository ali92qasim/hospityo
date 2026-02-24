<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription - {{ $visit->visit_no }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background: white;
        }

        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm;
        }

        /* Header Section */
        .header {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 15px;
            align-items: start;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .hospital-info {
            text-align: left;
        }

        .logo {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo img {
            max-width: 100px;
            max-height: 100px;
            object-fit: contain;
        }

        .doctor-info {
            text-align: right;
        }

        .hospital-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 3px;
        }

        .hospital-contact {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.4;
        }

        .doctor-header-name {
            font-size: 14pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 2px;
        }

        .doctor-credentials {
            font-size: 10pt;
            color: #4b5563;
            margin-bottom: 1px;
        }

        .doctor-header-specialization {
            font-size: 9pt;
            color: #6b7280;
        }
        /* Patient Info Bar */
        .patient-info-bar {
            background: #f3f4f6;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #d1d5db;
        }

        .patient-info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .info-item {
            font-size: 10pt;
        }

        .info-label {
            font-weight: 600;
            color: #374151;
            display: inline-block;
            min-width: 80px;
        }

        .info-value {
            color: #000;
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }

        /* Prescription Section */
        .prescription-section {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
        }

        .gpe-section {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px dashed #d1d5db;
        }

        .gpe-title {
            font-size: 11pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .gpe-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 6px;
        }

        .gpe-item {
            font-size: 9pt;
            padding: 4px;
            background: #f9fafb;
            border-radius: 3px;
        }

        .gpe-label {
            font-weight: 600;
            color: #374151;
            display: inline-block;
            min-width: 70px;
        }

        .gpe-value {
            color: #000;
        }

        .section-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 12px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .rx-symbol {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }

        .medicine-item {
            margin-bottom: 12px;
            padding: 8px;
            background: #f9fafb;
            border-left: 3px solid #2563eb;
            border-radius: 3px;
        }

        .medicine-name {
            font-weight: bold;
            font-size: 11pt;
            color: #000;
        }

        .medicine-details {
            font-size: 10pt;
            color: #4b5563;
            margin-top: 3px;
        }

        /* Right Column Sections */
        .diagnosis-section,
        .issues-section,
        .tests-section,
        .history-section {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 12px;
        }

        .diagnosis-section {
            min-height: 80px;
        }

        .issues-section {
            min-height: 70px;
        }

        .tests-section {
            min-height: 70px;
        }

        .history-section {
            min-height: 70px;
        }

        .vco-label {
            display: inline-block;
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .list-item {
            padding: 5px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 10pt;
            line-height: 1.5;
            word-wrap: break-word;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8pt;
            font-weight: 600;
            margin-left: 5px;
        }

        .badge-primary { background: #dbeafe; color: #1e40af; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }

        /* Instructions Section */
        .instructions-section {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
            min-height: 60px;
            background: #fffbeb;
        }

        .instructions-text {
            font-size: 10pt;
            line-height: 1.6;
            color: #000;
        }

        /* Footer Section */
        .footer-section {
            padding-top: 12px;
            border-top: 2px solid #000;
        }

        .next-visit {
            font-size: 11pt;
            text-align: center;
        }

        .next-visit-label {
            font-weight: 600;
            color: #374151;
            display: block;
            margin-bottom: 5px;
            font-size: 12pt;
        }

        .next-visit-date {
            font-size: 13pt;
            font-weight: bold;
            color: #1e40af;
            display: block;
        }
        }

        /* Print Styles */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }

            .container {
                max-width: 100%;
                padding: 10mm;
            }

            .no-print {
                display: none !important;
            }

            @page {
                margin: 10mm;
            }
        }

        .no-print {
            text-align: center;
            margin: 15px 0;
        }

        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 12pt;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        .close-btn {
            background: #6b7280;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 12pt;
            border-radius: 5px;
            cursor: pointer;
        }

        .close-btn:hover {
            background: #4b5563;
        }

        .empty-state {
            color: #9ca3af;
            font-style: italic;
            font-size: 10pt;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Buttons -->
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">
                <i class="fas fa-print"></i> Print Prescription
            </button>
            <button onclick="window.close()" class="close-btn">
                <i class="fas fa-times"></i> Close
            </button>
        </div>

        <!-- Header -->
        <div class="header">
            <!-- Hospital Info (Left) -->
            <div class="hospital-info">
                <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                @if($settings['hospital_address'])
                    <div class="hospital-contact">{{ $settings['hospital_address'] }}</div>
                @endif
                @if($settings['hospital_phone'])
                    <div class="hospital-contact">Phone: {{ $settings['hospital_phone'] }}</div>
                @endif
                @if($settings['hospital_email'])
                    <div class="hospital-contact">Email: {{ $settings['hospital_email'] }}</div>
                @endif
            </div>

            <!-- Logo (Center) -->
            <div class="logo">
                @if($settings['hospital_logo'])
                    <img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Hospital Logo">
                @endif
            </div>

            <!-- Doctor Info (Right) -->
            <div class="doctor-info">
                @if($visit->doctor)
                    <div class="doctor-header-name">Dr. {{ $visit->doctor->name }}</div>
                    <div class="doctor-credentials">{{ $visit->doctor->qualification }}</div>
                    <div class="doctor-header-specialization">{{ $visit->doctor->specialization }}</div>
                    @if($visit->doctor->pmdc_number)
                        <div class="doctor-header-specialization">PMDC: {{ $visit->doctor->pmdc_number }}</div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Patient Information Bar -->
        <div class="patient-info-bar">
            <div class="patient-info-grid">
                <div class="info-item">
                    <span class="info-label">Patient Name:</span>
                    <span class="info-value">{{ $visit->patient->name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Age/Gender:</span>
                    <span class="info-value">{{ $visit->patient->age }} Years / {{ ucfirst($visit->patient->gender) }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Mobile:</span>
                    <span class="info-value">{{ $visit->patient->phone ?? 'N/A' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Patient No:</span>
                    <span class="info-value">{{ $visit->patient->patient_no }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Visit No:</span>
                    <span class="info-value">{{ $visit->visit_no }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Date & Time:</span>
                    <span class="info-value">{{ $visit->visit_date ? $visit->visit_date->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="content-grid">
            <!-- Left Column: Prescription & GPE -->
            <div class="prescription-section">
                <div class="rx-symbol">℞</div>
                <div class="section-title">Prescription</div>
                
                @if($visit->prescriptions && $visit->prescriptions->count() > 0)
                    @foreach($visit->prescriptions as $prescription)
                        @foreach($prescription->items as $index => $item)
                        <div class="medicine-item">
                            <div class="medicine-name">{{ $index + 1 }}. {{ $item->medicine->name }}</div>
                            @if($item->prescriptionInstruction)
                            <div class="medicine-details" style="color: #1e40af; margin-top: 4px;">
                                {{ $item->prescriptionInstruction->instruction }}
                            </div>
                            @endif
                        </div>
                        @endforeach
                    @endforeach
                @else
                    <div class="empty-state">No prescription added</div>
                @endif

                <!-- GPE Section -->
                @if($visit->consultation && (
                    $visit->consultation->gpe_chest || 
                    $visit->consultation->gpe_abdomen || 
                    $visit->consultation->gpe_cvs || 
                    $visit->consultation->gpe_cns || 
                    $visit->consultation->gpe_pupils || 
                    $visit->consultation->gpe_conjunctiva || 
                    $visit->consultation->gpe_nails || 
                    $visit->consultation->gpe_throat || 
                    $visit->consultation->gpe_sclera || 
                    $visit->consultation->gpe_gcs
                ))
                <div class="gpe-section">
                    <div class="gpe-title">General Physical Examination (GPE)</div>
                    <div class="gpe-grid">
                        @if($visit->consultation->gpe_chest)
                        <div class="gpe-item">
                            <span class="gpe-label">Chest:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_chest }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_abdomen)
                        <div class="gpe-item">
                            <span class="gpe-label">Abdomen:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_abdomen }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_cvs)
                        <div class="gpe-item">
                            <span class="gpe-label">CVS:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_cvs }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_cns)
                        <div class="gpe-item">
                            <span class="gpe-label">CNS:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_cns }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_pupils)
                        <div class="gpe-item">
                            <span class="gpe-label">Pupils:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_pupils }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_conjunctiva)
                        <div class="gpe-item">
                            <span class="gpe-label">Conjunctiva:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_conjunctiva }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_nails)
                        <div class="gpe-item">
                            <span class="gpe-label">Nails:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_nails }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_throat)
                        <div class="gpe-item">
                            <span class="gpe-label">Throat:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_throat }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_sclera)
                        <div class="gpe-item">
                            <span class="gpe-label">Sclera:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_sclera }}</span>
                        </div>
                        @endif
                        @if($visit->consultation->gpe_gcs)
                        <div class="gpe-item">
                            <span class="gpe-label">GCS:</span>
                            <span class="gpe-value">{{ $visit->consultation->gpe_gcs }}</span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Diagnosis, Issues, Tests -->
            <div>
                <!-- Provisional Diagnosis Section -->
                <div class="diagnosis-section">
                    <div class="section-title">Provisional Diagnosis</div>
                    <div class="vco-label">V.C.O</div>
                    @php
                        $hasDiagnosis = false;
                        $diagnoses = [];
                        if($visit->consultation) {
                            if($visit->consultation->diagnosis_dm) {
                                $diagnoses[] = 'DM: ' . $visit->consultation->diagnosis_dm;
                            }
                            if($visit->consultation->diagnosis_htn) {
                                $diagnoses[] = 'HTN: ' . $visit->consultation->diagnosis_htn;
                            }
                            if($visit->consultation->diagnosis_ihd) {
                                $diagnoses[] = 'IHD: ' . $visit->consultation->diagnosis_ihd;
                            }
                            if($visit->consultation->diagnosis_asthma) {
                                $diagnoses[] = 'Asthma: ' . $visit->consultation->diagnosis_asthma;
                            }
                        }
                    @endphp
                    @if($visit->consultation)
                        @if(count($diagnoses) > 0)
                            <div class="list-item">{{ implode(', ', $diagnoses) }}</div>
                            @php $hasDiagnosis = true; @endphp
                        @endif
                        @if($visit->consultation->provisional_diagnosis)
                            <div class="list-item">{{ $visit->consultation->provisional_diagnosis }}</div>
                            @php $hasDiagnosis = true; @endphp
                        @endif
                    @endif
                    @if(!$hasDiagnosis)
                        <div class="empty-state">No diagnosis recorded</div>
                    @endif
                </div>

                <!-- Allergies Section -->
                <div class="diagnosis-section">
                    <div class="section-title">Allergies</div>
                    @if($visit->consultation && ($visit->consultation->allergies->count() > 0 || $visit->consultation->allergy_notes))
                        @if($visit->consultation->allergies->count() > 0)
                            <div class="list-item">
                                <strong>Known Allergies:</strong>
                                {{ $visit->consultation->allergies->pluck('name')->join(', ') }}
                            </div>
                        @endif
                        @if($visit->consultation->allergy_notes)
                            <div class="list-item">
                                <strong>Notes:</strong> {{ $visit->consultation->allergy_notes }}
                            </div>
                        @endif
                    @else
                        <div class="empty-state">No allergies recorded</div>
                    @endif
                </div>

                <!-- Presenting Complaints Section -->
                <div class="issues-section">
                    <div class="section-title">Presenting Complaints</div>
                    @php
                        $hasComplaints = false;
                    @endphp
                    @if($visit->consultation && $visit->consultation->presenting_complaints)
                        <div class="list-item">{{ $visit->consultation->presenting_complaints }}</div>
                        @php $hasComplaints = true; @endphp
                    @endif
                    @if($visit->consultation && $visit->consultation->chief_complaint)
                        <div class="list-item">{{ $visit->consultation->chief_complaint }}</div>
                        @php $hasComplaints = true; @endphp
                    @endif
                    @if($visit->triage && $visit->triage->chief_complaint)
                        <div class="list-item">
                            {{ $visit->triage->chief_complaint }}
                            @if($visit->triage->priority_level)
                                <span class="badge badge-warning">{{ strtoupper(str_replace('_', ' ', $visit->triage->priority_level)) }}</span>
                            @endif
                        </div>
                        @php $hasComplaints = true; @endphp
                    @endif
                    @if(!$hasComplaints)
                        <div class="empty-state">No complaints recorded</div>
                    @endif
                </div>

                <!-- Patient History Section -->
                <div class="history-section">
                    <div class="section-title">Patient History</div>
                    @if($visit->consultation && $visit->consultation->history)
                        <div class="list-item">{{ $visit->consultation->history }}</div>
                    @else
                        <div class="empty-state">No history recorded</div>
                    @endif
                </div>

                <!-- Investigations Section -->
                <div class="tests-section">
                    <div class="section-title">Investigations</div>
                    @if($visit->labOrders && $visit->labOrders->count() > 0)
                        <div style="font-size: 10pt; line-height: 1.6;">
                            @php
                                $investigationNames = $visit->labOrders->map(function($order) {
                                    $name = $order->investigation->name;
                                    // Extract abbreviation from name (text in parentheses or first word)
                                    if (preg_match('/\(([^)]+)\)/', $name, $matches)) {
                                        return $matches[1]; // Return text in parentheses like (CBC)
                                    }
                                    // If no parentheses, extract first meaningful part
                                    $parts = explode(' ', $name);
                                    // For multi-word tests, take first 2-3 words or abbreviation
                                    if (count($parts) > 1) {
                                        // Check if it's an abbreviation pattern (all caps)
                                        foreach ($parts as $part) {
                                            if (strlen($part) <= 5 && strtoupper($part) === $part && ctype_alpha($part)) {
                                                return $part;
                                            }
                                        }
                                        // Otherwise take first word
                                        return $parts[0];
                                    }
                                    return $parts[0];
                                })->unique()->join(', ');
                            @endphp
                            {{ $investigationNames }}
                        </div>
                    @else
                        <div class="empty-state">No investigations ordered</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Doctor Instructions -->
        <div class="instructions-section">
            <div class="section-title">ہدایات</div>
            <div class="instructions-text">
                @if($visit->consultation)
                    @if($visit->consultation->treatment_plan)
                        <strong>Treatment Plan:</strong><br>
                        {{ $visit->consultation->treatment_plan }}
                        <br><br>
                    @endif
                    @if($visit->consultation->follow_up_instructions)
                        <strong>Follow-up Instructions:</strong><br>
                        {{ $visit->consultation->follow_up_instructions }}
                    @endif
                    @if(!$visit->consultation->treatment_plan && !$visit->consultation->follow_up_instructions)
                        <div class="empty-state">No specific instructions provided</div>
                    @endif
                @else
                    <div class="empty-state">No instructions recorded</div>
                @endif
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-section">
            <!-- Next Visit Date -->
            <div class="next-visit">
                <div class="next-visit-label">آئندہ معائنہ کی تاریخ</div>
                <div class="next-visit-date">
                    @if($visit->consultation && $visit->consultation->next_visit_date)
                        {{ \Carbon\Carbon::parse($visit->consultation->next_visit_date)->format('d F Y') }}
                    @else
                        As needed
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when opened with auto parameter
        if (window.location.search.includes('auto=1')) {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>
