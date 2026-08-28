<?php

namespace App\Support;

use App\Models\Visit;

final class VisitWorkflowBackLink
{
    /** @return array{url: string, label: string} */
    public static function resolve(Visit $visit, ?string $from = null): array
    {
        if ($from === 'patients') {
            return [
                'url' => route('patients.index'),
                'label' => 'Patients',
            ];
        }

        return [
            'url' => route('visits.index', ['visit_type' => $visit->visit_type]),
            'label' => match ($visit->visit_type) {
                'opd' => 'OPD',
                'ipd' => 'Admitted Patients',
                'emergency' => 'Emergency',
                default => 'Visits',
            },
        ];
    }
}
