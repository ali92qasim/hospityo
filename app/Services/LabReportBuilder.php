<?php

namespace App\Services;

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Models\LabTest;
use App\Support\LabReportPrintSettings;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LabReportBuilder
{
    /** Rows available for test panels on the first page (after letterhead + patient box). */
    public const FIRST_PAGE_ROW_BUDGET = 8;

    /** Rows available for test panels on continuation pages. */
    public const PAGE_ROW_BUDGET = 30;

    /** Estimated rows for a test section heading and spacing. */
    public const SECTION_HEADER_ROWS = 2;

    /** Estimated rows for section bottom padding. */
    public const SECTION_FOOTER_ROWS = 1;

    /** Estimated rows for one previous-value line under a parameter. */
    public const PREVIOUS_VALUE_ROW_COST = 1;

    /** Max characters for clinical notes in the patient detail band. */
    public const PATIENT_BAND_NOTE_LIMIT = 120;

    /**
     * Build a multi-test report for an investigation order.
     *
     * @return array{
     *     order: LabOrder,
     *     pages: array<int, array{sections: array<int, array<string, mixed>>, row_cost: int}>,
     *     primaryResult: ?LabResult,
     *     comments: array<int, string>,
     *     patient_band: array<string, mixed>
     * }
     */
    public static function build(LabOrder $order): array
    {
        $order->loadMissing(['patient', 'doctor', 'visit', 'items.labTest']);

        $labResults = LabResult::query()
            ->where('lab_order_id', $order->id)
            ->with(['resultItems.parameter.labTest', 'technician', 'pathologist', 'reviewers'])
            ->orderBy('id')
            ->get();

        static::attachPreviousValues($order, $labResults);

        $sections = static::buildSections($order, $labResults);
        $pages = static::packIntoPages($sections);
        $reviewers = static::orderedReviewers($labResults);

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
            'patient_band' => static::buildPatientBand($order, $labResults, $sections, $reviewers),
            'reviewers' => $reviewers,
        ];
    }

    /**
     * @param  Collection<int, LabResult>  $labResults
     * @return list<array{name: string, qualification: ?string, specialization: ?string}>
     */
    public static function orderedReviewers(Collection $labResults): array
    {
        return $labResults
            ->flatMap(function (LabResult $result) {
                return $result->reviewers->map(fn ($doctor) => [
                    'id' => $doctor->id,
                    'name' => trim((string) $doctor->name),
                    'qualification' => filled($doctor->qualification) ? trim((string) $doctor->qualification) : null,
                    'specialization' => filled($doctor->specialization) ? trim((string) $doctor->specialization) : null,
                    'result_id' => $result->id,
                    'sort_order' => (int) ($doctor->pivot->sort_order ?? 0),
                ]);
            })
            ->sortBy([
                ['result_id', 'asc'],
                ['sort_order', 'asc'],
            ])
            ->unique('id')
            ->map(fn (array $row) => [
                'name' => $row['name'],
                'qualification' => $row['qualification'],
                'specialization' => $row['specialization'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, LabResult>  $labResults
     * @param  array<int, array<string, mixed>>  $sections
     * @param  list<array{name: string, qualification: ?string, specialization: ?string}>  $reviewers
     * @return array{
     *     registration_location: string,
     *     registration_date: mixed,
     *     case_number: ?string,
     *     note: ?string,
     *     department: ?string,
     *     consultant: ?string
     * }
     */
    public static function buildPatientBand(LabOrder $order, Collection $labResults, array $sections, ?array $reviewers = null): array
    {
        $hospitalName = trim((string) setting('hospital_name', config('app.name', 'Hospital Management System')));
        $hospitalAddress = trim((string) setting('hospital_address', ''));
        $registrationLocation = $hospitalName;
        if ($hospitalAddress !== '') {
            $registrationLocation .= ', '.$hospitalAddress;
        }

        $registrationDate = $order->visit?->visit_datetime ?? $order->ordered_at;

        $note = filled($order->clinical_notes)
            ? Str::limit(trim((string) $order->clinical_notes), self::PATIENT_BAND_NOTE_LIMIT)
            : null;

        $department = collect($sections)
            ->map(fn (array $section) => $section['investigation']->category ?? null)
            ->filter()
            ->unique()
            ->map(fn (string $category) => Str::title(str_replace(['_', '-'], ' ', $category)))
            ->values()
            ->implode(', ');

        $reviewers ??= static::orderedReviewers($labResults);
        $consultant = $reviewers[0]['name'] ?? null;

        return [
            'registration_location' => $registrationLocation,
            'registration_date' => $registrationDate,
            'case_number' => $order->order_number,
            'note' => $note,
            'department' => $department !== '' ? $department : null,
            'consultant' => $consultant ? (string) $consultant : null,
        ];
    }

    /**
     * @param  Collection<int, LabResult>  $labResults
     */
    public static function attachPreviousValues(LabOrder $order, Collection $labResults): void
    {
        $map = static::loadPreviousValuesByParameterId($order, $labResults);

        foreach ($labResults as $result) {
            foreach ($result->resultItems as $item) {
                $parameterId = (int) $item->lab_test_parameter_id;
                $item->previous_values = $map[$parameterId] ?? [];
            }
        }
    }

    /**
     * @param  Collection<int, LabResult>  $labResults
     * @return array<int, list<array{value: string, unit: ?string, flag: ?string, tested_at: mixed}>>
     */
    public static function loadPreviousValuesByParameterId(LabOrder $order, Collection $labResults): array
    {
        $parameterIds = $labResults
            ->flatMap(fn (LabResult $result) => $result->resultItems)
            ->pluck('lab_test_parameter_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($parameterIds === []) {
            return [];
        }

        $n = max(1, min(5, (int) (LabReportPrintSettings::get()['previous_values_count'] ?? 3)));

        $rows = LabResultItem::query()
            ->select([
                'lab_result_items.id',
                'lab_result_items.lab_result_id',
                'lab_result_items.lab_test_parameter_id',
                'lab_result_items.value',
                'lab_result_items.unit',
                'lab_result_items.flag',
                'lab_results.tested_at as prior_tested_at',
            ])
            ->join('lab_results', 'lab_results.id', '=', 'lab_result_items.lab_result_id')
            ->join('lab_orders', 'lab_orders.id', '=', 'lab_results.lab_order_id')
            ->where('lab_orders.patient_id', $order->patient_id)
            ->where('lab_orders.id', '!=', $order->id)
            ->whereIn('lab_result_items.lab_test_parameter_id', $parameterIds)
            ->whereIn('lab_results.status', ['reported', 'final'])
            ->orderByDesc('lab_results.tested_at')
            ->orderByDesc('lab_results.id')
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $parameterId = (int) $row->lab_test_parameter_id;
            if (! isset($grouped[$parameterId])) {
                $grouped[$parameterId] = [];
            }
            if (count($grouped[$parameterId]) >= $n) {
                continue;
            }

            $grouped[$parameterId][] = [
                'value' => (string) $row->value,
                'unit' => $row->unit,
                'flag' => $row->flag,
                'tested_at' => $row->prior_tested_at,
            ];
        }

        return $grouped;
    }

    /**
     * @param  Collection<int, LabResult>  $labResults
     * @return array<int, array<string, mixed>>
     */
    public static function buildSections(LabOrder $order, Collection $labResults): array
    {
        $itemsByInvestigation = $labResults
            ->flatMap(fn (LabResult $result) => $result->resultItems)
            ->groupBy(function ($item) {
                $investigationId = $item->parameter?->lab_test_id;

                return $investigationId ?: 'unassigned-'.$item->id;
            });

        $investigationOrder = $order->items
            ->sortBy(fn ($item) => $item->labTest?->name ?? '')
            ->values();

        $sections = [];

        foreach ($investigationOrder as $orderItem) {
            $investigation = $orderItem->labTest;
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

        foreach ($itemsByInvestigation as $group) {
            $firstItem = $group->first();
            $investigation = $firstItem->parameter?->labTest;
            $label = $investigation?->name ?? 'Lab test';

            $sections[] = static::makeSection(
                $investigation ?? new LabTest(['name' => $label]),
                $group->values()->all()
            );
        }

        return $sections;
    }

    /**
     * @param  array<int, mixed>  $resultItems
     * @return array<string, mixed>
     */
    public static function makeSection(LabTest $investigation, array $resultItems): array
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
