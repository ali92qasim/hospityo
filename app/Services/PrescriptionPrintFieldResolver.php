<?php

namespace App\Services;

use App\Models\Visit;
use App\Support\PrescriptionPrintFieldCatalog;

class PrescriptionPrintFieldResolver
{
    public function resolve(Visit $visit, string $fieldKey): ?string
    {
        $visit->loadMissing([
            'patient',
            'doctor',
            'consultation.allergies',
            'vitalSigns',
            'triage',
            'labOrders.items.investigation',
        ]);

        $doctor = $visit->attendingDoctor();
        $patient = $visit->patient;
        $consultation = $visit->consultation;
        $vitals = $visit->vitalSigns;

        $value = match ($fieldKey) {
            'hospital_name' => setting('hospital_name'),
            'hospital_address' => setting('hospital_address'),
            'hospital_phone' => setting('hospital_phone'),
            'hospital_email' => setting('hospital_email'),
            'doctor_name' => $doctor?->name ? 'Dr. '.$doctor->name : null,
            'doctor_qualification' => $doctor?->qualification,
            'doctor_specialization' => $doctor?->specialization,
            'doctor_registration_no' => $doctor?->pmdc_number,
            'patient_name' => $patient?->name,
            'age' => $patient?->age !== null ? (string) $patient->age : null,
            'gender' => $patient?->gender ? ucfirst($patient->gender) : null,
            'age_gender' => $patient ? $patient->age.'Y / '.ucfirst((string) $patient->gender) : null,
            'patient_phone' => $patient?->phone,
            'patient_no' => $patient?->patient_no,
            'visit_no' => $visit->visit_no,
            'date' => optional($visit->visit_datetime ?? now())->format('d M Y, h:i A'),
            'diagnosis' => $consultation?->provisional_diagnosis,
            'allergies' => $this->allergies($consultation),
            'presenting_complaints' => $this->complaints($visit, $consultation),
            'patient_history' => $this->history($consultation),
            'investigations' => $this->investigations($visit),
            'vital_bp' => $vitals?->blood_pressure,
            'vital_temp' => $vitals?->temperature !== null ? $vitals->temperature.'°F' : null,
            'vital_pulse' => $vitals?->pulse_rate !== null ? $vitals->pulse_rate.' bpm' : null,
            'vital_spo2' => $vitals?->spo2 !== null ? $vitals->spo2.'%' : null,
            'vital_bsr' => $vitals?->bsr,
            'vital_weight' => $vitals?->weight !== null ? $vitals->weight.' kg' : null,
            'vital_height' => $vitals?->height !== null ? $vitals->height.' ft' : null,
            'gpe_chest' => $consultation?->gpe_chest,
            'gpe_abdomen' => $consultation?->gpe_abdomen,
            'gpe_cvs' => $consultation?->gpe_cvs,
            'gpe_cns' => $consultation?->gpe_cns,
            'gpe_pupils' => $consultation?->gpe_pupils,
            'gpe_conjunctiva' => $consultation?->gpe_conjunctiva,
            'gpe_nails' => $consultation?->gpe_nails,
            'gpe_throat' => $consultation?->gpe_throat,
            'gpe_sclera' => $consultation?->gpe_sclera,
            'gpe_gcs' => $consultation?->gpe_gcs,
            'instructions' => $this->instructions($consultation),
            'next_visit_date' => $consultation?->next_visit_date
                ? \Carbon\Carbon::parse($consultation->next_visit_date)->format('d F Y')
                : null,
            default => in_array($fieldKey, PrescriptionPrintFieldCatalog::keys(), true) ? null : null,
        };

        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : $value;
    }

    private function allergies($consultation): ?string
    {
        if (! $consultation) {
            return null;
        }

        $parts = [];
        if ($consultation->relationLoaded('allergies') && $consultation->allergies->isNotEmpty()) {
            $parts[] = $consultation->allergies->pluck('name')->join(', ');
        }
        if ($consultation->allergy_notes) {
            $parts[] = $consultation->allergy_notes;
        }

        return $parts ? implode(' ', $parts) : null;
    }

    private function complaints(Visit $visit, $consultation): ?string
    {
        $parts = array_filter([
            $consultation?->presenting_complaints,
            $consultation?->chief_complaint,
            $visit->triage?->chief_complaint,
        ]);

        return $parts ? implode("\n", $parts) : null;
    }

    private function history($consultation): ?string
    {
        if (! $consultation) {
            return null;
        }

        $conditions = [];
        if ($consultation->diagnosis_dm) {
            $conditions[] = 'DM: '.$consultation->diagnosis_dm;
        }
        if ($consultation->diagnosis_htn) {
            $conditions[] = 'HTN: '.$consultation->diagnosis_htn;
        }
        if ($consultation->diagnosis_ihd) {
            $conditions[] = 'IHD: '.$consultation->diagnosis_ihd;
        }
        if ($consultation->diagnosis_asthma) {
            $conditions[] = 'Asthma: '.$consultation->diagnosis_asthma;
        }

        $parts = array_filter([
            $conditions ? implode(', ', $conditions) : null,
            $consultation->history,
        ]);

        return $parts ? implode("\n", $parts) : null;
    }

    private function investigations(Visit $visit): ?string
    {
        $names = $visit->labOrders
            ->flatMap(fn ($order) => $order->items ?? collect())
            ->map(fn ($item) => $item->investigation?->name)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? null : $names->join(', ');
    }

    private function instructions($consultation): ?string
    {
        $parts = array_filter([
            $consultation?->treatment_plan,
            $consultation?->follow_up_instructions,
        ]);

        return $parts ? implode(' | ', $parts) : null;
    }
}
