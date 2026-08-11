<?php

namespace App\Services;

use App\Models\Visit;
use Illuminate\Support\Facades\Log;

class VisitTypeDetailMismatchLogger
{
    public static function audit(Visit $visit): void
    {
        if (! config('visits.log_child_mismatches')) {
            return;
        }

        $visit->loadMissing(['opdDetails', 'ipdDetails', 'emergencyDetails']);

        $missingChild = match ($visit->visit_type) {
            'opd' => $visit->opdDetails === null,
            'ipd' => $visit->ipdDetails === null,
            'emergency' => $visit->emergencyDetails === null,
            default => false,
        };

        if ($missingChild) {
            Log::warning('visit_type_detail_mismatch', [
                'visit_id' => $visit->id,
                'visit_type' => $visit->visit_type,
                'field' => 'missing_child_row',
                'spine_value' => null,
                'child_value' => null,
            ]);
        }
    }
}
