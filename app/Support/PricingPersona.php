<?php

namespace App\Support;

class PricingPersona
{
    /**
     * Landing-page personas that recommend one of the main plans.
     * Keys are stable query values (?persona=laboratory).
     *
     * @return array<string, array{label: string, plan: string, reason: string}>
     */
    public static function definitions(): array
    {
        return [
            'physiotherapy' => [
                'label' => 'Physiotherapy / clinic',
                'plan' => 'starter',
                'reason' => 'Best for appointments, patients, visits, and billing.',
            ],
            'laboratory' => [
                'label' => 'Laboratory',
                'plan' => 'professional',
                'reason' => 'Includes Laboratory plus patients and billing.',
            ],
            'pharmacy' => [
                'label' => 'Pharmacy',
                'plan' => 'professional',
                'reason' => 'Includes Pharmacy/POS plus patients and billing.',
            ],
            'hospital' => [
                'label' => 'Full hospital',
                'plan' => 'enterprise',
                'reason' => 'Full module set for multi-department hospitals.',
            ],
        ];
    }

    public static function find(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    public static function recommendedPlanSlug(?string $persona): ?string
    {
        if ($persona === null || $persona === '') {
            return null;
        }

        return self::find($persona)['plan'] ?? null;
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }
}
