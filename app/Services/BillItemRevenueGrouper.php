<?php

namespace App\Services;

use App\Models\BillItem;
use App\Models\ImagingStudy;
use App\Models\LabTest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BillItemRevenueGrouper
{
    public static function groupKey(BillItem $item, ?Collection $catalogByName = null): string
    {
        if ($item->service_id) {
            return 'service:'.$item->service_id;
        }

        if ($item->lab_test_id) {
            return 'lab:'.$item->lab_test_id;
        }

        if ($item->imaging_study_id) {
            return 'imaging:'.$item->imaging_study_id;
        }

        $matched = static::matchCatalogByDescription($item->description, $catalogByName);
        if ($matched instanceof LabTest) {
            return 'lab:'.$matched->id;
        }
        if ($matched instanceof ImagingStudy) {
            return 'imaging:'.$matched->id;
        }

        if (in_array($item->item_category, ['lab', 'imaging'], true)) {
            return $item->item_category.':desc:'.static::normalizeName($item->description ?? '');
        }

        if ($item->description) {
            return 'other:'.static::normalizeName($item->description);
        }

        return 'unknown';
    }

    public static function groupLabel(BillItem $item, ?Collection $catalogByName = null): string
    {
        if ($item->service) {
            return $item->service->name;
        }

        if ($item->labTest) {
            return $item->labTest->name;
        }

        if ($item->imagingStudy) {
            return $item->imagingStudy->name;
        }

        $matched = static::matchCatalogByDescription($item->description, $catalogByName);
        if ($matched) {
            return $matched->name;
        }

        if (in_array($item->item_category, ['lab', 'imaging'], true) && $item->description) {
            return $item->description;
        }

        return $item->description ?: 'Unknown';
    }

    public static function isInvestigation(BillItem $item, ?Collection $catalogByName = null): bool
    {
        if ($item->lab_test_id || $item->imaging_study_id || in_array($item->item_category, ['lab', 'imaging'], true)) {
            return true;
        }

        return static::matchCatalogByDescription($item->description, $catalogByName) !== null;
    }

    public static function isLab(BillItem $item): bool
    {
        return (bool) $item->lab_test_id || $item->item_category === 'lab';
    }

    public static function isImaging(BillItem $item): bool
    {
        return (bool) $item->imaging_study_id || $item->item_category === 'imaging';
    }

    /**
     * @return Collection<string, LabTest|ImagingStudy>
     */
    public static function investigationsByName(): Collection
    {
        return static::catalogByName();
    }

    /**
     * @return Collection<string, LabTest|ImagingStudy>
     */
    public static function catalogByName(): Collection
    {
        $lab = LabTest::query()->get()->keyBy(fn (LabTest $test) => static::normalizeName($test->name));
        $imaging = ImagingStudy::query()->get()->keyBy(fn (ImagingStudy $study) => static::normalizeName($study->name));

        return $lab->union($imaging);
    }

    public static function matchInvestigationByDescription(
        ?string $description,
        ?Collection $catalogByName = null
    ): LabTest|ImagingStudy|null {
        return static::matchCatalogByDescription($description, $catalogByName);
    }

    public static function matchCatalogByDescription(
        ?string $description,
        ?Collection $catalogByName = null
    ): LabTest|ImagingStudy|null {
        $normalized = static::normalizeName($description ?? '');
        if ($normalized === '') {
            return null;
        }

        $lookup = $catalogByName ?? static::catalogByName();

        return $lookup->get($normalized);
    }

    private static function normalizeName(string $value): string
    {
        return Str::upper(trim($value));
    }
}
