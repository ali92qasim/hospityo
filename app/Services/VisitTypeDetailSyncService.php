<?php

namespace App\Services;

use App\Models\EmergencyVisit;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Visit;
use Illuminate\Support\Facades\DB;

class VisitTypeDetailSyncService
{
    public static function createForVisit(Visit $visit): void
    {
        DB::connection('tenant')->transaction(function () use ($visit) {
            match ($visit->visit_type) {
                'opd' => OpdVisit::firstOrCreate(
                    ['visit_id' => $visit->id],
                    ['queue_priority' => 'medium']
                ),
                'ipd' => IpdVisit::firstOrCreate(['visit_id' => $visit->id]),
                'emergency' => EmergencyVisit::firstOrCreate(['visit_id' => $visit->id]),
                default => null,
            };
        });

        VisitTypeDetailMismatchLogger::audit($visit->fresh(['opdDetails']));
    }

    public static function syncLegacyToChild(Visit $visit, array $changedAttributes = []): void
    {
        if (! config('visits.dual_write_enabled')) {
            return;
        }

        VisitTypeDetailMismatchLogger::audit($visit->fresh(['opdDetails']));
    }

    public static function resolveQueuePriority(Visit $visit): string
    {
        return $visit->queuePriority();
    }
}
