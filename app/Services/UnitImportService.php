<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Facades\Log;

/**
 * Parses CSV or Excel files and upserts Unit records.
 *
 * Standard columns: name, abbreviation, conversion_factor, type, is_active
 * Optional column: base_unit_abbreviation (overrides base unit parsed from name)
 *
 * Name may include the base unit in parentheses, e.g. "10 UNITS (MISC.)" or
 * "INJ PACKING 10 (INJ)". Natt Brothers exports (Name, Short name, Allow decimal)
 * are also supported — conversion is inferred when omitted.
 *
 * Upsert key: abbreviation (case-insensitive)
 */
class UnitImportService
{
    private const VALID_TYPES = ['solid', 'liquid', 'gas', 'packaging'];

    private const NAME_ALIASES = ['name'];

    private const ABBREVIATION_ALIASES = ['abbreviation', 'short_name', 'shortname'];

    private const BASE_UNIT_ALIASES = ['base_unit_abbreviation', 'base_unit', 'baseunit'];

    private const CONVERSION_ALIASES = ['conversion_factor', 'conversion'];

    private const TYPE_ALIASES = ['type'];

    private const ACTIVE_ALIASES = ['is_active', 'active', 'status'];

    public function __construct(
        private readonly SpreadsheetImportReader $reader,
    ) {}

