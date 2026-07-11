<?php

namespace App\Services;

use App\Models\BillItem;
use App\Models\Investigation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BillItemRevenueGrouper
{
    /**
     * Stable key for aggregating bill lines in revenue reports.
     */
    public static function groupKey(BillItem $item, ?Collection $investigationsByName = null): string
    {
        if ($item->service_id) {
            return 'service:' . $item->service_id;
        }

        if ($item->investigation_id) {
            return 'investigation:' . $item->investigation_id;
        }

        $matched = static::matchInvestigationByDescription($item->description, $investigationsByName);
        if ($matched) {
            return 'investigation:' . $matched->id;
        }

        if ($item->item_category === 'investigation') {
            return 'investigation:desc:' . static::normalizeName($item->description ?? '');
        }

        if ($item->description) {
            return 'other:' . static::normalizeName($item->description);
        }

        return 'unknown';
    }

    /**
     * Human-readable label for a revenue group.
     */
    public static function groupLabel(BillItem $item, ?Collection $investigationsByName = null): string
    {
        if ($item->service) {
            return $item->service->name;
        }

        if ($item->investigation) {
            return $item->investigation->name;
        }

        $matched = static::matchInvestigationByDescription($item->description, $investigationsByName);
        if ($matched) {
            return $matched->name;
        }

        if ($item->item_category === 'investigation' && $item->description) {
            return $item->description;
        }

        return $item->description ?: 'Unknown';
    }

    /**
     * Whether a line should be treated as investigation revenue.
     */
    public static function isInvestigation(BillItem $item, ?Collection $investigationsByName = null): bool
    {
        if ($item->investigation_id || $item->item_category === 'investigation') {
            return true;
        }

        return static::matchInvestigationByDescription($item->description, $investigationsByName) !== null;
    }

    /**
     * @return Collection<string, Investigation>
     */
    public static function investigationsByName(): Collection
    {
        return Investigation::query()
            ->get()
            ->keyBy(fn (Investigation $investigation) => static::normalizeName($investigation->name));
    }

    public static function matchInvestigationByDescription(
        ?string $description,
        ?Collection $investigationsByName = null
    ): ?Investigation {
        $normalized = static::normalizeName($description ?? '');
        if ($normalized === '') {
            return null;
        }

        $lookup = $investigationsByName ?? static::investigationsByName();

        return $lookup->get($normalized);
    }

    private static function normalizeName(string $value): string
    {
        return Str::upper(trim($value));
    }
}
