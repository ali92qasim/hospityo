<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Doctor;
use App\Models\Tenant;
use App\Models\Visit;
use Illuminate\Support\Collection;

class OpdVisitHandler implements VisitTypeHandler
{
    public function type(): VisitType
    {
        return VisitType::Opd;
    }

    public function workflowSteps(): array
    {
        return [
            VisitStatus::Registered->value => 'Registration',
            VisitStatus::VitalsRecorded->value => 'Vital Signs',
            VisitStatus::WithDoctor->value => 'Consultation',
            VisitStatus::Completed->value => 'Completed',
        ];
    }

    public function allowedTransitions(Visit $visit): array
    {
        return match ($visit->statusEnum()) {
            VisitStatus::Registered => [VisitStatus::VitalsRecorded, VisitStatus::WithDoctor],
            VisitStatus::VitalsRecorded => [VisitStatus::WithDoctor, VisitStatus::Completed],
            VisitStatus::WithDoctor => [VisitStatus::TestsOrdered, VisitStatus::TestsCompleted, VisitStatus::Completed],
            VisitStatus::TestsOrdered => [VisitStatus::TestsCompleted, VisitStatus::Completed],
            VisitStatus::TestsCompleted => [VisitStatus::Completed],
            default => [],
        };
    }

    public function workflowData(Visit $visit): array
    {
        $visit->loadMissing(['opdDetails', 'doctor', 'consultation', 'vitalSigns']);

        return [
            'steps' => $this->workflowSteps(),
            'default_tab' => 'vitals',
            'show_lab_investigations' => Tenant::currentHasModule('laboratory'),
            'show_imaging_investigations' => Tenant::currentHasModule('imaging'),
            'show_investigations' => Tenant::currentHasModule('laboratory') || Tenant::currentHasModule('imaging'),
            'show_prescriptions' => Tenant::currentHasModule('pharmacy'),
            'consultation_label' => 'Consultation',
            'queue_priority' => $visit->queuePriority(),
            'show_opd_ui' => true,
            'show_emergency_ui' => false,
            'show_ipd_ui' => false,
            'append_only_vitals' => false,
            'show_consultation_gpe' => true,
            'show_doctor_assignment_in_vitals' => false,
            'show_active_complaints' => false,
            'show_complete_visit_button' => true,
            'print_label' => 'Print Report',
            'vitals_tab_default_visible' => true,
            'care_team_consult_message' => null,
            'care_team_prescribe_message' => null,
            'care_team_labs_message' => null,
            'workflow_accordion' => true,
        ];
    }

    public function resolveDoctors(Visit $visit): Collection
    {
        return Doctor::where('status', 'active')->get();
    }

    public function canComplete(Visit $visit): bool
    {
        return in_array($visit->statusEnum(), [VisitStatus::WithDoctor, VisitStatus::VitalsRecorded], true);
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
        if ($visit->vitalSigns && $visit->doctor_id) {
            return 'consultation';
        }

        return 'vitals';
    }

    public function resolveInitialSection(Visit $visit): string
    {
        if (! $visit->vitalSigns) {
            return 'vitals';
        }

        if (! $visit->consultation) {
            return 'consultation';
        }

        return 'prescription';
    }

    public function showOrderDoctorPicker(Visit $visit, ?Doctor $authDoctor): bool
    {
        return false;
    }
}
