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

        /* Page-1 chrome: PHC registration line above a full-width accent band */
        .report-reg-line {
            text-align: right;
            font-size: 8pt;
            color: #555;
            margin-bottom: 1.5mm;
        }

        .report-band {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            width: 100%;
            background: var(--lab-report-accent);
            color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 4mm 5mm;
            margin-bottom: 3mm;
        }

        .report-band-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .report-logo-tile {
            background: #fff;
            border-radius: 6px;
            padding: 4px;
            flex: none;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-logo-tile img {
            width: 64px;
            height: 64px;
            object-fit: contain;
            display: block;
        }

        .band-hospital-name { font-size: 18pt; font-weight: bold; line-height: 1.15; }
        .band-hospital-address { font-size: 9pt; margin-top: 2px; }
        .band-report-caption { font-size: 9pt; font-weight: 700; letter-spacing: 0.12em; margin-top: 3px; }

        .report-band-contact {
            font-size: 9pt;
            text-align: right;
            flex: none;
        }

        .report-band-contact div {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 5px;
        }

        .band-icon {
            width: 3.5mm;
            height: 3.5mm;
            fill: currentColor;
            flex: none;
        }

        .report-band-qr {
            background: #fff;
            padding: 3px;
            border-radius: 4px;
            flex: none;
            width: auto;
            height: auto;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .report-band-qr svg {
            width: 72px;
            height: 72px;
            display: block;
        }

        /* Page-1 patient strip: who | when / where | QR, closed by a 1px accent divider (OQ-7) */
        .patient-strip {
            display: grid;
            grid-template-columns: 1fr 1fr 26mm;
            gap: 2px 14px;
            padding: 0 0 2.5mm;
            margin-bottom: 4mm;
            border-bottom: 1px solid var(--lab-report-accent);
        }

        .patient-strip.no-qr { grid-template-columns: 1fr 1fr; }
        .patient-strip-col { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
        .patient-strip-qr { grid-row: span 2; display: flex; justify-content: flex-end; width: auto; height: auto; }
        .patient-strip-qr svg { width: 90px; height: 90px; display: block; }
        .patient-strip-note { grid-column: 1 / 3; }

        .patient-item { font-size: 9.5pt; }
        .patient-label { font-weight: 700; display: inline-block; min-width: 27mm; }
        /* Middle-column labels ("Registration Location:" ~35.5mm at 9.5pt bold) need a wider shared column. */
        .patient-strip-middle .patient-label { min-width: 38mm; }

        /* Continuation pages (2..M): slim accent strip identifying hospital + patient (OQ-1, no Order #). */
        .running-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            background: var(--lab-report-accent);
            color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            padding: 2mm 4mm;
            margin-bottom: 4mm;
            font-size: 9pt;
        }

        .running-header strong { font-weight: 700; }

        .test-panel {
            border: 1px solid var(--lab-report-accent);
            margin-bottom: 10px;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .test-panel-header {
            background: var(--lab-report-accent);
            color: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
                @php $phcNumber = trim((string) setting('phc_registration_number', '')); @endphp
                @if(($printToggles['show_phc_registration'] ?? true) && $phcNumber !== '')
                    <div class="report-reg-line">PHC Reg. No. {{ $phcNumber }}</div>
                @endif
                <header class="report-band">
                    <div class="report-band-brand">
                        @if(($printToggles['show_logo'] ?? true) && $settings['hospital_logo'])
                            <div class="report-logo-tile"><img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Hospital Logo"></div>
                        @endif
                        <div>
                            <div class="band-hospital-name">{{ $settings['hospital_name'] }}</div>
                            @if(($printToggles['show_hospital_address'] ?? true) && $settings['hospital_address'])
                                <div class="band-hospital-address">{{ $settings['hospital_address'] }}</div>
                            @endif
                            <div class="band-report-caption">LAB REPORT</div>
                        </div>
                    </div>
                    @php
                        $bandPhone = ($printToggles['show_hospital_phone'] ?? true) && $settings['hospital_phone'];
                        $bandEmail = ($printToggles['show_hospital_email'] ?? true) && $settings['hospital_email'];
                        $bandWebsite = ($printToggles['show_hospital_website'] ?? true) && $settings['hospital_website'];
                    @endphp
                    @if($bandPhone || $bandEmail || $bandWebsite)
                        <div class="report-band-contact">
                            @if($bandPhone)
                                <div><svg class="band-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.02-.24c1.12.37 2.33.57 3.57.57a1 1 0 0 1 1 1V20a1 1 0 0 1-1 1A17 17 0 0 1 3 4a1 1 0 0 1 1-1h3.5a1 1 0 0 1 1 1c0 1.25.2 2.45.57 3.57a1 1 0 0 1-.25 1.02l-2.2 2.2z"/></svg><span>{{ $settings['hospital_phone'] }}</span></div>
                            @endif
                            @if($bandEmail)
                                <div><svg class="band-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 4H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg><span>{{ $settings['hospital_email'] }}</span></div>
                            @endif
                            @if($bandWebsite)
                                <div><svg class="band-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zm6.93 6h-2.95a15.65 15.65 0 0 0-1.38-3.56A8.03 8.03 0 0 1 18.93 8zM12 4.04c.83 1.2 1.48 2.53 1.91 3.96h-3.82c.43-1.43 1.08-2.76 1.91-3.96zM4.26 14a8.2 8.2 0 0 1 0-4h3.38a16.5 16.5 0 0 0 0 4H4.26zm.81 2h2.95c.32 1.25.78 2.45 1.38 3.56A7.99 7.99 0 0 1 5.07 16zm2.95-8H5.07a7.99 7.99 0 0 1 4.33-3.56A15.65 15.65 0 0 0 8.02 8zM12 19.96c-.83-1.2-1.48-2.53-1.91-3.96h3.82c-.43 1.43-1.08 2.76-1.91 3.96zM14.34 14H9.66a14.7 14.7 0 0 1 0-4h4.68a14.7 14.7 0 0 1 0 4zm.25 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56zM16.36 14a16.5 16.5 0 0 0 0-4h3.38a8.2 8.2 0 0 1 0 4h-3.38z"/></svg><span>{{ $settings['hospital_website'] }}</span></div>
                            @endif
                        </div>
                    @endif
                    {{-- OQ-6: QR sits on the band's right edge only when the patient band is hidden --}}
                    @if($qrSvg && ! ($printToggles['show_patient_band'] ?? true))
                        <div class="report-band-qr report-qr" data-qr-url="{{ $shareUrl }}">{!! $qrSvg !!}</div>
                    @endif
                </header>

                @if($printToggles['show_patient_band'] ?? true)
                <div @class(['patient-strip', 'no-qr' => ! $qrSvg])>
                    <div class="patient-strip-col patient-strip-left">
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
                            <span class="patient-label">Patient No.:</span>
                            <span>{{ $order->patient->patient_no }}</span>
                        </div>
                        @if(!empty($patientBand['department']))
                            <div class="patient-item">
                                <span class="patient-label">Department:</span>
                                <span>{{ $patientBand['department'] }}</span>
                            </div>
                        @endif
                        @if(!empty($patientBand['consultant']))
                            <div class="patient-item">
                                <span class="patient-label">Consultant:</span>
                                <span>{{ $patientBand['consultant'] }}</span>
                            </div>
                        @endif
                    </div><!-- /left -->
                    <div class="patient-strip-col patient-strip-middle">
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
                            <span class="patient-label">Collection:</span>
                            <span>{{ $order->sample_collected_at ? $order->sample_collected_at->format('d M Y, h:i A') : 'Not recorded' }}</span>
                        </div>
                        <div class="patient-item">
                            <span class="patient-label">Reporting:</span>
                            <span>{{ $primaryResult?->reported_at?->format('d M Y, h:i A') ?? now()->format('d M Y, h:i A') }}</span>
                        </div>
                    </div><!-- /middle -->
                    @if($qrSvg)
                        <div class="patient-strip-qr report-qr" data-qr-url="{{ $shareUrl }}">{!! $qrSvg !!}</div>
                    @endif
                    @if(!empty($patientBand['note']))
                        <div class="patient-strip-note">
                            <div class="patient-item">
                                <span class="patient-label">Note:</span>
                                <span>{{ $patientBand['note'] }}</span>
                            </div>
                        </div>
                    @endif
                </div><!-- /patient-strip -->
                @endif
            @else
                <div class="running-header">
                    <div><strong>{{ $settings['hospital_name'] }}</strong> · LAB REPORT</div>
                    <div>{{ $order->patient->name }} · Patient No. {{ $order->patient->patient_no }}</div>
                </div><!-- /running-header -->
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
