<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IPD Report - {{ $visit->visit_no }}</title>
    @include('partials.favicon')
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; line-height: 1.35; color: #111; background: #e5e7eb; }

        .report-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 16px;
            padding: 10mm 12mm;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
        }

        .report-header { margin-bottom: 8px; }
        .header-grid {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: start;
            border-bottom: 2px solid #000;
            padding-bottom: 6px;
        }
        .hospital-info { text-align: left; }
        .logo { display: flex; justify-content: center; align-items: center; }
        .logo img { max-width: 70px; max-height: 70px; object-fit: contain; }
        .doctor-info { text-align: right; }
        .hospital-name, .doctor-header-name { font-size: 12pt; font-weight: bold; color: #1e40af; }
        .hospital-contact, .doctor-credentials, .doctor-header-specialization { font-size: 8.5pt; color: #4b5563; }

        .patient-info-bar {
            background: #f3f4f6;
            padding: 6px 8px;
            border-radius: 3px;
            margin-top: 6px;
            border: 1px solid #d1d5db;
        }
        .patient-info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3px; }
        .info-item { font-size: 8.5pt; }
        .info-label { font-weight: 600; color: #374151; display: inline-block; min-width: 62px; }

        .section-banner {
            margin-top: 10px;
            padding: 8px 10px;
            background: #1e40af;
            color: #fff;
            border-radius: 4px;
        }
        .section-banner-title { font-size: 13pt; font-weight: bold; }
        .section-banner-subtitle { font-size: 8.5pt; margin-top: 2px; opacity: 0.9; }

        .report-body { flex: 1; margin-top: 8px; }
        .block { margin-bottom: 10px; }
        .block-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1e40af;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-bottom: 6px;
        }
        .text-block { white-space: pre-line; font-size: 9pt; }
        .muted { color: #6b7280; font-size: 8pt; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 8.5pt; }
        .data-table th, .data-table td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        .data-table th { background: #f3f4f6; font-weight: 600; }

        .gpe-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 4px; }
        .gpe-item { font-size: 8.5pt; padding: 3px 5px; background: #f9fafb; border-radius: 2px; border-left: 2px solid #2563eb; }
        .gpe-label { font-weight: 600; color: #374151; }

        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 7.5pt;
            font-weight: 600;
        }
        .badge-primary { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-done { background: #dbeafe; color: #1e40af; }
        .badge-cancelled { background: #f3f4f6; color: #4b5563; }

        .medicine-item {
            margin-bottom: 4px;
            padding: 4px 6px;
            background: #f9fafb;
            border-left: 3px solid #2563eb;
            border-radius: 2px;
        }
        .medicine-name { font-weight: bold; font-size: 9.5pt; }
        .medicine-details { font-size: 8.5pt; color: #374151; }

        .result-flag-H, .result-flag-HH { color: #dc2626; font-weight: bold; }
        .result-flag-L, .result-flag-LL { color: #ea580c; font-weight: bold; }

        .page-footer {
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 7.5pt;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        }

        .no-print { text-align: center; margin: 12px 0; }
        .print-btn, .close-btn {
            border: none;
            padding: 8px 20px;
            font-size: 11pt;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 4px;
        }
        .print-btn { background: #2563eb; color: #fff; }
        .close-btn { background: #6b7280; color: #fff; }

        @media print {
            body { background: #fff; margin: 0; }
            .no-print { display: none !important; }
            .report-page {
                width: 100%;
                min-height: auto;
                margin: 0;
                padding: 8mm 10mm;
                box-shadow: none;
                page-break-after: always;
            }
            .report-page:last-child { page-break-after: auto; }
            @page { size: A4; margin: 8mm; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" class="print-btn">Print IPD Report</button>
        <button type="button" onclick="window.close()" class="close-btn">Close</button>
    </div>

    @php
        $c = $visit->consultation;
        $admission = $visit->admission;
    @endphp

    {{-- PAGE 1: Admission & clinical summary --}}
    <section class="report-page">
        @include('admin.visits.partials.print-ipd-header', [
            'sectionTitle' => 'IPD Admission Summary',
            'sectionSubtitle' => $visit->visit_no . ' · ' . strtoupper($visit->status),
        ])

        <div class="report-body">
            @if($admission)
                <div class="block">
                    <div class="block-title">Admission Details</div>
                    <table class="data-table">
                        <tr>
                            <th>Ward / Bed</th>
                            <th>Admission Date</th>
                            <th>Discharge Date</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <td>{{ $admission->bed?->ward?->name ?? 'N/A' }} · Bed {{ $admission->bed?->bed_number ?? 'N/A' }}</td>
                            <td>{{ $admission->admission_date?->format('d M Y, h:i A') ?? 'N/A' }}</td>
                            <td>{{ $admission->discharge_date?->format('d M Y, h:i A') ?? '—' }}</td>
                            <td>{{ ucfirst($admission->status ?? 'active') }}</td>
                        </tr>
                    </table>
                    @if(filled($admission->admission_notes))
                        <p class="text-block" style="margin-top:6px;"><strong>Notes:</strong> {{ $admission->admission_notes }}</p>
                    @endif
                </div>
            @endif

            @if($visit->careTeam->isNotEmpty())
                <div class="block">
                    <div class="block-title">Care Team</div>
                    <table class="data-table">
                        <tr>
                            <th>Doctor</th>
                            <th>Specialization</th>
                            <th>Role</th>
                            <th>Added</th>
                        </tr>
                        @foreach($visit->careTeam as $member)
                            <tr>
                                <td>Dr. {{ $member->doctor->name }}</td>
                                <td>{{ $member->doctor->specialization }}</td>
                                <td>
                                    @if($member->is_primary)
                                        <span class="badge badge-primary">Primary</span>
                                    @else
                                        Team member
                                    @endif
                                </td>
                                <td>{{ $member->added_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if($visit->allVitalSigns->isNotEmpty())
                <div class="block">
                    <div class="block-title">Vital Signs History</div>
                    <table class="data-table">
                        <tr>
                            <th>Recorded</th>
                            <th>BP</th>
                            <th>Temp</th>
                            <th>Pulse</th>
                            <th>SpO₂</th>
                            <th>BSR</th>
                            <th>Weight</th>
                            <th>By</th>
                        </tr>
                        @foreach($visit->allVitalSigns as $vital)
                            <tr>
                                <td>{{ $vital->created_at->format('d M Y, h:i A') }}</td>
                                <td>{{ $vital->blood_pressure ?? '—' }}</td>
                                <td>{{ $vital->temperature ? $vital->temperature . '°F' : '—' }}</td>
                                <td>{{ $vital->pulse_rate ? $vital->pulse_rate . ' bpm' : '—' }}</td>
                                <td>{{ $vital->spo2 ? $vital->spo2 . '%' : '—' }}</td>
                                <td>{{ $vital->bsr ?? '—' }}</td>
                                <td>{{ $vital->weight ? $vital->weight . ' kg' : '—' }}</td>
                                <td>{{ $vital->user?->name ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endif

            @if($c)
                <div class="block">
                    <div class="block-title">Consultation Summary</div>
                    @if(filled($c->provisional_diagnosis))
                        <p class="text-block"><strong>Provisional Diagnosis:</strong> {{ $c->provisional_diagnosis }}</p>
                    @endif
                    @if(filled($c->presenting_complaints))
                        <p class="text-block" style="margin-top:4px;"><strong>Presenting Complaints:</strong> {{ $c->presenting_complaints }}</p>
                    @endif
                    @if(filled($c->history))
                        <p class="text-block" style="margin-top:4px;"><strong>History:</strong> {{ $c->history }}</p>
                    @endif
                    @if($c->allergies?->isNotEmpty() || filled($c->allergy_notes))
                        <p class="text-block" style="margin-top:4px;">
                            <strong>Allergies:</strong>
                            {{ $c->allergies?->pluck('name')->join(', ') }}
                            @if(filled($c->allergy_notes)) · {{ $c->allergy_notes }} @endif
                        </p>
                    @endif
                    @if(filled($c->treatment_plan) || filled($c->follow_up_instructions))
                        <p class="text-block" style="margin-top:4px;">
                            <strong>Plan / Instructions:</strong>
                            {{ $c->treatment_plan }}
                            @if(filled($c->follow_up_instructions)) · {{ $c->follow_up_instructions }} @endif
                        </p>
                    @endif
                </div>
            @endif

            @if($visit->prescriptions->isNotEmpty())
                <div class="block">
                    <div class="block-title">Prescriptions (Summary)</div>
                    @foreach($visit->prescriptions as $prescription)
                        <p class="muted" style="margin-bottom:4px;">
                            Prescription #{{ $prescription->id }}
                            · {{ $prescription->created_at->format('d M Y, h:i A') }}
                            · Dr. {{ $prescription->doctor?->name ?? 'N/A' }}
                            · {{ ucfirst($prescription->status) }}
                        </p>
                        @foreach($prescription->items as $i => $item)
                            <div class="medicine-item">
                                <div class="medicine-name">{{ $i + 1 }}. {{ $item->medicine->name }}</div>
                                @if($item->prescriptionInstruction)
                                    <div class="medicine-details">{{ $item->prescriptionInstruction->instruction }}</div>
                                @endif
                                @if($item->quantity && $item->quantity > 1)
                                    <div class="medicine-details">Qty: {{ $item->quantity }}</div>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>
            @endif
        </div>

        <div class="page-footer">
            <span>IPD Clinical Report</span>
            <span>Page 1 · Summary</span>
        </div>
    </section>

    {{-- GPE records: one page each --}}
    @foreach($visit->ipdGpeRecords as $index => $gpe)
        @if($gpe->hasAnyFinding())
            <section class="report-page">
                @include('admin.visits.partials.print-ipd-header', [
                    'sectionTitle' => 'General Physical Examination',
                    'sectionSubtitle' => 'Record ' . ($index + 1) . ' of ' . $visit->ipdGpeRecords->count()
                        . ' · ' . $gpe->created_at->format('d M Y, h:i A'),
                ])

                <div class="report-body">
                    <div class="block">
                        <p class="muted">
                            Examining doctor:
                            <strong>{{ $gpe->doctor ? 'Dr. ' . $gpe->doctor->name : 'N/A' }}</strong>
                            @if($gpe->recordedBy)
                                · Recorded by {{ $gpe->recordedBy->name }}
                            @endif
                        </p>
                    </div>

                    <div class="block">
                        <div class="block-title">System Findings</div>
                        <div class="gpe-grid">
                            @foreach($gpe->systemFindings() as $label => $value)
                                @if(filled($value))
                                    <div class="gpe-item"><span class="gpe-label">{{ $label }}:</span> {{ $value }}</div>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    @if(filled($gpe->remarks))
                        <div class="block">
                            <div class="block-title">Remarks</div>
                            <p class="text-block">{{ $gpe->remarks }}</p>
                        </div>
                    @endif
                </div>

                <div class="page-footer">
                    <span>GPE · {{ $gpe->created_at->format('d M Y, h:i A') }}</span>
                    <span>Page {{ $index + 2 }} · GPE {{ $index + 1 }}</span>
                </div>
            </section>
        @endif
    @endforeach

    {{-- Doctor visit notes: one page each --}}
    @foreach($visit->doctorVisitNotes as $index => $note)
        <section class="report-page">
            @include('admin.visits.partials.print-ipd-header', [
                'sectionTitle' => 'Doctor Visit Note',
                'sectionSubtitle' => 'Note ' . ($index + 1) . ' of ' . $visit->doctorVisitNotes->count()
                    . ' · Visited ' . $note->visited_at->format('d M Y, h:i A'),
            ])

            <div class="report-body">
                <div class="block">
                    <table class="data-table">
                        <tr>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Recorded By</th>
                            <th>Visit Time (auto-captured)</th>
                        </tr>
                        <tr>
                            <td>Dr. {{ $note->doctor?->name ?? 'N/A' }}</td>
                            <td><span class="badge badge-{{ $note->status === 'completed' ? 'done' : ($note->status === 'cancelled' ? 'cancelled' : 'pending') }}">{{ ucfirst($note->status) }}</span></td>
                            <td>{{ $note->createdBy?->name ?? 'N/A' }}</td>
                            <td>{{ $note->visited_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    </table>
                </div>

                @if(filled($note->notes))
                    <div class="block">
                        <div class="block-title">Visit Notes</div>
                        <p class="text-block">{{ $note->notes }}</p>
                    </div>
                @endif

                @if(filled($note->orders))
                    <div class="block">
                        <div class="block-title">Orders</div>
                        <p class="text-block">{{ $note->orders }}</p>
                    </div>
                @endif
            </div>

            <div class="page-footer">
                <span>Dr. {{ $note->doctor?->name ?? 'Unknown' }}</span>
                <span>Doctor Visit Note {{ $index + 1 }}</span>
            </div>
        </section>
    @endforeach

    {{-- Investigation orders: one page each --}}
    @foreach($visit->labOrders as $orderIndex => $order)
        @php
            $labResult = $order->items->first()?->result ?? $order->results->first();
        @endphp
        <section class="report-page">
            @include('admin.visits.partials.print-ipd-header', [
                'sectionTitle' => 'Investigation Order',
                'sectionSubtitle' => ($order->order_number ?? ('Order #' . $order->id))
                    . ' · ' . ($order->ordered_at?->format('d M Y, h:i A') ?? 'N/A'),
            ])

            <div class="report-body">
                <div class="block">
                    <table class="data-table">
                        <tr>
                            <th>Order #</th>
                            <th>Ordering Doctor</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Ordered At</th>
                        </tr>
                        <tr>
                            <td>{{ $order->order_number ?? $order->id }}</td>
                            <td>Dr. {{ $order->doctor?->name ?? 'N/A' }}</td>
                            <td>{{ strtoupper($order->priority ?? 'routine') }}</td>
                            <td>{{ ucfirst($order->status ?? 'ordered') }}</td>
                            <td>{{ $order->ordered_at?->format('d M Y, h:i A') ?? 'N/A' }}</td>
                        </tr>
                    </table>
                    @if(filled($order->clinical_notes))
                        <p class="text-block" style="margin-top:6px;"><strong>Clinical Notes:</strong> {{ $order->clinical_notes }}</p>
                    @endif
                    @if(filled($order->special_instructions))
                        <p class="text-block" style="margin-top:4px;"><strong>Special Instructions:</strong> {{ $order->special_instructions }}</p>
                    @endif
                </div>

                <div class="block">
                    <div class="block-title">Ordered Investigations</div>
                    <table class="data-table">
                        <tr>
                            <th>Investigation</th>
                            <th>Category</th>
                            <th>Qty</th>
                            <th>Priority</th>
                            <th>Item Status</th>
                            <th>Clinical Notes</th>
                        </tr>
                        @foreach($order->items as $item)
                            <tr>
                                <td>{{ $item->investigation?->name ?? 'N/A' }}</td>
                                <td>{{ ucfirst(str_replace('-', ' ', $item->investigation?->category ?? 'N/A')) }}</td>
                                <td>{{ $item->quantity ?? 1 }}</td>
                                <td>{{ strtoupper($item->priority ?? 'routine') }}</td>
                                <td>{{ ucfirst($item->status ?? 'ordered') }}</td>
                                <td>{{ $item->clinical_notes ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>

                <div class="block">
                    <div class="block-title">Results</div>
                    @if($labResult && $labResult->resultItems && $labResult->resultItems->isNotEmpty())
                        <table class="data-table">
                            <tr>
                                <th>Parameter</th>
                                <th>Result</th>
                                <th>Unit</th>
                                <th>Flag</th>
                                <th>Reference</th>
                            </tr>
                            @foreach($labResult->resultItems as $resultItem)
                                @php
                                    $param = $resultItem->parameter;
                                    $refRange = $param
                                        ? $param->getReferenceRange($visit->patient->age, $visit->patient->gender)
                                        : '—';
                                    if (is_array($refRange)) {
                                        $refRange = json_encode($refRange);
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $param?->parameter_name ?? 'Parameter' }}</td>
                                    <td class="{{ $resultItem->flag ? 'result-flag-' . $resultItem->flag : '' }}">{{ $resultItem->value ?? '—' }}</td>
                                    <td>{{ $resultItem->unit ?? $param?->unit ?? '—' }}</td>
                                    <td>{{ $resultItem->flag ?? '—' }}</td>
                                    <td>{{ $refRange ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </table>
                        @if(filled($labResult->interpretation))
                            <p class="text-block" style="margin-top:6px;"><strong>Interpretation:</strong> {{ $labResult->interpretation }}</p>
                        @endif
                        @if(filled($labResult->comments))
                            <p class="text-block" style="margin-top:4px;"><strong>Comments:</strong> {{ $labResult->comments }}</p>
                        @endif
                        <p class="muted" style="margin-top:4px;">
                            Reported: {{ $labResult->reported_at?->format('d M Y, h:i A') ?? 'N/A' }}
                            · Status: {{ ucfirst($labResult->status ?? 'pending') }}
                        </p>
                    @elseif($order->radiologyResult)
                        @php $rad = $order->radiologyResult; @endphp
                        @if(filled($rad->report_text))
                            <p class="text-block"><strong>Report:</strong> {{ $rad->report_text }}</p>
                        @endif
                        @if(filled($rad->impression))
                            <p class="text-block" style="margin-top:4px;"><strong>Impression:</strong> {{ $rad->impression }}</p>
                        @endif
                        <p class="muted" style="margin-top:4px;">
                            Reported: {{ $rad->reported_at?->format('d M Y, h:i A') ?? 'N/A' }}
                            · Status: {{ ucfirst($rad->status ?? 'draft') }}
                        </p>
                    @else
                        <p class="muted">No results recorded for this order yet.</p>
                    @endif
                </div>
            </div>

            <div class="page-footer">
                <span>{{ $order->order_number ?? 'Order #' . $order->id }}</span>
                <span>Investigation Order {{ $orderIndex + 1 }}</span>
            </div>
        </section>
    @endforeach

    @if($visit->ipdGpeRecords->filter(fn ($g) => $g->hasAnyFinding())->isEmpty()
        && $visit->doctorVisitNotes->isEmpty()
        && $visit->labOrders->isEmpty())
        <section class="report-page">
            @include('admin.visits.partials.print-ipd-header', [
                'sectionTitle' => 'Additional Records',
                'sectionSubtitle' => 'No GPE records, visit notes, or investigation orders on file',
            ])
            <div class="report-body">
                <p class="muted">This admission summary is complete. No separate GPE, doctor visit note, or investigation pages are attached.</p>
            </div>
        </section>
    @endif

    <script>
        if (window.location.search.includes('auto=1')) {
            window.onload = function () { window.print(); };
        }
    </script>
</body>
</html>
