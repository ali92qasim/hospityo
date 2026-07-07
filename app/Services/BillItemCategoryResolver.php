<?php

namespace App\Services;

use App\Models\Service;

class BillItemCategoryResolver
{
    public const CATEGORIES = ['opd', 'ipd', 'emergency', 'investigation', 'pharmacy'];

    /**
     * Derive the revenue/share category for a bill line.
     * Investigation lines are always "investigation"; service lines inherit bill context.
     */
    public static function resolve(array $item, string $billType): string
    {
        if (! empty($item['investigation_id'])) {
            return 'investigation';
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
