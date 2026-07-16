<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $order = $report['order'];
        $pages = $report['pages'];
        $primaryResult = $report['primaryResult'];
        $comments = $report['comments'] ?? [];
        $settings = [
            'hospital_name' => setting('hospital_name', config('app.name', 'Hospital Management System')),
            'hospital_address' => setting('hospital_address', ''),
            'hospital_phone' => setting('hospital_phone', ''),
            'hospital_email' => setting('hospital_email', ''),
            'hospital_logo' => setting('hospital_logo', null),
        ];
        $visitType = $order->visit->visit_type ?? null;
        $visitLabel = $visitType === 'ipd' ? 'IPD' : ($visitType === 'opd' ? 'OPD' : 'Lab');
    @endphp
    <title>Lab Report - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.35;
            color: #111;
            background: #fff;
        }

        .no-print {
            text-align: center;
            margin: 15px 0;
        }

        .print-btn, .close-btn {
            border: none;
            padding: 10px 25px;
            font-size: 12pt;
            cursor: pointer;
            color: #fff;
        }

        .print-btn { background: #111; margin-right: 10px; }
        .close-btn { background: #666; }

        .report-page {
            width: 210mm;
            min-height: 277mm;
            margin: 0 auto;
            padding: 10mm 12mm 12mm;
            page-break-after: always;
            break-after: page;
        }

        .report-page:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .letterhead {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 10px;
            border-bottom: 2px solid #111;
            margin-bottom: 12px;
        }

        .letterhead-logo {
            width: 64px;
            height: 64px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .letterhead-text { flex: 1; min-width: 0; }

        .hospital-name {
            font-size: 17pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .hospital-meta {
            font-size: 9pt;
            color: #444;
            margin-top: 2px;
        }

        .report-title {
            margin-top: 8px;
            font-size: 12pt;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .patient-box {
            border: 1px solid #111;
            padding: 8px 10px;
            margin-bottom: 12px;
        }

        .patient-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 16px;
        }

        .patient-item { font-size: 9.5pt; }
        .patient-label { font-weight: 700; display: inline-block; min-width: 128px; }

        .test-panel {
            border: 1px solid #111;
            margin-bottom: 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .test-panel-header {
            background: #f3f4f6;
            border-bottom: 1px solid #111;
            padding: 6px 8px;
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            border-top: 1px solid #d1d5db;
            padding: 5px 8px;
            font-size: 9.5pt;
            text-align: left;
            vertical-align: top;
        }

        .results-table th {
            background: #fafafa;
            font-weight: 700;
        }

        .result-abnormal { font-weight: 700; }

        .comments-box,
        .signatures {
            margin-top: 14px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .comments-box {
            border: 1px solid #111;
            padding: 8px 10px;
            font-size: 9.5pt;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 28px;
        }

        .signature-line {
            border-top: 1px solid #111;
            padding-top: 4px;
            margin-top: 42px;
            text-align: center;
            font-size: 9.5pt;
        }

        .empty-state {
            border: 1px solid #111;
            padding: 24px;
            text-align: center;
            color: #555;
        }

        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .report-page {
                width: auto;
                margin: 0;
                padding: 0;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="print-btn">Print Report</button>
        <button onclick="window.close()" class="close-btn">Close</button>
    </div>

    @forelse($pages as $pageIndex => $page)
        <section class="report-page">
            @if($pageIndex === 0)
                <header class="letterhead">
                    @if($settings['hospital_logo'])
                        <img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="{{ $settings['hospital_name'] }}" class="letterhead-logo">
                    @endif
                    <div class="letterhead-text">
                        <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                        @if($settings['hospital_address'])
                            <div class="hospital-meta">{{ $settings['hospital_address'] }}</div>
                        @endif
                        @if($settings['hospital_phone'] || $settings['hospital_email'])
                            <div class="hospital-meta">
                                @if($settings['hospital_phone'])Tel: {{ $settings['hospital_phone'] }}@endif
                                @if($settings['hospital_phone'] && $settings['hospital_email']) · @endif
                                @if($settings['hospital_email']){{ $settings['hospital_email'] }}@endif
                            </div>
                        @endif
                        <div class="report-title">Laboratory Report</div>
                    </div>
                </header>

                <div class="patient-box">
                    <div class="patient-grid">
                        <div class="patient-item">
                            <span class="patient-label">Patient Name:</span>
                            <span>{{ $order->patient->name }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Age / Sex:</span>
                            <span>{{ $order->patient->age }} Years / {{ ucfirst($order->patient->gender) }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Referred By:</span>
                            <span>Dr. {{ $order->doctor->name ?? 'N/A' }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">{{ $visitLabel }} #:</span>
                            <span>{{ $order->patient->patient_no }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Order #:</span>
                            <span>{{ $order->order_number }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Collection:</span>
                            <span>{{ $order->sample_collected_at ? $order->sample_collected_at->format('d M Y, h:i A') : 'Not recorded' }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Reporting:</span>
                            <span>{{ $primaryResult?->reported_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Consultant:</span>
                            <span>{{ $primaryResult?->pathologist?->name ?? 'Pending' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            @foreach($page['sections'] as $section)
                <article class="test-panel">
                    <div class="test-panel-header">{{ $section['investigation']->name }}</div>
                    <table class="results-table">
                        <thead>
                            <tr>
                                <th style="width: 36%;">Parameter</th>
                                <th style="width: 18%;">Result</th>
                                <th style="width: 14%;">Unit</th>
                                <th style="width: 32%;">Reference Range</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($section['items'] as $item)
                                @php
                                    $parameter = $item->parameter;
                                    $referenceRange = $parameter
                                        ? $parameter->getReferenceRange($order->patient->age, $order->patient->gender)
                                        : '-';
                                    $isAbnormal = $item->flag && $item->flag !== 'N';
                                @endphp
                                <tr>
                                    <td>{{ $parameter?->parameter_name ?? 'N/A' }}</td>
                                    <td class="{{ $isAbnormal ? 'result-abnormal' : '' }}">{{ $item->value }}</td>
                                    <td>{{ $item->unit ?? ($parameter?->unit ?? '-') }}</td>
                                    <td>{{ $referenceRange }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </article>
            @endforeach

            @if($loop->last)
                @if(!empty($comments))
                    <div class="comments-box">
                        <strong>Comments</strong>
                        @foreach($comments as $comment)
                            <p style="margin-top: 4px;">{{ $comment }}</p>
                        @endforeach
                    </div>
                @endif

                <div class="signatures">
                    <div>
                        <div class="signature-line">
                            <strong>Lab Technician</strong>
                            @if($primaryResult?->technician)
                                <div>{{ $primaryResult->technician->name }}</div>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="signature-line">
                            <strong>Pathologist / Consultant</strong>
                            @if($primaryResult?->pathologist)
                                <div>{{ $primaryResult->pathologist->name }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </section>
    @empty
        <section class="report-page">
            <div class="empty-state">No laboratory results are available for this order.</div>
        </section>
    @endforelse

    <script>
        if (window.location.search.includes('print=1')) {
            window.onload = function () {
                window.print();
            };
        }
    </script>
</body>
</html>
