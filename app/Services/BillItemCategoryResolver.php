<?php

namespace App\Services;

use App\Models\Service;

class BillItemCategoryResolver
{
    public const CATEGORIES = ['opd', 'ipd', 'emergency', 'lab', 'imaging', 'pharmacy'];

    /**
     * Derive the revenue/share category for a bill line.
     */
    public static function resolve(array $item, string $billType): string
    {
        if (! empty($item['lab_test_id'])) {
            return 'lab';
        }

        if (! empty($item['imaging_study_id'])) {
            return 'imaging';
        }

        if (! empty($item['service_id'])) {
            $service = Service::find($item['service_id']);

            if ($service && $service->category === 'medication') {
                return 'pharmacy';
            }

            if (in_array($billType, self::CATEGORIES, true)) {
                return $billType;
            }

            return 'opd';
        }

        return in_array($billType, self::CATEGORIES, true) ? $billType : 'opd';
    }
}
