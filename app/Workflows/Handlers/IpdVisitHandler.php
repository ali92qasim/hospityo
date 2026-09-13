<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Bed;
use App\Models\Doctor;
use App\Models\Tenant;
use App\Models\Visit;
use App\Models\Ward;
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
            'show_lab_investigations' => Tenant::currentHasModule('laboratory'),
            'show_imaging_investigations' => Tenant::currentHasModule('imaging'),
            'show_investigations' => Tenant::currentHasModule('laboratory') || Tenant::currentHasModule('imaging'),
            'show_prescriptions' => Tenant::currentHasModule('pharmacy'),
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
            'wards' => Ward::query()->where('status', 'active')->orderBy('name')->get(),
            'draft_bill' => $draftBill,
            'settlement' => $settlement,
            'expected_discharge_date' => $visit->ipdDetails?->expected_discharge_date,
            'workflow_accordion' => true,
            'tab_access' => $this->tabAccess($visit),
        ];
    }

    /**
     * Sequential IPD tab unlock state driven by completed steps.
     *
     * @return array<string, array{unlocked: bool, complete: bool, lock_reason: ?string}>
     */
    public function tabAccess(Visit $visit): array
    {
        $visit->loadMissing(['admission', 'allVitalSigns', 'careTeam', 'consultation']);

        $admitted = (bool) $visit->admission;
        $hasVitals = $visit->allVitalSigns->isNotEmpty();
        $hasCareTeam = $visit->hasActiveCareTeam();
        $hasConsultation = (bool) $visit->consultation;
        $admissionFirst = 'Complete the Admission step first.';
        $vitalsFirst = 'Record vital signs first.';
        $careTeamFirst = 'Add a doctor to the Care Team first.';
        $clinicalUnlocked = $admitted && $hasVitals && $hasCareTeam;
        $clinicalReason = ! $admitted
            ? $admissionFirst
            : (! $hasVitals ? $vitalsFirst : (! $hasCareTeam ? $careTeamFirst : null));

        return [
            'admission' => [
                'unlocked' => true,
                'complete' => $admitted,
                'lock_reason' => null,
            ],
            'vitals' => [
                'unlocked' => $admitted,
                'complete' => $hasVitals,
                'lock_reason' => $admitted ? null : $admissionFirst,
            ],
            'care-team' => [
                'unlocked' => $admitted,
                'complete' => $hasCareTeam,
                'lock_reason' => $admitted ? null : $admissionFirst,
            ],
            'consultation' => [
                'unlocked' => $clinicalUnlocked,
                'complete' => $hasConsultation,
                'lock_reason' => $clinicalReason,
            ],
            'gpe' => [
                'unlocked' => $clinicalUnlocked,
                'complete' => $visit->ipdGpeRecords()->exists(),
                'lock_reason' => $clinicalReason,
            ],
            'lab' => [
                'unlocked' => $clinicalUnlocked,
                'complete' => false,
                'lock_reason' => $clinicalReason,
            ],
            'imaging' => [
                'unlocked' => $clinicalUnlocked,
                'complete' => false,
                'lock_reason' => $clinicalReason,
            ],
            'prescription' => [
                'unlocked' => $clinicalUnlocked,
                'complete' => $visit->relationLoaded('prescriptions')
                    ? $visit->prescriptions->isNotEmpty()
                    : $visit->prescriptions()->exists(),
                'lock_reason' => $clinicalReason,
            ],
        ];
    }

    public function resolveInitialSection(Visit $visit): string
    {
        if (! $visit->admission) {
            return 'admission';
        }

        if ($visit->allVitalSigns->isEmpty()) {
            return 'vitals';
        }

        if ($visit->hasActiveCareTeam()) {
            return 'consultation';
        }

        return 'vitals';
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
