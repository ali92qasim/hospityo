<?php

namespace App\Services;

use App\Models\DoctorShareRate;
use App\Models\DoctorShareRule;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DoctorShareRateMigrator
{
    public static function copyFromLegacyRules(): int
    {
        $candidates = [];

        $rules = DoctorShareRule::query()
            ->whereNotNull('doctor_id')
            ->where('is_active', true)
            ->where('share_type', 'percentage')
            ->get(['doctor_id', 'applies_to', 'share_value']);

        foreach ($rules as $rule) {
            $category = self::categoryFor($rule->applies_to);

            if ($category === null) {
                continue;
            }

            $percentage = self::normalizePercentage($rule->share_value);
            $key = $rule->doctor_id.':'.$category;

            if (isset($candidates[$key]) && $candidates[$key]['percentage'] !== $percentage) {
                throw self::conflict(
                    (int) $rule->doctor_id,
                    $category,
                    $candidates[$key]['percentage'],
                    $percentage,
                );
            }

            $candidates[$key] = [
                'doctor_id' => (int) $rule->doctor_id,
                'service_category' => $category,
                'percentage' => $percentage,
            ];
        }

        return DB::connection('tenant')->transaction(function () use ($candidates): int {
            foreach ($candidates as $candidate) {
                $existing = DoctorShareRate::query()
                    ->where('doctor_id', $candidate['doctor_id'])
                    ->where('service_category', $candidate['service_category'])
                    ->first();

                if ($existing !== null
                    && self::normalizePercentage($existing->percentage) !== $candidate['percentage']) {
                    throw self::conflict(
                        $candidate['doctor_id'],
                        $candidate['service_category'],
                        self::normalizePercentage($existing->percentage),
                        $candidate['percentage'],
                    );
                }
            }

            $inserted = 0;

            foreach ($candidates as $candidate) {
                if (DoctorShareRate::query()
                    ->where('doctor_id', $candidate['doctor_id'])
                    ->where('service_category', $candidate['service_category'])
                    ->exists()) {
                    continue;
                }

                DoctorShareRate::query()->create($candidate);
                $inserted++;
            }

            return $inserted;
        });
    }

    private static function categoryFor(string $appliesTo): ?string
    {
        if ($appliesTo === 'all') {
            return 'general';
        }

        if ($appliesTo === 'general' || $appliesTo === 'investigation') {
            return null;
        }

        return in_array($appliesTo, DoctorShareRate::CATEGORIES, true)
            ? $appliesTo
            : null;
    }

    private static function normalizePercentage(mixed $percentage): string
    {
        return number_format((float) $percentage, 2, '.', '');
    }

    private static function conflict(
        int $doctorId,
        string $category,
        string $firstPercentage,
        string $secondPercentage,
    ): RuntimeException {
        return new RuntimeException(
            "Conflicting doctor share percentages for doctor {$doctorId}, "
            ."category {$category}: {$firstPercentage} and {$secondPercentage}."
        );
    }
}
