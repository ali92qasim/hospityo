<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Visit;
use App\Services\IpdDraftBillService;
use Illuminate\Support\Collection;

class IpdVisitHandler implements VisitTypeHandler
{
    public function type(): VisitType
    {
        return VisitType::Ipd;
    }

    public function workflowSteps(): array
    {
        return [
            VisitStatus::Registered->value => 'Registration',
            VisitStatus::VitalsRecorded->value => 'Vital Signs',
            VisitStatus::Admitted->value => 'Admitted',
            VisitStatus::WithDoctor->value => 'Treatment',
            VisitStatus::Discharged->value => 'Discharged',
        ];
    }

    public function allowedTransitions(Visit $visit): array
    {
        return match ($visit->statusEnum()) {
            VisitStatus::Registered => [VisitStatus::VitalsRecorded, VisitStatus::Admitted],
            VisitStatus::VitalsRecorded => [VisitStatus::Admitted],
            VisitStatus::Admitted => [VisitStatus::WithDoctor],
            VisitStatus::WithDoctor => [VisitStatus::Discharged],
            default => [],
        };
    }

    public function workflowData(Visit $visit): array
    {
        $visit->loadMissing([
            'ipdDetails',
            'admission.bed.ward',
            'admission.advances',
            'careTeam.doctor',
            'primaryDoctor.doctor',
            'allVitalSigns',
            'ipdGpeRecords',
            'doctorVisitNotes',
            'draftBill',
        ]);

        $draftBill = IpdDraftBillService::resolveForVisit($visit);
        $settlement = null;

        if ($visit->admission?->status === 'active') {
            $settlement = [
                'total_advances' => $visit->admission->total_advances,
                'draft_charges' => (float) ($draftBill?->total_amount ?? 0),
                'credit_balance' => $visit->admission->credit_balance,
                'amount_due' => max(0, ($draftBill?->due_amount ?? 0)),
            ];
        }

        return [
            'steps' => $this->workflowSteps(),
            'default_tab' => 'admission',
            'show_investigations' => true,
            'consultation_label' => 'Consultation',
            'show_opd_ui' => false,
            'show_emergency_ui' => false,
            'show_ipd_ui' => true,
            'append_only_vitals' => true,
            'show_consultation_gpe' => false,
            'show_doctor_assignment_in_vitals' => false,
            'show_active_complaints' => true,
            'show_complete_visit_button' => false,
            'print_label' => 'Print IPD Report',
            'vitals_tab_default_visible' => false,
            'care_team_consult_message' => 'Add at least one doctor to the Care Team before starting consultation.',
            'care_team_prescribe_message' => 'Add at least one doctor to the Care Team before creating prescriptions.',
            'care_team_labs_message' => 'Add at least one doctor to the Care Team before ordering investigations.',
            'available_beds' => Bed::with('ward')->where('status', 'available')->get(),
            'draft_bill' => $draftBill,
            'settlement' => $settlement,
            'expected_discharge_date' => $visit->ipdDetails?->expected_discharge_date,
        ];
    }

    public function resolveDoctors(Visit $visit): Collection
    {
        return Doctor::where('status', 'active')->get();
    }

    public function canComplete(Visit $visit): bool
    {
        return false;
    }

    public function canConsult(Visit $visit): bool
    {
        return $visit->hasActiveCareTeam();
    }

    public function canPrescribe(Visit $visit): bool
    {
        return $visit->hasActiveCareTeam();
    }

    public function canOrderLabs(Visit $visit): bool
    {
        return $visit->hasActiveCareTeam();
    }

    public function resolveInitialTab(Visit $visit): string
    {
        if (! $visit->admission) {
            return 'admission';
        }

        if ($visit->hasActiveCareTeam()) {
            return 'consultation';
        }

        return 'vitals';
    }

    public function showOrderDoctorPicker(Visit $visit, ?Doctor $authDoctor): bool
    {
        return empty($authDoctor)
            || ! $visit->careTeam->contains('doctor_id', $authDoctor->id ?? null);
    }
}
