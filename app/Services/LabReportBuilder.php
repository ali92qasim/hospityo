<?php

namespace App\Services;

use App\Models\Investigation;
use App\Models\InvestigationOrder;
use App\Models\LabResult;
use Illuminate\Support\Collection;

class LabReportBuilder
{
    /** Rows available for test panels on the first page (after letterhead + patient box). */
    public const FIRST_PAGE_ROW_BUDGET = 14;

    /** Rows available for test panels on continuation pages. */
    public const PAGE_ROW_BUDGET = 30;

    /** Estimated rows for a test section heading and spacing. */
    public const SECTION_HEADER_ROWS = 2;

    /** Estimated rows for section bottom padding. */
    public const SECTION_FOOTER_ROWS = 1;

    /**
     * Build a multi-test report for an investigation order.
     *
     * @return array{
     *     order: InvestigationOrder,
     *     pages: array<int, array{sections: array<int, array<string, mixed>>, row_cost: int}>,
     *     primaryResult: ?LabResult,
     *     comments: array<int, string>
     * }
     */
    public static function build(InvestigationOrder $order): array
    {
        $order->loadMissing(['patient', 'doctor', 'visit', 'items.investigation']);

        $labResults = LabResult::query()
            ->where('investigation_order_id', $order->id)
            ->with(['resultItems.parameter.investigation', 'technician', 'pathologist'])
            ->orderBy('id')
            ->get();

        $sections = static::buildSections($order, $labResults);
        $pages = static::packIntoPages($sections);

        return [
            'order' => $order,
            'pages' => $pages,
            'primaryResult' => $labResults->last(),
            'comments' => $labResults
                ->pluck('comments')
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  Collection<int, LabResult>  $labResults
     * @return array<int, array<string, mixed>>
     */
    public static function buildSections(InvestigationOrder $order, Collection $labResults): array
    {
        $itemsByInvestigation = $labResults
            ->flatMap(fn (LabResult $result) => $result->resultItems)
            ->groupBy(function ($item) {
                $investigationId = $item->parameter?->lab_test_id;

                return $investigationId ?: 'unassigned-' . $item->id;
            });

        $investigationOrder = $order->items
            ->sortBy(fn ($item) => $item->investigation?->name ?? '')
            ->values();

        $sections = [];

        foreach ($investigationOrder as $orderItem) {
            $investigation = $orderItem->investigation;
            if (! $investigation) {
                continue;
            }

            $resultItems = $itemsByInvestigation->get($investigation->id, collect());
            if ($resultItems->isEmpty()) {
                continue;
            }

            $sections[] = static::makeSection($investigation, $resultItems->values()->all());
            $itemsByInvestigation->forget($investigation->id);
        }

        // Any remaining groups not matched to an order item (legacy data).
        foreach ($itemsByInvestigation as $group) {
            $firstItem = $group->first();
            $investigation = $firstItem->parameter?->investigation;
            $label = $investigation?->name ?? 'Investigation';

            $sections[] = static::makeSection(
                $investigation ?? new Investigation(['name' => $label]),
                $group->values()->all()
            );
        }

        return $sections;
    }

    /**
     * @param  array<int, mixed>  $resultItems
     * @return array<string, mixed>
     */
    public static function makeSection(Investigation $investigation, array $resultItems): array
    {
        $rowCost = static::SECTION_HEADER_ROWS
            + count($resultItems)
            + static::SECTION_FOOTER_ROWS;

        return [
            'investigation' => $investigation,
            'items' => $resultItems,
            'row_cost' => $rowCost,
            'is_large' => $rowCost > static::PAGE_ROW_BUDGET,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $sections
     * @return array<int, array{sections: array<int, array<string, mixed>>, row_cost: int}>
     */
    public static function packIntoPages(array $sections): array
    {
        if ($sections === []) {
            return [];
        }

        $firstPageSections = [];
        $firstPageCost = 0;
        $remaining = [];

        foreach ($sections as $section) {
            $tooLargeForFirstPage = $section['row_cost'] > static::FIRST_PAGE_ROW_BUDGET;

            if ($tooLargeForFirstPage) {
                $remaining[] = $section;
                continue;
            }

            if (($firstPageCost + $section['row_cost']) <= static::FIRST_PAGE_ROW_BUDGET) {
                $firstPageSections[] = $section;
                $firstPageCost += $section['row_cost'];
                continue;
            }

            $remaining[] = $section;
        }

        if ($firstPageSections === [] && $remaining !== []) {
            $firstPageSections[] = array_shift($remaining);
            $firstPageCost = $firstPageSections[0]['row_cost'];
        }

        $pages = [];

        if ($firstPageSections !== []) {
            $pages[] = [
                'sections' => $firstPageSections,
                'row_cost' => $firstPageCost,
            ];
        }

        $currentPage = ['sections' => [], 'row_cost' => 0];

        foreach ($remaining as $section) {
            if ($section['is_large']) {
                if ($currentPage['sections'] !== []) {
                    $pages[] = $currentPage;
                    $currentPage = ['sections' => [], 'row_cost' => 0];
                }

                $pages[] = ['sections' => [$section], 'row_cost' => $section['row_cost']];
                continue;
            }

            if ($currentPage['sections'] !== []
                && ($currentPage['row_cost'] + $section['row_cost']) > static::PAGE_ROW_BUDGET) {
                $pages[] = $currentPage;
                $currentPage = ['sections' => [], 'row_cost' => 0];
            }

            if ($currentPage['sections'] === []
                && $section['row_cost'] > static::PAGE_ROW_BUDGET) {
                $pages[] = ['sections' => [$section], 'row_cost' => $section['row_cost']];
                continue;
            }

            $currentPage['sections'][] = $section;
            $currentPage['row_cost'] += $section['row_cost'];
        }

        if ($currentPage['sections'] !== []) {
            $pages[] = $currentPage;
        }

        return $pages;
    }
}
