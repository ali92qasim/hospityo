<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clinical History — {{ $patient->patient_no }}</title>
    <style>
        @page { margin: 18mm 14mm 18mm 14mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.45;
        }
        h1, h2, h3, h4 { margin: 0 0 6px 0; color: #0066CC; }
        h1 { font-size: 16pt; }
        h2 { font-size: 12pt; margin-top: 16px; border-bottom: 1px solid #0066CC; padding-bottom: 3px; }
        h3 { font-size: 11pt; color: #1e40af; margin-top: 10px; }
        h4 { font-size: 10pt; color: #374151; margin-top: 8px; }
        p { margin: 0 0 4px 0; }
        .muted { color: #6b7280; font-size: 8.5pt; }
        .header {
            border-bottom: 2px solid #0066CC;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: top; }
        .logo { max-height: 48px; max-width: 120px; }
        .meta-grid { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .meta-grid td { padding: 3px 6px 3px 0; vertical-align: top; }
        .label { color: #0066CC; font-weight: bold; width: 28%; }
        table.data {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0 10px 0;
            font-size: 9pt;
        }
        table.data th, table.data td {
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
            vertical-align: top;
        }
        table.data th {
            background: #eff6ff;
            color: #1e40af;
            font-weight: bold;
        }
        .section {
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .encounter {
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px dashed #93c5fd;
            page-break-inside: avoid;
        }
        .kv { margin: 2px 0; }
        .kv span { color: #0066CC; }
        .badge {
            display: inline-block;
            background: #eff6ff;
            color: #1e40af;
            border: 1px solid #bfdbfe;
            padding: 1px 6px;
            font-size: 8pt;
            border-radius: 3px;
        }
        .footer {
            margin-top: 18px;
            padding-top: 8px;
            border-top: 1px solid #d1d5db;
            font-size: 8pt;
            color: #6b7280;
        }
        .empty { color: #6b7280; font-style: italic; }
        .pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
@php
    $dash = '—';
    $fmt = function ($value, string $format = 'd M Y h:i A') use ($dash) {
        if (! $value) {
            return $dash;
        }
        try {
            return \Illuminate\Support\Carbon::parse($value)->format($format);
        } catch (\Throwable $e) {
            return $dash;
        }
    };
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width: 70%;">
                @if(!empty($facility['logo_path']))
                    <img class="logo" src="file://{{ str_replace('\\', '/', $facility['logo_path']) }}" alt="">
                @endif
                <h1>{{ $facility['name'] ?? 'Hospital' }}</h1>
                @if(!empty($facility['address']))
                    <p class="muted">{{ $facility['address'] }}</p>
                @endif
                <p class="muted">
                    @if(!empty($facility['phone']))Tel: {{ $facility['phone'] }}@endif
                    @if(!empty($facility['phone']) && !empty($facility['email'])) · @endif
                    @if(!empty($facility['email'])){{ $facility['email'] }}@endif
                </p>
            </td>
            <td style="width: 30%; text-align: right;">
                <h1 style="font-size: 13pt;">Patient Clinical History</h1>
                <p class="muted">Chronological medical record extract</p>
                <p class="muted">Generated: {{ $fmt($generated_at) }}</p>
                @if(!empty($generated_by))
                    <p class="muted">By: {{ $generated_by }}</p>
                @endif
            </td>
        </tr>
    </table>
</div>

<h2>1. Patient identification</h2>
<table class="meta-grid">
    <tr>
        <td class="label">Patient No</td>
        <td>{{ $patient->patient_no ?: $dash }}</td>
        <td class="label">Name</td>
        <td>{{ $patient->name ?: $dash }}</td>
    </tr>
    <tr>
        <td class="label">Gender / Age</td>
        <td>{{ $patient->gender ? ucfirst($patient->gender) : $dash }}{{ $patient->age !== null ? ', '.$patient->age.' years' : '' }}</td>
        <td class="label">Phone</td>
        <td>{{ $patient->phone ?: $dash }}</td>
    </tr>
    <tr>
        <td class="label">Marital status</td>
        <td>{{ $patient->marital_status ? ucfirst(str_replace('_', ' ', $patient->marital_status)) : $dash }}</td>
        <td class="label">Address</td>
        <td>{{ $patient->present_address ?: ($patient->permanent_address ?: $dash) }}</td>
    </tr>
    <tr>
        <td class="label">Emergency contact</td>
        <td colspan="3">
            @if($patient->emergency_name || $patient->emergency_phone)
                {{ $patient->emergency_name ?: $dash }}
                @if($patient->emergency_relation) ({{ $patient->emergency_relation }}) @endif
                · {{ $patient->emergency_phone ?: $dash }}
            @else
                {{ $dash }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="label">Known allergies</td>
        <td colspan="3">
            @if(!empty($allergies))
                {{ implode('; ', $allergies) }}
            @else
                {{ $dash }}
            @endif
        </td>
    </tr>
</table>

<h2>2. Chronological index</h2>
@if(empty($index))
    <p class="empty">No clinical encounters recorded for this patient.</p>
@else
    <table class="data">
        <thead>
            <tr>
                <th style="width: 18%;">Date / time</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 22%;">Reference</th>
                <th>Summary</th>
                <th style="width: 16%;">Author</th>
            </tr>
        </thead>
        <tbody>
            @foreach($index as $row)
                <tr>
                    <td>{{ $fmt($row['at'] ?? null) }}</td>
                    <td><span class="badge">{{ $row['type'] ?? $dash }}</span></td>
                    <td>{{ $row['label'] ?? $dash }}</td>
                    <td>{{ $row['summary'] ?? $dash }}</td>
                    <td>{{ $row['author'] ?? $dash }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<h2>3. Clinical encounters</h2>
@forelse($encounters as $encounter)
    @php
        $visit = $encounter['visit'];
        $consultation = $encounter['consultation'];
    @endphp
    <div class="encounter">
        @if($visit)
            <h3>
                {{ $visit->visit_no ?: 'Visit' }}
                · {{ strtoupper((string) $visit->visit_type) }}
                · {{ $fmt($visit->visit_datetime) }}
            </h3>
            <p class="kv"><span>Status:</span> {{ ucfirst(str_replace('_', ' ', (string) $visit->status)) }}</p>
            <p class="kv"><span>Attending:</span> {{ $encounter['doctor_name'] ?: $dash }}</p>

            @if($encounter['triage'])
                <div class="section">
                    <h4>Triage</h4>
                    <p class="kv"><span>Chief complaint:</span> {{ $encounter['triage']->chief_complaint ?: $dash }}</p>
                    <p class="kv"><span>Priority:</span> {{ $encounter['triage']->priority_level ?: $dash }}</p>
                </div>
            @endif

            @if($consultation)
                <div class="section">
                    <h4>Consultation / plan of care</h4>
                    <p class="kv"><span>Chief complaint:</span> {{ $consultation->chief_complaint ?: ($consultation->presenting_complaints ?: $dash) }}</p>
                    <p class="kv"><span>History:</span></p>
                    <p class="pre">{{ $consultation->history ?: $dash }}</p>
                    <p class="kv"><span>Examination:</span></p>
                    <p class="pre">{{ $consultation->examination ?: $dash }}</p>
                    <p class="kv"><span>Provisional diagnosis:</span> {{ $consultation->provisional_diagnosis ?: $dash }}</p>
                    @php
                        $flags = collect([
                            $consultation->diagnosis_dm ? 'DM' : null,
                            $consultation->diagnosis_htn ? 'HTN' : null,
                            $consultation->diagnosis_ihd ? 'IHD' : null,
                            $consultation->diagnosis_asthma ? 'Asthma' : null,
                        ])->filter()->values();
                    @endphp
                    @if($flags->isNotEmpty())
                        <p class="kv"><span>Diagnosis flags:</span> {{ $flags->implode(', ') }}</p>
                    @endif
                    <p class="kv"><span>Treatment / plan:</span></p>
                    <p class="pre">{{ $consultation->treatment_plan ?: ($consultation->treatment ?: $dash) }}</p>
                    <p class="kv"><span>Follow-up:</span> {{ $consultation->follow_up_instructions ?: $dash }}</p>
                    @if($consultation->next_visit_date)
                        <p class="kv"><span>Next visit:</span> {{ $fmt($consultation->next_visit_date, 'd M Y') }}</p>
                    @endif
                </div>
            @endif

            @if($encounter['vitals']->isNotEmpty())
                <div class="section">
                    <h4>Vital signs</h4>
                    <table class="data">
                        <thead>
                            <tr>
                                <th>Recorded</th>
                                <th>BP</th>
                                <th>Temp</th>
                                <th>Pulse</th>
                                <th>SpO2</th>
                                <th>BSR</th>
                                <th>Wt / Ht</th>
                                <th>By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($encounter['vitals'] as $vital)
                                <tr>
                                    <td>{{ $fmt($vital->created_at) }}</td>
                                    <td>{{ $vital->blood_pressure ?: $dash }}</td>
                                    <td>{{ $vital->temperature ?: $dash }}</td>
                                    <td>{{ $vital->pulse_rate ?: $dash }}</td>
                                    <td>{{ $vital->spo2 ?: $dash }}</td>
                                    <td>{{ $vital->bsr ?: $dash }}</td>
                                    <td>{{ ($vital->weight ?: $dash).' / '.($vital->height ?: $dash) }}</td>
                                    <td>{{ $vital->user?->name ?: $dash }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($encounter['prescriptions']->isNotEmpty())
                <div class="section">
                    <h4>Prescriptions</h4>
                    @foreach($encounter['prescriptions'] as $prescription)
                        <p class="muted">Rx #{{ $prescription->id }} · {{ $fmt($prescription->created_at) }}</p>
                        <table class="data">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($prescription->items as $item)
                                    <tr>
                                        <td>{{ $item->medicine?->name ?: ($item->medicine_name ?? $dash) }}</td>
                                        <td>{{ $item->dosage ?: $dash }}</td>
                                        <td>{{ $item->frequency ?: $dash }}</td>
                                        <td>{{ $item->duration ?: $dash }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="empty">No items</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    @endforeach
                </div>
            @endif

            @if($encounter['lab_orders']->isNotEmpty())
                <div class="section">
                    <h4>Laboratory investigations</h4>
                    @foreach($encounter['lab_orders'] as $order)
                        <p class="kv">
                            <span>{{ $order->order_number ?: 'Lab order' }}:</span>
                            {{ $fmt($order->ordered_at ?: $order->created_at) }}
                            · Status: {{ $order->status ?: $dash }}
                        </p>
                        <p class="muted">
                            Tests:
                            {{ $order->items->map(fn ($i) => $i->labTest?->name)->filter()->implode(', ') ?: $dash }}
                        </p>
                        @if($order->result)
                            <p class="kv"><span>Reported:</span> {{ $fmt($order->result->reported_at ?: $order->result->verified_at) }}</p>
                            @if($order->result->interpretation)
                                <p class="pre">{{ $order->result->interpretation }}</p>
                            @endif
                            @if($order->result->comments)
                                <p class="pre">{{ $order->result->comments }}</p>
                            @endif
                            @if(is_array($order->result->results) && count($order->result->results))
                                <table class="data">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th>Value</th>
                                            <th>Unit / Ref</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->result->results as $key => $value)
                                            @php
                                                if (is_array($value)) {
                                                    $param = $value['name'] ?? $value['parameter'] ?? $key;
                                                    $val = $value['value'] ?? $value['result'] ?? json_encode($value);
                                                    $unit = trim(($value['unit'] ?? '').' '.($value['reference'] ?? $value['ref_range'] ?? ''));
                                                } else {
                                                    $param = is_string($key) ? $key : 'Result';
                                                    $val = $value;
                                                    $unit = '';
                                                }
                                            @endphp
                                            <tr>
                                                <td>{{ $param }}</td>
                                                <td>{{ is_scalar($val) ? $val : json_encode($val) }}</td>
                                                <td>{{ $unit !== '' ? $unit : $dash }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif

            @if($encounter['imaging_orders']->isNotEmpty())
                <div class="section">
                    <h4>Imaging / radiology</h4>
                    @foreach($encounter['imaging_orders'] as $order)
                        <p class="kv">
                            <span>{{ $order->order_number ?: 'Imaging order' }}:</span>
                            {{ $fmt($order->ordered_at ?: $order->created_at) }}
                            · Status: {{ $order->status ?: $dash }}
                        </p>
                        <p class="muted">
                            Studies:
                            {{ $order->items->map(fn ($i) => $i->imagingStudy?->name)->filter()->implode(', ') ?: $dash }}
                        </p>
                        @if($order->report)
                            <p class="kv"><span>Impression:</span></p>
                            <p class="pre">{{ $order->report->impression ?: $dash }}</p>
                            <p class="kv"><span>Report:</span></p>
                            <p class="pre">{{ $order->report->report_text ?: $dash }}</p>
                        @endif
                    @endforeach
                </div>
            @endif

            @if($encounter['progress_notes']->isNotEmpty())
                <div class="section">
                    <h4>Progress notes</h4>
                    <table class="data">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Date / time</th>
                                <th style="width: 18%;">Doctor</th>
                                <th>Notes / orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($encounter['progress_notes'] as $note)
                                <tr>
                                    <td>{{ $fmt($note->visited_at ?: $note->created_at) }}</td>
                                    <td>{{ $note->doctor?->name ?: $dash }}</td>
                                    <td class="pre">{{ trim(($note->notes ?: '')."\n".($note->orders ?: '')) ?: $dash }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if($encounter['admission'])
                <div class="section">
                    <h4>Admission / discharge</h4>
                    <p class="kv"><span>Admitted:</span> {{ $fmt($encounter['admission']->admission_date) }}</p>
                    <p class="kv"><span>Ward / bed:</span>
                        {{ $encounter['admission']->bed?->ward?->name ?: $dash }}
                        / {{ $encounter['admission']->bed?->bed_number ?? $encounter['admission']->bed?->name ?? $dash }}
                    </p>
                    <p class="kv"><span>Admission notes:</span></p>
                    <p class="pre">{{ $encounter['admission']->admission_notes ?: $dash }}</p>
                    <p class="kv"><span>Discharged:</span> {{ $fmt($encounter['admission']->discharge_date) }} · Status: {{ $encounter['admission']->status ?: $dash }}</p>
                    <p class="kv"><span>Discharge summary:</span></p>
                    <p class="pre">{{ $encounter['admission']->discharge_summary ?: ($encounter['admission']->discharge_notes ?: $dash) }}</p>
                </div>
            @endif
        @else
            <h3>Standalone operative record</h3>
        @endif

        @if($encounter['surgeries']->isNotEmpty())
            <div class="section">
                <h4>Operative / procedure notes</h4>
                @foreach($encounter['surgeries'] as $surgery)
                    <p class="kv"><span>Surgery No:</span> {{ $surgery->surgery_number ?: $dash }}</p>
                    <p class="kv"><span>Provider(s):</span> {{ $surgery->doctor?->name ?: $dash }}</p>
                    <p class="kv"><span>Procedure:</span> {{ $surgery->procedure_name ?: $dash }}</p>
                    <p class="kv"><span>Scheduled / actual:</span>
                        {{ $fmt($surgery->scheduled_date, 'd M Y') }}
                        · {{ $fmt($surgery->actual_start_time) }} – {{ $fmt($surgery->actual_end_time) }}
                    </p>
                    <p class="kv"><span>Pre-op Dx:</span> {{ $surgery->pre_op_diagnosis ?: $dash }}</p>
                    <p class="kv"><span>Post-op Dx:</span> {{ $surgery->post_op_diagnosis ?: $dash }}</p>
                    <p class="kv"><span>Findings / notes:</span></p>
                    <p class="pre">{{ $surgery->procedure_notes ?: $dash }}</p>
                    <p class="kv"><span>Complications:</span> {{ $surgery->complications ?: 'None recorded' }}</p>
                    <p class="kv"><span>Anaesthesia:</span> {{ $surgery->anesthesia_type ?: ($surgery->anaesthesiaRecord?->anaesthesia_type ?: $dash) }}</p>
                    @if($surgery->anaesthesiaRecord)
                        <p class="kv"><span>Estimated blood loss:</span>
                            {{ $surgery->anaesthesiaRecord->estimated_blood_loss_ml !== null
                                ? $surgery->anaesthesiaRecord->estimated_blood_loss_ml.' ml'
                                : $dash }}
                        </p>
                        <p class="kv"><span>Condition at end / recovery:</span>
                            {{ $surgery->anaesthesiaRecord->recovery_status ?: $dash }}
                        </p>
                    @endif
                    <p class="kv"><span>Status:</span> {{ $surgery->status ?: $dash }}</p>
                @endforeach
            </div>
        @endif
    </div>
@empty
    <p class="empty">No clinical encounters recorded for this patient.</p>
@endforelse

<div class="footer">
    <p>Computer-generated EMR extract as of {{ $fmt($generated_at) }}. Confidential — authorized access only.</p>
    <p>This document assembles available electronic clinical records for inspection and continuity of care. Blank fields indicate data not captured in the system.</p>
</div>
</body>
</html>
