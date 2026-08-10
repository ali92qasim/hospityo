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
                    ['queue_priority' => self::normalizePriority($visit->priority ?? 'medium')]
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

        if ($visit->visit_type === 'opd' && array_key_exists('priority', $changedAttributes)) {
            $visit->opdDetails()?->update([
                'queue_priority' => self::normalizePriority($visit->priority ?? 'medium'),
            ]);
        }

        if (array_key_exists('discharge_datetime', $changedAttributes) && $visit->discharge_datetime) {
            $visit->updateQuietly(['closed_at' => $visit->discharge_datetime]);
        }

        VisitTypeDetailMismatchLogger::audit($visit->fresh(['opdDetails']));
    }

    public static function resolveQueuePriority(Visit $visit): string
    {
        return $visit->queuePriority();
    }

    private static function normalizePriority(?string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high', 'critical'], true)
            ? $priority
            : 'medium';
    }
}
