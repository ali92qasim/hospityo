<?php

namespace App\Enums;

/**
 * @deprecated Kind is no longer stored. Kept for historical tenant migrations.
 */
enum InvestigationKind: string
{
    case Lab = 'lab';
    case Imaging = 'imaging';

    /** @return list<string> */
    public function categories(): array
    {
        return match ($this) {
            self::Lab => [
                'hematology',
                'biochemistry',
                'microbiology',
                'immunology',
                'pathology',
                'histopathology',
                'molecular',
            ],
            self::Imaging => [
                'x-ray',
                'ultrasound',
                'ct-scan',
                'mri',
                'radiology',
                'cardiology',
                'cardiac-diagnostics',
            ],
        };
    }

    public static function fromCategory(?string $category): ?self
    {
        $normalized = strtolower(trim((string) $category));

        foreach (self::cases() as $kind) {
            if (in_array($normalized, $kind->categories(), true)) {
                return $kind;
            }
        }

        return null;
    }
}
