<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Report - {{ $labResult->labOrder->order_number }}</title>
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
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }

        .logo {
            margin: 0 auto 15px;
            width: 120px;
            height: 120px;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .hospital-header {
            text-align: center;
        }

        .hospital-name {
            font-size: 22pt;
            font-weight: bold;
            color: #000;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .hospital-address {
            font-size: 10pt;
            color: #333;
            margin-bottom: 4px;
            line-height: 1.6;
        }

        .hospital-contact {
            font-size: 9pt;
            color: #555;
            margin-bottom: 3px;
        }

        .report-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 15px;
            padding: 8px 0;
            border-top: 2px solid #ddd;
            border-bottom: 2px solid #ddd;
            letter-spacing: 2px;
            color: #000;
        }

        /* Patient Details */
        .patient-details {
            margin-bottom: 20px;
            border: 1px solid #000;
            padding: 10px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .detail-item {
            font-size: 10pt;
        }

        .detail-label {
            font-weight: bold;
            display: inline-block;
            min-width: 140px;
        }

        /* Results Table */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .results-table th {
            background: #f0f0f0;
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10pt;
        }

        .results-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 10pt;
        }

        .result-abnormal {
            font-weight: bold;
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
            background: #000;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 12pt;
            cursor: pointer;
            margin-right: 10px;
        }

        .print-btn:hover {
            background: #333;
        }

        .close-btn {
            background: #666;
            color: white;
            border: none;
            padding: 10px 25px;
            font-size: 12pt;
            cursor: pointer;
        }

        .close-btn:hover {
            background: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Print Buttons -->
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">Print Report</button>
            <button onclick="window.close()" class="close-btn">Close</button>
        </div>

        <!-- Header -->
        <div class="header">
            @php
                $settings = [
                    'hospital_name' => cache('settings.hospital_name', config('app.name', 'Hospital Management System')),
                    'hospital_address' => cache('settings.hospital_address', ''),
                    'hospital_phone' => cache('settings.hospital_phone', ''),
                    'hospital_email' => cache('settings.hospital_email', ''),
                    'hospital_logo' => cache('settings.hospital_logo', null)
                ];
            @endphp
            
            <!-- Logo (Centered) -->
            @if($settings['hospital_logo'])
                <div class="logo">
                    <img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Hospital Logo">
                </div>
            @endif

            <!-- Hospital Info (Centered) -->
            <div class="hospital-header">
                <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                
                @if($settings['hospital_address'])
                    <div class="hospital-address">{{ $settings['hospital_address'] }}</div>
                @endif
                
                <div class="hospital-contact">
                    @if($settings['hospital_phone'])
                        <span>Tel: {{ $settings['hospital_phone'] }}</span>
                    @endif
                    @if($settings['hospital_phone'] && $settings['hospital_email'])
                        <span> | </span>
                    @endif
                    @if($settings['hospital_email'])
                        <span>Email: {{ $settings['hospital_email'] }}</span>
                    @endif
                </div>
                
                <div class="report-title">LABORATORY REPORT</div>
            </div>
        </div>

        <!-- Patient Details -->
        <div class="patient-details">
            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-label">Patient Name:</span>
                    <span>{{ $labResult->labOrder->patient->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Age:</span>
                    <span>{{ $labResult->labOrder->patient->age }} Years</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Sex:</span>
                    <span>{{ ucfirst($labResult->labOrder->patient->gender) }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Referred By:</span>
                    <span>Dr. {{ $labResult->labOrder->doctor->name ?? 'N/A' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">{{ $labResult->labOrder->visit->visit_type === 'ipd' ? 'IPD' : ($labResult->labOrder->visit->visit_type === 'opd' ? 'OPD' : 'Lab') }} #:</span>
                    <span>{{ $labResult->labOrder->patient->patient_no }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Collection Date & Time:</span>
                    <span>{{ $labResult->labOrder->sample_collected_at ? $labResult->labOrder->sample_collected_at->format('d M Y, h:i A') : 'Not recorded' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Reporting Date & Time:</span>
                    <span>{{ $labResult->reported_at ? $labResult->reported_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Consultant Name:</span>
                    <span>{{ $labResult->pathologist ? $labResult->pathologist->name : 'Pending' }}</span>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        @if($labResult->resultItems && $labResult->resultItems->count() > 0)
            <table class="results-table">
                <thead>
                    <tr>
                        <th style="width: 35%;">Test Name</th>
                        <th style="width: 20%;">Result</th>
                        <th style="width: 15%;">Unit</th>
                        <th style="width: 30%;">Reference Range</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($labResult->resultItems as $item)
                        @php
                            $parameter = $item->parameter;
                            $isAbnormal = false;
                            
                            // Get reference range
                            $referenceRange = '-';
                            if ($parameter) {
                                $referenceRange = $parameter->getReferenceRange(
                                    $labResult->labOrder->patient->age,
                                    $labResult->labOrder->patient->gender
                                );
                            }
                            
                            // Check if result is abnormal based on flag
                            if ($item->flag && $item->flag !== 'N') {
                                $isAbnormal = true;
                            }
                        @endphp
                        <tr>
                            <td>{{ $parameter ? $parameter->parameter_name : 'N/A' }}</td>
                            <td class="{{ $isAbnormal ? 'result-abnormal' : '' }}">{{ $item->value }}</td>
                            <td>{{ $item->unit ?? ($parameter ? $parameter->unit : '-') }}</td>
                            <td>{{ $referenceRange }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div style="padding: 20px; border: 1px solid #000; text-align: center;">
                <p>No detailed results available</p>
            </div>
        @endif

        <!-- Comments -->
        @if($labResult->comments)
            <div style="margin-top: 20px; padding: 10px; border: 1px solid #000;">
                <strong>Comments:</strong>
                <p style="margin-top: 5px;">{{ $labResult->comments }}</p>
            </div>
        @endif

        <!-- Signatures -->
        <div style="margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div style="text-align: center;">
                <div style="border-top: 1px solid #000; padding-top: 5px; margin-top: 50px;">
                    <strong>Lab Technician</strong>
                    @if($labResult->technician)
                        <div>{{ $labResult->technician->name }}</div>
                    @endif
                </div>
            </div>
            <div style="text-align: center;">
                <div style="border-top: 1px solid #000; padding-top: 5px; margin-top: 50px;">
                    <strong>Pathologist/Consultant</strong>
                    @if($labResult->pathologist)
                        <div>{{ $labResult->pathologist->name }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when opened with print parameter
        if (window.location.search.includes('print=1')) {
            window.onload = function() {
                window.print();
            };
        }
    </script>
</body>
</html>
