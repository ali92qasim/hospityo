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
            padding: 15mm;
        }

        /* Header Section */
        .header {
            display: flex;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .logo {
            width: 80px;
            height: 80px;
            margin-right: 15px;
            flex-shrink: 0;
        }

        .logo-placeholder {
            width: 80px;
            height: 80px;
            border: 2px solid #2563eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f9ff;
            font-size: 10pt;
            color: #2563eb;
            text-align: center;
            font-weight: bold;
        }

        .hospital-info {
            flex: 1;
        }

        .hospital-name {
            font-size: 20pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 3px;
        }

        .department-name {
            font-size: 12pt;
            color: #4b5563;
            margin-bottom: 2px;
        }

        .hospital-contact {
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
            grid-template-columns: 1.5fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Prescription Section */
        .prescription-section {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 15px;
            min-height: 300px;
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
        .tests-section {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 15px;
        }

        .diagnosis-section {
            min-height: 120px;
        }

        .issues-section {
            min-height: 100px;
        }

        .tests-section {
            min-height: 100px;
        }

        .list-item {
            padding: 5px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 10pt;
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
            padding: 15px;
            margin-bottom: 20px;
            min-height: 80px;
            background: #fffbeb;
        }

        .instructions-text {
            font-size: 10pt;
            line-height: 1.6;
            color: #000;
        }

        /* Footer Section */
        .footer-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding-top: 15px;
            border-top: 2px solid #000;
        }

        .next-visit {
            font-size: 11pt;
        }

        .next-visit-label {
            font-weight: 600;
            color: #374151;
        }

        .next-visit-date {
            font-size: 12pt;
            font-weight: bold;
            color: #1e40af;
            margin-top: 5px;
        }

        .doctor-signature {
            text-align: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 8px;
        }

        .doctor-name {
            font-weight: bold;
            font-size: 11pt;
        }

        .doctor-specialization {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 2px;
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
            <div class="logo">
                <div class="logo-placeholder">
                    LOGO
                </div>
            </div>
            <div class="hospital-info">
                <div class="hospital-name">{{ config('app.name', 'Hospital Management System') }}</div>
                <div class="department-name">
                    @if($visit->doctor && $visit->doctor->department)
                        {{ $visit->doctor->department->name }} Department
                    @else
                        General Medicine
                    @endif
                </div>
                <div class="hospital-contact">
                    Address: [Hospital Address] | Phone: [Contact Number] | Email: [Email Address]
                </div>
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
                    <span class="info-value">{{ $visit->patient->patient_id }}</span>
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
            <!-- Left Column: Prescription -->
            <div class="prescription-section">
                <div class="rx-symbol">℞</div>
                <div class="section-title">Prescription</div>
                
                @if($visit->prescriptions && $visit->prescriptions->count() > 0)
                    @foreach($visit->prescriptions as $prescription)
                        @foreach($prescription->items as $index => $item)
                        <div class="medicine-item">
                            <div class="medicine-name">{{ $index + 1 }}. {{ $item->medicine->name }}</div>
                            <div class="medicine-details">
                                <strong>Dosage:</strong> {{ $item->dosage }} | 
                                <strong>Qty:</strong> {{ $item->quantity }}
                                @if($item->instructions)
                                <br><strong>Instructions:</strong> {{ $item->instructions }}
                                @endif
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                @else
                    <div class="empty-state">No prescription added</div>
                @endif
            </div>

            <!-- Right Column: Diagnosis, Issues, Tests -->
            <div>
                <!-- Diagnosis Section -->
                <div class="diagnosis-section">
                    <div class="section-title">Diagnosis</div>
                    @if($visit->consultation && $visit->consultation->provisional_diagnosis)
                        <div class="list-item">
                            {{ $visit->consultation->provisional_diagnosis }}
                        </div>
                    @else
                        <div class="empty-state">No diagnosis recorded</div>
                    @endif
                </div>

                <!-- Active Issues Section -->
                <div class="issues-section">
                    <div class="section-title">Active Issues</div>
                    @if($visit->consultation && $visit->consultation->chief_complaint)
                        <div class="list-item">
                            • {{ $visit->consultation->chief_complaint }}
                        </div>
                    @endif
                    @if($visit->triage && $visit->triage->chief_complaint)
                        <div class="list-item">
                            • {{ $visit->triage->chief_complaint }}
                            @if($visit->triage->priority_level)
                                <span class="badge badge-warning">{{ strtoupper(str_replace('_', ' ', $visit->triage->priority_level)) }}</span>
                            @endif
                        </div>
                    @endif
                    @if((!$visit->consultation || !$visit->consultation->chief_complaint) && (!$visit->triage || !$visit->triage->chief_complaint))
                        <div class="empty-state">No active issues recorded</div>
                    @endif
                </div>

                <!-- Ordered Tests Section -->
                <div class="tests-section">
                    <div class="section-title">Ordered Tests</div>
                    @if($visit->labOrders && $visit->labOrders->count() > 0)
                        @foreach($visit->labOrders as $labOrder)
                        <div class="list-item">
                            • {{ $labOrder->labTest->name }}
                            @if($labOrder->priority === 'stat')
                                <span class="badge badge-warning">STAT</span>
                            @elseif($labOrder->priority === 'urgent')
                                <span class="badge badge-warning">URGENT</span>
                            @endif
                            @if(in_array($labOrder->status, ['verified', 'reported']))
                                <span class="badge badge-success">Completed</span>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <div class="empty-state">No tests ordered</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Doctor Instructions -->
        <div class="instructions-section">
            <div class="section-title">Doctor's Instructions</div>
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
                <div class="next-visit-label">Next Visit Date:</div>
                <div class="next-visit-date">
                    @if($visit->consultation && $visit->consultation->follow_up_date)
                        {{ $visit->consultation->follow_up_date->format('d F Y') }}
                    @else
                        As needed
                    @endif
                </div>
            </div>

            <!-- Doctor Signature -->
            <div class="doctor-signature">
                <div class="signature-line">
                    @if($visit->doctor)
                        <div class="doctor-name">Dr. {{ $visit->doctor->name }}</div>
                        <div class="doctor-specialization">{{ $visit->doctor->specialization }}</div>
                        @if($visit->doctor->license_number)
                            <div class="doctor-specialization">License: {{ $visit->doctor->license_number }}</div>
                        @endif
                    @else
                        <div class="doctor-name">Doctor's Signature</div>
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
