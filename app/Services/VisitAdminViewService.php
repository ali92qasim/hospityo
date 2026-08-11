<?php

namespace App\Services;

use App\Models\Consultation;
use App\Models\OpdVisit;
use App\Models\Visit;

class VisitAdminViewService
{
    public static function loadRelations(Visit $visit): Visit
    {
        return $visit->load([
            'patient',
            'doctor.department',
            'primaryDoctor.doctor.department',
            'admission.bed.ward',
            'consultation',
            'triage',
            'opdDetails',
            'bills' => fn ($query) => $query->where('status', '!=', 'cancelled'),
        ]);
    }

    /** @return array<string, mixed> */
    public static function present(Visit $visit): array
    {
        $visit = self::loadRelations($visit);

        return [
            'display_priority' => self::displayPriority($visit),
            'bed_number' => $visit->admission?->bed?->bed_number,
            'ward_name' => $visit->admission?->bed?->ward?->name,
            'total_charges' => self::totalCharges($visit),
            'chief_complaint' => self::chiefComplaint($visit),
            'diagnosis' => $visit->consultation?->provisional_diagnosis,
            'treatment' => $visit->consultation?->treatment,
            'clinical_notes' => $visit->consultation?->notes,
            'discharge_at' => $visit->admission?->discharge_date ?? $visit->closed_at,
            'requires_doctor' => $visit->visit_type !== 'ipd',
        ];
    }

    public static function update(Visit $visit, array $validated): void
    {
        $spine = [
            'patient_id' => $validated['patient_id'],
            'visit_type' => $validated['visit_type'],
            'visit_datetime' => $validated['visit_datetime'],
            'status' => $validated['status'],
            'doctor_id' => $validated['visit_type'] === 'ipd'
                ? null
                : ($validated['doctor_id'] ?? null),
        ];

        if (array_key_exists('closed_at', $validated)) {
            $spine['closed_at'] = $validated['closed_at'];
        }

        $visit->update($spine);

        if ($validated['visit_type'] === 'opd') {
            OpdVisit::updateOrCreate(
                ['visit_id' => $visit->id],
                ['queue_priority' => $validated['priority']]
            );
        }

        if ($validated['visit_type'] === 'emergency' && $visit->triage) {
            $visit->triage->update(['priority_level' => $validated['priority']]);
        }

        $consultationPayload = array_filter([
            'chief_complaint' => $validated['chief_complaint'] ?? null,
            'provisional_diagnosis' => $validated['diagnosis'] ?? null,
            'treatment' => $validated['treatment'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($consultationPayload !== []) {
            Consultation::recordForVisit($visit, $consultationPayload);
        }

        if (
            $visit->visit_type === 'emergency'
            && isset($validated['chief_complaint'])
            && $visit->triage
        ) {
            $visit->triage->update(['chief_complaint' => $validated['chief_complaint']]);
        }
    }

    private static function displayPriority(Visit $visit): string
    {
        return match ($visit->visit_type) {
            'opd' => $visit->queuePriority(),
            'emergency' => $visit->triage?->priority_level ?? 'medium',
            default => 'medium',
        };
    }

    private static function chiefComplaint(Visit $visit): ?string
    {
        if ($visit->visit_type === 'emergency') {
            return $visit->triage?->chief_complaint ?? $visit->consultation?->chief_complaint;
        }

        return $visit->consultation?->chief_complaint;
    }

    private static function totalCharges(Visit $visit): float
    {
        if ($visit->relationLoaded('bills') && $visit->bills->isNotEmpty()) {
            return (float) $visit->bills->sum('total_amount');
        }

        return (float) $visit->bills()->where('status', '!=', 'cancelled')->sum('total_amount');
    }
}
