<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitType;
use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Support\Collection;

class EmergencyVisitHandler implements VisitTypeHandler
{
    public function type(): VisitType
    {
        return VisitType::Emergency;
    }

    public function workflowData(Visit $visit): array
    {
        $visit->loadMissing(['emergencyDetails', 'doctor', 'triage', 'consultation', 'vitalSigns']);

        return [
            'steps' => [
                'registered' => 'Registration',
                'triaged' => 'Triaged',
                'vitals_recorded' => 'Vital Signs',
                'with_doctor' => 'Emergency Care',
                'completed' => 'Completed',
            ],
            'default_tab' => 'triage',
            'show_investigations' => false,
            'consultation_label' => 'Emergency Care',
        ];
    }

    public function resolveDoctors(Visit $visit): Collection
    {
        return Doctor::where('status', 'active')->get();
    }

    public function canComplete(Visit $visit): bool
    {
        return in_array($visit->status, ['with_doctor', 'triaged'], true);
    }
}
