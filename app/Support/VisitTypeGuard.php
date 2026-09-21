<?php

namespace App\Support;

use App\Models\Visit;

final class VisitTypeGuard
{
    public static function assert(Visit $visit, string $expectedType): void
    {
        if ($visit->visit_type === $expectedType) {
            return;
        }

        $label = match ($expectedType) {
            'ipd' => 'IPD',
            'emergency' => 'Emergency',
            'opd' => 'OPD',
            default => $expectedType,
        };

        abort(403, "This action requires an {$label} visit.");
    }
}