    /**
     * @return array{created: int, updated: int, errors: string[]}
     */
    public function importFromFile(string $absolutePath): array
    {
        try {
            $rows = $this->reader->read($absolutePath);
        } catch (\RuntimeException $e) {
            Log::error('[UnitImport] Failed to read file', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            return $this->abort($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[UnitImport] Unexpected read failure', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            return $this->abort(
                'Could not read the uploaded file. It may be corrupt or in an unsupported format.'
            );
        }

        if ($rows === []) {
            return $this->abort('The uploaded file appears to be empty.');
        }

        return $this->process($rows);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{created: int, updated: int, errors: string[]}
     */
    private function process(array $rows): array
    {
        $headerIndex = $this->locateHeaderRow($rows);

        if ($headerIndex === null) {
            return $this->abort(
                "Invalid file format. The file must contain 'name' and 'abbreviation' (or 'short name') columns. " .
                'Please download the template and use it as a starting point.'
            );
        }

        $header = $this->buildHeaderMap($rows[$headerIndex] ?? []);
        $dataRows = array_slice($rows, $headerIndex + 1);

        $parsedRows = [];
        $errors = [];
        $rowNum = $headerIndex + 1;

        foreach ($dataRows as $rawRow) {
            $rowNum++;

            if ($this->isBlankRow($rawRow)) {
                continue;
            }

            $rawRow = array_pad($rawRow, count($rows[$headerIndex]), '');
            $row = array_map(fn ($value) => $this->toUtf8((string) $value), $rawRow);
            $data = $this->combineRow($header, $row);

            $parsed = $this->parseRow($data, $rowNum, $errors);

            if ($parsed !== null) {
                $parsedRows[] = $parsed;
            }
        }

        if ($parsedRows === []) {
            return [
                'created' => 0,
                'updated' => 0,
                'errors' => $errors ?: ['No valid rows found to import.'],
            ];
        }

        return $this->importParsedRows($parsedRows, $errors);
    }

    /**
     * @param  array<int, array<string, mixed>>  $parsedRows
     * @param  string[]  $errors
     * @return array{created: int, updated: int, errors: string[]}
     */
    private function importParsedRows(array $parsedRows, array $errors): array
    {
        $created = 0;
        $updated = 0;
        $pending = $parsedRows;
        $maxPasses = max(5, (int) ceil(count($parsedRows) / 2) + 2);

        for ($pass = 0; $pass < $maxPasses && $pending !== []; $pass++) {
            $nextPending = [];

            foreach ($pending as $item) {
                $baseUnitId = null;

                if ($item['base_unit_abbreviation'] !== null) {
                    $baseUnitId = $this->resolveBaseUnitId($item['base_unit_abbreviation']);

                    if ($baseUnitId === null) {
                        $nextPending[] = $item;
                        continue;
                    }
                }

                try {
                    $unit = Unit::query()
                        ->whereRaw('UPPER(abbreviation) = ?', [strtoupper($item['abbreviation'])])
                        ->first();

                    $payload = [
                        'name' => $item['name'],
                        'abbreviation' => $item['abbreviation'],
                        'base_unit_id' => $baseUnitId,
                        'conversion_factor' => $item['conversion_factor'],
                        'type' => $item['type'],
                        'is_active' => $item['is_active'],
                    ];

                    if ($unit) {
                        $unit->update($payload);
                        $updated++;
                    } else {
                        Unit::create($payload);
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Row {$item['row_num']} (abbreviation: {$item['abbreviation']}): {$e->getMessage()}";
                    Log::warning('[UnitImport] Row error', [
                        'row' => $item['row_num'],
                        'abbreviation' => $item['abbreviation'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (count($nextPending) === count($pending)) {
                foreach ($nextPending as $item) {
                    $errors[] = "Row {$item['row_num']} (abbreviation: {$item['abbreviation']}): base unit '{$item['base_unit_abbreviation']}' was not found — import base units first.";
                }
                break;
            }

            $pending = $nextPending;
        }

        return compact('created', 'updated', 'errors');
    }

    /**
     * @param  array<string, string>  $data
     * @param  string[]  $errors
     * @return array<string, mixed>|null
     */
    private function parseRow(array $data, int $rowNum, array &$errors): ?array
    {
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            $errors[] = "Row {$rowNum}: 'name' is required — row skipped.";
            return null;
        }

        if (strlen($name) > 255) {
            $errors[] = "Row {$rowNum} (name: {$name}): name exceeds 255 characters — row skipped.";
            return null;
        }

        $abbreviation = trim($data['abbreviation'] ?? '');

        if ($abbreviation === '') {
            $errors[] = "Row {$rowNum} (name: {$name}): 'abbreviation' (or 'short name') is required — row skipped.";
            return null;
        }

        if (strlen($abbreviation) > 10) {
            $errors[] = "Row {$rowNum} (abbreviation: {$abbreviation}): abbreviation exceeds 10 characters — row skipped.";
            return null;
        }

        [$baseFromName, $parenthetical] = $this->parseBaseUnitFromName($name);
        $baseUnitAbbrev = trim($data['base_unit_abbreviation'] ?? '') ?: $baseFromName;

        if ($baseUnitAbbrev !== null) {
            $baseUnitAbbrev = $this->normalizeBaseUnitAbbrev($baseUnitAbbrev);
        }

        $conversionFactor = $this->resolveConversionFactor(
            trim($data['conversion_factor'] ?? ''),
            $name,
            $parenthetical,
            $baseUnitAbbrev !== null,
            $rowNum,
            $abbreviation,
            $errors,
        );

        if ($conversionFactor === null) {
            return null;
        }

        if ($baseUnitAbbrev === null && (float) $conversionFactor !== 1.0) {
            $errors[] = "Row {$rowNum} (abbreviation: {$abbreviation}): conversion_factor must be 1 for base units — row skipped.";
            return null;
        }

        $type = $this->resolveType(trim($data['type'] ?? ''), $name);
        $isActive = $this->parseIsActive($data['is_active'] ?? '1');

        return [
            'row_num' => $rowNum,
            'name' => $name,
            'abbreviation' => $abbreviation,
            'base_unit_abbreviation' => $baseUnitAbbrev,
            'conversion_factor' => $conversionFactor,
            'type' => $type,
            'is_active' => $isActive,
        ];
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function parseBaseUnitFromName(string $name): array
    {
        if (! preg_match('/\(([^)]+)\)\s*$/u', $name, $matches)) {
            return [null, null];
        }

        $parenthetical = trim($matches[1]);

        return [$parenthetical !== '' ? $parenthetical : null, $parenthetical];
    }

    private function normalizeBaseUnitAbbrev(string $abbrev): string
    {
        $abbrev = trim($abbrev);

        if (preg_match('/^(\d+(?:\.\d+)?)(.+)$/u', $abbrev, $matches)) {
            return trim($matches[2], " \t\n\r\0\x0B.");
        }

        return trim($abbrev, " \t\n\r\0\x0B.");
    }

    private function resolveBaseUnitId(string $abbrev): ?int
    {
        $candidates = [
            $abbrev,
            rtrim($abbrev, '.') . '.',
            rtrim($abbrev, '.'),
        ];

        if (str_contains($abbrev, '/')) {
            foreach (explode('/', $abbrev) as $part) {
                $part = trim($part, " \t\n\r\0\x0B.");
                if ($part !== '') {
                    $candidates[] = $part;
                    $candidates[] = rtrim($part, '.') . '.';
                    $candidates[] = rtrim($part, '.');
                }
            }
        }

        foreach (array_unique(array_filter($candidates)) as $candidate) {
            $unit = Unit::query()
                ->whereRaw('UPPER(abbreviation) = ?', [strtoupper($candidate)])
                ->first();

            if ($unit !== null) {
                return $unit->id;
            }
        }

        return null;
    }

    private function resolveConversionFactor(
        string $rawValue,
        string $name,
        ?string $parenthetical,
        bool $hasBaseUnit,
        int $rowNum,
        string $abbreviation,
        array &$errors,
    ): ?float {
        if ($rawValue !== '') {
            if (! is_numeric($rawValue) || (float) $rawValue <= 0) {
                $errors[] = "Row {$rowNum} (abbreviation: {$abbreviation}): conversion_factor must be a number greater than zero — row skipped.";
                return null;
            }

            return round((float) $rawValue, 4);
        }

        if (! $hasBaseUnit) {
            return 1.0;
        }

        if ($parenthetical !== null && preg_match('/^(\d+(?:\.\d+)?)/u', $parenthetical, $matches)) {
            return round((float) $matches[1], 4);
        }

        if (preg_match('/\b(\d+(?:\.\d+)?)\s*(?:UNITS?|PACKING|PKT|PACK)\b/i', $name, $matches)) {
            return round((float) $matches[1], 4);
        }

        if (preg_match('/^(\d+(?:\.\d+)?)\s/u', $name, $matches)) {
            return round((float) $matches[1], 4);
        }

        return 1.0;
    }

    private function resolveType(string $rawType, string $name): string
    {
        $type = strtolower(trim($rawType));

        if (in_array($type, self::VALID_TYPES, true)) {
            return $type;
        }

        $upperName = strtoupper($name);

        if (str_contains($upperName, 'DROP') || str_contains($upperName, 'SYR') || str_contains($upperName, 'ML')) {
            return 'liquid';
        }

        if (str_contains($upperName, 'TAB') || str_contains($upperName, 'CAP') || str_contains($upperName, 'TABLET')) {
            return 'solid';
        }

        if (str_contains($upperName, 'GAS') || str_contains($upperName, 'INHAL')) {
            return 'gas';
        }

        return 'packaging';
    }

    private function parseIsActive(string $value): bool
    {
        return ! in_array(strtolower(trim($value)), ['0', 'false', 'no', 'inactive'], true);
    }

    /**
     * @param  array<int, string>  $headerRow
     * @return array<int, string>
     */
    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $column) {
            $key = $this->normalizeColumnKey((string) $column);

            if ($key === '') {
                continue;
            }

            if (in_array($key, self::NAME_ALIASES, true)) {
                $map[$index] = 'name';
                continue;
            }

            if (in_array($key, self::ABBREVIATION_ALIASES, true)) {
                $map[$index] = 'abbreviation';
                continue;
            }

            if (in_array($key, self::BASE_UNIT_ALIASES, true)) {
                $map[$index] = 'base_unit_abbreviation';
                continue;
            }

            if (in_array($key, self::CONVERSION_ALIASES, true)) {
                $map[$index] = 'conversion_factor';
                continue;
            }

            if (in_array($key, self::TYPE_ALIASES, true)) {
                $map[$index] = 'type';
                continue;
            }

            if (in_array($key, self::ACTIVE_ALIASES, true)) {
                $map[$index] = 'is_active';
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     */
    private function locateHeaderRow(array $rows): ?int
    {
        foreach ($rows as $index => $row) {
            $keys = array_map(
                fn ($column) => $this->normalizeColumnKey((string) $column),
                $row
            );

            $hasName = in_array('name', $keys, true);
            $hasAbbrev = ! empty(array_intersect($keys, self::ABBREVIATION_ALIASES));

            if ($hasName && $hasAbbrev) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, string>  $headerMap
     * @param  array<int, string>  $row
     * @return array<string, string>
     */
    private function combineRow(array $headerMap, array $row): array
    {
        $data = [];

        foreach ($headerMap as $index => $key) {
            $data[$key] = $row[$index] ?? '';
        }

        return $data;
    }

    private function normalizeColumnKey(string $column): string
    {
        $column = strtolower(trim($this->toUtf8($column)));
        $column = preg_replace('/[^a-z0-9]+/u', '_', $column) ?? '';
        $column = trim($column, '_');

        return $column;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function isBlankRow(array $row): bool
    {
        return empty(array_filter(array_map('trim', $row)));
    }

    private function toUtf8(string $value): string
    {
        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');

        return mb_check_encoding($converted, 'UTF-8')
            ? $converted
            : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    /**
     * @return array{created: int, updated: int, errors: string[]}
     */
    private function abort(string $message): array
    {
        return ['created' => 0, 'updated' => 0, 'errors' => [$message]];
    }
}
