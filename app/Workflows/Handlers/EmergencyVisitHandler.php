<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Doctor;
use App\Models\Tenant;
use App\Models\Visit;
use Illuminate\Support\Collection;

class EmergencyVisitHandler implements VisitTypeHandler
{
    public function type(): VisitType
    {
        return VisitType::Emergency;
    }

    public function workflowSteps(): array
    {
        return [
            VisitStatus::Registered->value => 'Registration',
            VisitStatus::Triaged->value => 'Triaged',
            VisitStatus::VitalsRecorded->value => 'Vital Signs',
            VisitStatus::WithDoctor->value => 'Emergency Care',
            VisitStatus::Completed->value => 'Completed',
        ];
    }

    public function allowedTransitions(Visit $visit): array
    {
        return match ($visit->statusEnum()) {
            VisitStatus::Registered => [VisitStatus::Triaged],
            VisitStatus::Triaged => [VisitStatus::VitalsRecorded, VisitStatus::WithDoctor],
            VisitStatus::VitalsRecorded => [VisitStatus::WithDoctor],
            VisitStatus::WithDoctor => [VisitStatus::Completed],
            default => [],
        };
    }

    public function workflowData(Visit $visit): array
    {
        $visit->loadMissing(['emergencyDetails', 'doctor', 'triage', 'consultation', 'vitalSigns']);

        return [
            'steps' => $this->workflowSteps(),
            'default_tab' => 'triage',
            'show_investigations' => false,
            'show_lab_investigations' => false,
            'show_imaging_investigations' => false,
            'show_prescriptions' => Tenant::currentHasModule('pharmacy'),
            'consultation_label' => 'Emergency Care',
            'show_opd_ui' => false,
            'show_ipd_ui' => false,
            'show_emergency_ui' => true,
            'append_only_vitals' => false,
            'show_consultation_gpe' => true,
            'show_doctor_assignment_in_vitals' => false,
            'show_active_complaints' => false,
            'show_complete_visit_button' => true,
            'print_label' => 'Print Report',
            'vitals_tab_default_visible' => false,
            'care_team_consult_message' => null,
            'care_team_prescribe_message' => null,
            'care_team_labs_message' => null,
            'has_emergency_detail' => (bool) $visit->emergencyDetails,
            'triage_completed' => (bool) $visit->triage,
            'triage_priority_level' => $visit->triage?->priority_level,
            'workflow_accordion' => true,
        ];
    }

    public function resolveInitialSection(Visit $visit): string
    {
        if (! $visit->triage) {
            return 'vitals';
        }

        if (! $visit->vitalSigns) {
            return 'vitals';
        }

        if (! $visit->consultation) {
            return 'consultation';
        }

        return 'prescription';
    }

    public function resolveDoctors(Visit $visit): Collection
    {
        return Doctor::where('status', 'active')->get();
    }

    public function canComplete(Visit $visit): bool
    {
        return in_array($visit->statusEnum(), [VisitStatus::WithDoctor, VisitStatus::Triaged], true);
    }

    public function canConsult(Visit $visit): bool
    {
        return (bool) $visit->doctor_id;
    }

    public function canPrescribe(Visit $visit): bool
    {
        return (bool) $visit->doctor_id;
    }

    public function canOrderLabs(Visit $visit): bool
    {
        return (bool) $visit->doctor_id;
    }

    public function resolveInitialTab(Visit $visit): string
    {
        if (! $visit->triage) {
            return 'triage';
        }

        if ($visit->vitalSigns) {
            return 'consultation';
        }

        return 'vitals';
    }

    public function showOrderDoctorPicker(Visit $visit, ?Doctor $authDoctor): bool
    {
        return false;
    }
}
