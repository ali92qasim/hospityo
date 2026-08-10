<?php

namespace App\Workflows\Handlers;

use App\Contracts\VisitTypeHandler;
use App\Enums\VisitType;
use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Support\Collection;

class OpdVisitHandler implements VisitTypeHandler
{
    public function type(): VisitType
    {
        return VisitType::Opd;
    }

    public function workflowData(Visit $visit): array
    {
        $visit->loadMissing(['opdDetails', 'doctor', 'consultation', 'vitalSigns']);

        return [
            'steps' => [
                'registered' => 'Registration',
                'vitals_recorded' => 'Vital Signs',
                'with_doctor' => 'Consultation',
                'completed' => 'Completed',
            ],
            'default_tab' => 'vitals',
            'show_investigations' => true,
            'consultation_label' => 'Consultation',
            'queue_priority' => $visit->queuePriority(),
        ];
    }

    public function resolveDoctors(Visit $visit): Collection
    {
        return Doctor::where('status', 'active')->get();
    }

    public function canComplete(Visit $visit): bool
    {
        return in_array($visit->status, ['with_doctor', 'vitals_recorded'], true);
    }
}
