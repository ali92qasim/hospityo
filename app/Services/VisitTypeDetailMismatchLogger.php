<?php

namespace App\Services;

use App\Models\EmergencyVisit;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VisitTypeDetailMismatchLogger
{
    public static function audit(Visit $visit): void
    {
        if (! config('visits.log_child_mismatches')) {
            return;
        }

        $visit->loadMissing(['opdDetails']);

        if ($visit->visit_type !== 'opd') {
            return;
        }

        $childPriority = $visit->opdDetails?->queue_priority;
        $spinePriority = $visit->priority ?? 'medium';

        if ($childPriority !== null && $childPriority !== $spinePriority) {
            Log::warning('visit_type_detail_mismatch', [
                'visit_id' => $visit->id,
                'visit_type' => $visit->visit_type,
                'field' => 'queue_priority',
                'spine_value' => $spinePriority,
                'child_value' => $childPriority,
            ]);
        }

        if ($visit->opdDetails === null) {
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
