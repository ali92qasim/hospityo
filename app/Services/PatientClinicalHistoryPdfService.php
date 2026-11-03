<?php

namespace App\Services;

use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class PatientClinicalHistoryPdfService
{
    /**
     * Build and render the patient clinical history PDF.
     * Returns binary PDF contents, or null on failure.
     */
    public function render(Patient $patient, ?User $generatedBy = null): ?string
    {
        try {
            $payload = $this->buildPayload($patient, $generatedBy);

            return Pdf::loadView('pdf.patient-clinical-history', $payload)
                ->setPaper('a4', 'portrait')
                ->output();
        } catch (\Throwable $e) {
            Log::error('[PatientClinicalHistory] PDF render failed', [
                'patient_id' => $patient->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(Patient $patient, ?User $generatedBy = null): array
    {
        $patient->loadMissing([
            'visits' => fn ($q) => $q->orderBy('visit_datetime')->orderBy('id'),
            'visits.doctor',
            'visits.primaryDoctor.doctor',
            'visits.consultation.allergies',
            'visits.triage',
            'visits.allVitalSigns.user',
            'visits.prescriptions.items.medicine',
            'visits.labOrders.items.labTest',
            'visits.labOrders.result',
            'visits.imagingOrders.items.imagingStudy',
            'visits.imagingOrders.report',
            'visits.admission.bed.ward',
            'visits.doctorVisitNotes.doctor',
        ]);

        $surgeries = Surgery::query()
            ->where('patient_id', $patient->id)
            ->with(['doctor', 'anaesthesiaRecord', 'anaesthesiaRecord.anaesthetist', 'teamMembers'])
            ->orderBy('scheduled_date')
            ->orderBy('id')
            ->get();

        $surgeriesByVisit = $surgeries->groupBy(fn (Surgery $surgery) => $surgery->visit_id ?: 'unlinked');

        $encounters = [];
        $index = [];
        $allergyNames = collect();

        foreach ($patient->visits as $visit) {
            $doctor = $visit->attendingDoctor();
            $consultation = $visit->consultation;
            $visitSurgeries = $surgeriesByVisit->get($visit->id, collect());

            if ($consultation) {
                if ($consultation->relationLoaded('allergies')) {
                    $allergyNames = $allergyNames->merge($consultation->allergies->pluck('name'));
                }
                if (filled($consultation->allergy_notes)) {
                    $allergyNames->push($consultation->allergy_notes);
                }
            }

            $encounterAt = $visit->visit_datetime;

            $index[] = [
                'at' => $encounterAt,
                'type' => strtoupper((string) $visit->visit_type),
                'label' => trim(($visit->visit_no ?: 'Visit').' · '.strtoupper((string) $visit->visit_type)),
                'summary' => $this->visitIndexSummary($visit, $consultation),
                'author' => $doctor?->name,
            ];

            foreach ($visitSurgeries as $surgery) {
                $index[] = [
                    'at' => $surgery->actual_start_time
                        ?? ($surgery->scheduled_date?->copy()->startOfDay())
                        ?? $encounterAt,
                    'type' => 'OT',
                    'label' => ($surgery->surgery_number ?: 'Surgery').' · OT',
                    'summary' => $surgery->procedure_name ?: 'Procedure',
                    'author' => $surgery->doctor?->name,
                ];
            }

            $encounters[] = [
                'visit' => $visit,
                'doctor_name' => $doctor?->name,
                'consultation' => $consultation,
                'vitals' => $visit->allVitalSigns ?? collect(),
                'prescriptions' => $visit->prescriptions ?? collect(),
                'lab_orders' => $visit->labOrders ?? collect(),
                'imaging_orders' => $visit->imagingOrders ?? collect(),
                'admission' => $visit->admission,
                'progress_notes' => $visit->doctorVisitNotes ?? collect(),
                'surgeries' => $visitSurgeries,
                'triage' => $visit->triage,
            ];
        }

        $orphanSurgeries = $surgeriesByVisit->get('unlinked', collect());
        foreach ($orphanSurgeries as $surgery) {
            $index[] = [
                'at' => $surgery->actual_start_time
                    ?? ($surgery->scheduled_date?->copy()->startOfDay())
                    ?? null,
                'type' => 'OT',
                'label' => ($surgery->surgery_number ?: 'Surgery').' · OT',
                'summary' => $surgery->procedure_name ?: 'Procedure',
                'author' => $surgery->doctor?->name,
            ];
            $encounters[] = [
                'visit' => null,
                'doctor_name' => $surgery->doctor?->name,
                'consultation' => null,
                'vitals' => collect(),
                'prescriptions' => collect(),
                'lab_orders' => collect(),
                'imaging_orders' => collect(),
                'admission' => null,
                'progress_notes' => collect(),
                'surgeries' => collect([$surgery]),
                'triage' => null,
            ];
        }

        usort($index, function (array $a, array $b) {
            $aTs = $a['at']?->timestamp ?? PHP_INT_MAX;
            $bTs = $b['at']?->timestamp ?? PHP_INT_MAX;

            return $aTs <=> $bTs;
        });

        $logoRelative = setting('hospital_logo');

        return [
            'patient' => $patient,
            'facility' => [
                'name' => setting('hospital_name', config('app.name', 'Hospital Management System')),
                'address' => setting('hospital_address', ''),
                'phone' => setting('hospital_phone', ''),
                'email' => setting('hospital_email', ''),
                'logo_path' => $this->resolveLogoPath(is_string($logoRelative) ? $logoRelative : null),
            ],
            'generated_at' => now(),
            'generated_by' => $generatedBy?->name,
            'allergies' => $allergyNames->filter()->unique()->values()->all(),
            'index' => $index,
            'encounters' => $encounters,
            'has_clinical_data' => count($encounters) > 0,
        ];
    }

    private function visitIndexSummary($visit, $consultation): string
    {
        $parts = [];

        if ($consultation?->chief_complaint) {
            $parts[] = $consultation->chief_complaint;
        } elseif ($consultation?->presenting_complaints) {
            $parts[] = $consultation->presenting_complaints;
        } elseif ($visit->triage?->chief_complaint) {
            $parts[] = $visit->triage->chief_complaint;
        }

        if ($consultation?->provisional_diagnosis) {
            $parts[] = 'Dx: '.$consultation->provisional_diagnosis;
        }

        $parts[] = 'Status: '.ucfirst(str_replace('_', ' ', (string) $visit->status));

        return implode(' · ', array_filter($parts));
    }

    private function resolveLogoPath(?string $relative): ?string
    {
        if (! filled($relative)) {
            return null;
        }

        $path = storage_path('app/public/'.ltrim($relative, '/'));

        return is_file($path) ? $path : null;
    }
}
