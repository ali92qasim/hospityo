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
        $patientBand = $report['patient_band'] ?? [];
        $reviewers = $report['reviewers'] ?? [];
        $pageCount = count($pages);
        $printToggles = \App\Support\LabReportPrintSettings::get();
        $accentColor = $printToggles['accent_color'] ?? \App\Support\LabReportPrintSettings::DEFAULTS['accent_color'];
        $queryAccent = request()->query('accent');
        if (is_string($queryAccent) && preg_match('/^#[0-9A-Fa-f]{6}$/', $queryAccent)) {
            $accentColor = strtoupper($queryAccent);
        }
        $shareUrl = $order->publicReportUrl();
        $qrSvg = ($printToggles['show_qr'] ?? true)
            ? \App\Services\LabReportQrCode::svg($shareUrl)
            : null;
        $settings = [
            'hospital_name' => setting('hospital_name', config('app.name', 'Hospital Management System')),
            'hospital_address' => setting('hospital_address', ''),
            'hospital_phone' => setting('hospital_phone', ''),
            'hospital_email' => setting('hospital_email', ''),
            'hospital_website' => setting('hospital_website', ''),
            'hospital_logo' => setting('hospital_logo', null),
        ];
        $contactParts = [];
        if (($printToggles['show_footer_phone'] ?? false) && filled($settings['hospital_phone'])) {
            $contactParts[] = $settings['hospital_phone'];
        }
        if (($printToggles['show_footer_email'] ?? false) && filled($settings['hospital_email'])) {
            $contactParts[] = $settings['hospital_email'];
        }
        if (($printToggles['show_footer_address'] ?? false) && filled($settings['hospital_address'])) {
            $contactParts[] = $settings['hospital_address'];
        }
        if (($printToggles['show_footer_website'] ?? false) && filled($settings['hospital_website'])) {
            $contactParts[] = $settings['hospital_website'];
        }
    @endphp
    <title>Lab Report - {{ $order->order_number }}</title>
    @include('partials.favicon')
    <style>
        :root { --lab-report-accent: {{ $accentColor }}; }
    </style>
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

        body.embed-preview .no-print {
            display: none !important;
        }

        .print-btn, .close-btn {
            border: none;
            padding: 10px 25px;
            font-size: 12pt;
            cursor: pointer;
            color: #fff;
            border-radius: 8px;
        }

        .print-btn { background: #2563eb; margin-right: 10px; }
        .print-btn:hover { background: #1d4ed8; }
        .close-btn { background: #6b7280; }
        .close-btn:hover { background: #4b5563; }

        .report-page {
            width: 210mm;
            min-height: 277mm;
            margin: 0 auto;
            padding: 10mm 12mm 12mm;
            page-break-after: always;
            break-after: page;
        }

        /* Use :last-of-type — Vite script tags make :last-child fail and force a blank page */
        .report-page:last-of-type {
            page-break-after: auto;
            break-after: auto;
        }

        /* Header: logo left, hospital title center, QR right */
        .header {
            display: grid;
            grid-template-columns: 100px 1fr 100px;
            gap: 20px;
            align-items: start;
            border-bottom: 2px solid var(--lab-report-accent);
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header.header-no-qr {
            grid-template-columns: 100px 1fr;
        }

        .logo {
            width: 100px;
            height: 100px;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .report-qr {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
        }

        .report-qr svg {
            width: 90px;
            height: 90px;
            display: block;
        }

        .hospital-header {
            text-align: center;
        }

        .hospital-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .hospital-address {
            font-size: 9pt;
            margin-bottom: 3px;
        }

        .report-title {
            font-size: 16pt;
            font-weight: bold;
            margin-top: 10px;
            text-decoration: underline;
        }

        .patient-box {
            border: 1px solid var(--lab-report-accent);
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
            border: 1px solid var(--lab-report-accent);
            margin-bottom: 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .test-panel-header {
            background: var(--lab-report-accent);
            color: #fff;
            border-bottom: 1px solid var(--lab-report-accent);
            padding: 6px 8px;
            font-size: 10.5pt;
            font-weight: 700;
            text-transform: uppercase;
            text-align: center;
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

        tr.previous-result td {
            color: #6b7280;
            font-size: 0.85em;
        }

        .result-abnormal-muted {
            color: #c2410c;
            font-weight: 600;
        }

        .comments-box,
        .signatures {
            margin-top: 14px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .comments-box {
            border: 1px solid var(--lab-report-accent);
            padding: 8px 10px;
            font-size: 9.5pt;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr;
            max-width: 280px;
            margin-left: auto;
            margin-top: 28px;
        }

        .signature-line {
            border-top: 1px solid var(--lab-report-accent);
            padding-top: 4px;
            margin-top: 42px;
            text-align: center;
            font-size: 9.5pt;
        }

        .reviewer-blocks {
            display: flex;
            flex-wrap: wrap;
            gap: 16px 28px;
            margin-top: 20px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .reviewer-block {
            min-width: 140px;
            max-width: 200px;
            font-size: 9.5pt;
            line-height: 1.35;
            border: 1px solid var(--lab-report-accent);
            padding: 8px 10px;
        }

        .reviewer-block .reviewer-name {
            font-weight: 700;
        }

        .report-contact {
            margin-top: 14px;
            font-size: 9pt;
            text-align: center;
            color: #222;
        }

        .page-number {
            margin-top: 16px;
            text-align: center;
            font-size: 9pt;
            color: #333;
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
                min-height: 0;
                height: auto;
                margin: 0;
                padding: 0;
                page-break-after: always;
                break-after: page;
            }
            .report-page:last-of-type {
                page-break-after: avoid;
                break-after: avoid;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
    @vite(['resources/js/lab-report-print.js'])
</head>
<body @class(['embed-preview' => ! empty($embed)])>
    <div class="no-print">
        <button type="button" id="lab-report-print-btn" class="print-btn">Print / Save as PDF</button>
        @unless($isPublic ?? false)
            <button type="button" id="lab-report-close-btn" class="close-btn">Close</button>
        @endunless
    </div>

    @forelse($pages as $pageIndex => $page)
        <section class="report-page">
            @if($pageIndex === 0)
                <div class="header {{ $qrSvg ? '' : 'header-no-qr' }}">
                    <div class="logo">
                        @if(($printToggles['show_logo'] ?? true) && $settings['hospital_logo'])
                            <img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Hospital Logo">
                        @endif
                    </div>
                    <div class="hospital-header">
                        <div class="hospital-name">{{ $settings['hospital_name'] }}</div>
                        @if(($printToggles['show_hospital_address'] ?? true) && $settings['hospital_address'])
                            <div class="hospital-address">{{ $settings['hospital_address'] }}</div>
                        @endif
                        @if(($printToggles['show_hospital_phone'] ?? true) && $settings['hospital_phone'])
                            <div class="hospital-address">Phone: {{ $settings['hospital_phone'] }}</div>
                        @endif
                        @if(($printToggles['show_hospital_email'] ?? true) && $settings['hospital_email'])
                            <div class="hospital-address">Email: {{ $settings['hospital_email'] }}</div>
                        @endif
                        @if(($printToggles['show_hospital_website'] ?? true) && $settings['hospital_website'])
                            <div class="hospital-address">Website: {{ $settings['hospital_website'] }}</div>
                        @endif
                        <div class="report-title">LAB REPORT</div>
                    </div>
                    @if($qrSvg)
                        <div class="report-qr" data-qr-url="{{ $shareUrl }}">
                            {!! $qrSvg !!}
                        </div>
                    @endif
                </div>

                @if($printToggles['show_patient_band'] ?? true)
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
                            <span class="patient-label">Registration Location:</span>
                            <span>{{ $patientBand['registration_location'] ?? $settings['hospital_name'] }}</span>
                        </div>
                        @if(!empty($patientBand['registration_date']))
                            <div class="patient-item">
                                <span class="patient-label">Registration Date:</span>
                                <span>{{ $patientBand['registration_date']->format('d M Y, h:i A') }}</span>
                            </div>
                        @endif
                        <div class="patient-item">
                            <span class="patient-label">Referred By:</span>
                            <span>Dr. {{ $order->doctor->name ?? 'N/A' }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Patient No.:</span>
                            <span>{{ $order->patient->patient_no }}</span>
                        </div>
                        @if(!empty($patientBand['department']))
                            <div class="patient-item">
                                <span class="patient-label">Department:</span>
                                <span>{{ $patientBand['department'] }}</span>
                            </div>
                        @endif
                        <div class="patient-item">
                            <span class="patient-label">Collection:</span>
                            <span>{{ $order->sample_collected_at ? $order->sample_collected_at->format('d M Y, h:i A') : 'Not recorded' }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Reporting:</span>
                            <span>{{ $primaryResult?->reported_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</span>
                        </div>
                        @if(!empty($patientBand['consultant']))
                            <div class="patient-item">
                                <span class="patient-label">Consultant:</span>
                                <span>{{ $patientBand['consultant'] }}</span>
                            </div>
                        @endif
                        @if(!empty($patientBand['note']))
                            <div class="patient-item">
                                <span class="patient-label">Note:</span>
                                <span>{{ $patientBand['note'] }}</span>
                            </div>
                        @endif
                    </div>
                </div>
                @endif
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
                                @foreach(($item->previous_values ?? []) as $prior)
                                    @php
                                        $priorAbnormal = ! empty($prior['flag']) && $prior['flag'] !== 'N';
                                        $priorDate = ! empty($prior['tested_at'])
                                            ? \Illuminate\Support\Carbon::parse($prior['tested_at'])->format('d M Y')
                                            : '';
                                    @endphp
                                    <tr class="previous-result">
                                        <td></td>
                                        <td class="{{ $priorAbnormal ? 'result-abnormal-muted' : '' }}">{{ $prior['value'] }}</td>
                                        <td>{{ $prior['unit'] ?? '—' }}</td>
                                        <td>{{ $priorDate }}</td>
                                    </tr>
                                @endforeach
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

                @if(($printToggles['show_reviewers'] ?? true) && count($reviewers) > 0)
                    <div class="reviewer-blocks">
                        @foreach($reviewers as $reviewer)
                            <div class="reviewer-block">
                                <div class="reviewer-name">Dr. {{ $reviewer['name'] }}</div>
                                @if(!empty($reviewer['qualification']))
                                    <div>{{ $reviewer['qualification'] }}</div>
                                @endif
                                @if(!empty($reviewer['specialization']))
                                    <div>{{ $reviewer['specialization'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($primaryResult?->pathologist)
                    <div class="signatures">
                        <div>
                            <div class="signature-line">
                                <strong>Verified By</strong>
                                <div>{{ $primaryResult->pathologist->name }}</div>
                            </div>
                        </div>
                    </div>
                @endif

                @if(count($contactParts) > 0)
                    <div class="report-contact">{{ implode(' · ', $contactParts) }}</div>
                @endif
            @endif

            @if($printToggles['show_page_numbers'] ?? true)
                <div class="page-number">Page {{ $pageIndex + 1 }} of {{ $pageCount }}</div>
            @endif
        </section>
    @empty
        <section class="report-page">
            <div class="empty-state">No laboratory results are available for this order.</div>
        </section>
    @endforelse
</body>
</html>
