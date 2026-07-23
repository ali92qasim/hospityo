<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineBrand;
use App\Models\MedicineCategory;
use App\Models\Unit;
use Illuminate\Support\Facades\Log;

/**
 * Parses CSV or Excel files and upserts Medicine records.
 *
 * Required columns: name
 * Optional columns: sku (auto-generated when empty), generic_name, brand_name, category_code, strength,
 *   base_unit_abbreviation, purchase_unit_abbreviation, dispensing_unit_abbreviation,
 *   selling_price, reorder_level, status, manage_stock
 *
 * Upsert key: sku (generated when omitted)
 */
class MedicineImportService
{
    private const ALLOWED_STATUS = ['active', 'inactive'];

    private const CHUNK_SIZE = 250;

    public function __construct(
        private readonly SpreadsheetImportReader $reader,
    ) {}

    /**
     * @param  callable(int $processed, int $total, int $created, int $updated): void|null  $onProgress
     * @return array{created: int, updated: int, errors: string[], total: int}
     */
    public function importFromFile(string $absolutePath, ?callable $onProgress = null): array
    {
        try {
            $rows = $this->reader->read($absolutePath);
        } catch (\RuntimeException $e) {
            Log::error('[MedicineImport] Failed to read file', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            return $this->abort($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[MedicineImport] Unexpected read failure', [
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

        return $this->process($rows, $onProgress);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @param  callable(int, int, int, int): void|null  $onProgress
     * @return array{created: int, updated: int, errors: string[], total: int}
     */
    private function process(array $rows, ?callable $onProgress): array
    {
        $rawHeader = array_shift($rows);
        $header = array_map(
            fn ($column) => strtolower(trim($this->toUtf8((string) $column))),
            $rawHeader ?? []
        );

        if (! in_array('name', $header, true)) {
            $found = implode(', ', array_filter($header));

            return $this->abort(
                "Invalid file format. The file must contain a 'name' column. " .
                "Columns found: [{$found}]. " .
                'Please download the template and use it as a starting point.'
            );
        }

        $dataRows = array_values(array_filter($rows, fn ($row) => ! $this->isBlankRow($row)));
        $total = count($dataRows);

        $categories = MedicineCategory::pluck('id', 'code')
            ->mapWithKeys(fn ($id, $code) => [strtoupper(trim($code)) => $id])
            ->all();

        $brands = MedicineBrand::query()
            ->get(['id', 'name'])
            ->mapWithKeys(fn (MedicineBrand $brand) => [mb_strtolower(trim($brand->name)) => $brand->id])
            ->all();

        $units = Unit::pluck('id', 'abbreviation')
            ->mapWithKeys(fn ($id, $abbreviation) => [strtoupper(trim($abbreviation)) => $id])
            ->all();

        $created = 0;
        $updated = 0;
        $errors = [];
        $processed = 0;
        $pendingBatch = [];
        $rowNum = 1;

        $flushBatch = function () use (&$pendingBatch, &$created, &$updated, &$processed, $total, $onProgress) {
            if ($pendingBatch === []) {
                return;
            }

            [$batchCreated, $batchUpdated] = $this->upsertBatch($pendingBatch);
            $created += $batchCreated;
            $updated += $batchUpdated;
            $pendingBatch = [];

            if ($onProgress !== null) {
                $onProgress($processed, $total, $created, $updated);
            }
        };

        foreach ($dataRows as $rawRow) {
            $rowNum++;

            $rawRow = array_pad($rawRow, count($header), '');
            $row = array_map(fn ($value) => $this->toUtf8((string) $value), $rawRow);
            $data = array_combine($header, $row);

            $record = $this->parseRow($data, $rowNum, $categories, $brands, $units, $errors);

            $processed++;

            if ($record !== null) {
                $pendingBatch[] = $record;
            }

            if (count($pendingBatch) >= self::CHUNK_SIZE) {
                $flushBatch();
            }
        }

        $flushBatch();

        if ($onProgress !== null && $total === 0) {
            $onProgress(0, 0, 0, 0);
        }

        return compact('created', 'updated', 'errors', 'total');
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, int>  $categories
     * @param  array<string, int>  $brands
     * @param  array<string, int>  $units
     * @param  string[]  $errors
     * @return array<string, mixed>|null
     */
    private function parseRow(
        array $data,
        int $rowNum,
        array $categories,
        array $brands,
        array $units,
        array &$errors,
    ): ?array {
        $sku = trim($data['sku'] ?? '');
        $name = trim($data['name'] ?? '');

        if ($name === '') {
            $errors[] = "Row {$rowNum}: 'name' is required — row skipped.";
            return null;
        }

        $genericName = trim($data['generic_name'] ?? '') ?: null;
        if ($genericName !== null && strlen($genericName) > 255) {
            $errors[] = "Row {$rowNum}: generic_name exceeds 255 characters — row skipped.";
            return null;
        }

        $brandId = $this->resolveBrandId($data['brand_name'] ?? '', $brands, $rowNum, $sku ?: 'AUTO', $errors);
        $categoryId = $this->resolveCategoryId($data['category_code'] ?? '', $categories, $rowNum, $sku ?: 'AUTO', $errors);

        $strength = trim($data['strength'] ?? '');
        if (strlen($strength) > 255) {
            $errors[] = "Row {$rowNum}: strength exceeds 255 characters — row skipped.";
            return null;
        }

        if ($sku === '') {
            $categoryCode = trim($data['category_code'] ?? '');
            $brandName = trim($data['brand_name'] ?? '');
            $baseSku = Medicine::buildSkuFromAttributes(
                $name,
                $strength,
                $categoryCode,
                $brandName,
                $genericName,
            );
            $sku = Medicine::uniqueSku($baseSku);
        }

        if (strlen($sku) > 255) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): SKU exceeds 255 characters — row skipped.";
            return null;
        }

        if (strlen($name) > 255) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): name exceeds 255 characters — row skipped.";
            return null;
        }

        $baseUnitAbbrev = strtoupper(trim($data['base_unit_abbreviation'] ?? ''));
        $purchaseUnitAbbrev = strtoupper(trim($data['purchase_unit_abbreviation'] ?? '')) ?: $baseUnitAbbrev;
        $dispensingUnitAbbrev = strtoupper(trim($data['dispensing_unit_abbreviation'] ?? '')) ?: $baseUnitAbbrev;

        $baseUnitId = $this->resolveUnitId($baseUnitAbbrev, $units, 'base_unit_abbreviation', $rowNum, $sku, $errors);
        $purchaseUnitId = $this->resolveUnitId($purchaseUnitAbbrev, $units, 'purchase_unit_abbreviation', $rowNum, $sku, $errors);
        $dispensingUnitId = $this->resolveUnitId($dispensingUnitAbbrev, $units, 'dispensing_unit_abbreviation', $rowNum, $sku, $errors);

        $sellingPrice = $this->parseSellingPrice($data['selling_price'] ?? '', $rowNum, $sku, $errors);
        $reorderLevel = $this->parseReorderLevel($data['reorder_level'] ?? '', $rowNum, $sku, $errors);
        $status = $this->parseStatus($data['status'] ?? 'active', $rowNum, $sku, $errors);
        $manageStock = $this->parseManageStock($data['manage_stock'] ?? '1');

        if ($sellingPrice === false || $reorderLevel === false || $status === false) {
            return null;
        }

        return [
            'sku' => $sku,
            'name' => $name,
            'generic_name' => $genericName,
            'brand_id' => $brandId,
            'category_id' => $categoryId,
            'strength' => $strength,
            'selling_price' => $sellingPrice,
            'base_unit_id' => $baseUnitId,
            'purchase_unit_id' => $purchaseUnitId,
            'dispensing_unit_id' => $dispensingUnitId,
            'reorder_level' => $reorderLevel,
            'status' => $status,
            'manage_stock' => $manageStock,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @return array{0: int, 1: int}
     */
    private function upsertBatch(array $batch): array
    {
        $skus = array_column($batch, 'sku');
        $existingSkus = Medicine::query()
            ->whereIn('sku', $skus)
            ->pluck('sku')
            ->flip()
            ->all();

        $batchCreated = 0;
        $batchUpdated = 0;
        $now = now();

        foreach ($batch as &$record) {
            if (isset($existingSkus[$record['sku']])) {
                $batchUpdated++;
            } else {
                $batchCreated++;
            }

            $record['manage_stock'] = $record['manage_stock'] ? 1 : 0;
            $record['created_at'] = $now;
            $record['updated_at'] = $now;
        }
        unset($record);

        try {
            Medicine::upsert(
                $batch,
                ['sku'],
                [
                    'name',
                    'generic_name',
                    'brand_id',
                    'category_id',
                    'strength',
                    'selling_price',
                    'base_unit_id',
                    'purchase_unit_id',
                    'dispensing_unit_id',
                    'reorder_level',
                    'status',
                    'manage_stock',
                    'updated_at',
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('[MedicineImport] Batch upsert failed, falling back to row-by-row', [
                'error' => $e->getMessage(),
                'batch_size' => count($batch),
            ]);

            return $this->upsertBatchFallback($batch, $existingSkus);
        }

        return [$batchCreated, $batchUpdated];
    }

    /**
     * @param  array<int, array<string, mixed>>  $batch
     * @param  array<string, int>  $existingSkus
     * @return array{0: int, 1: int}
     */
    private function upsertBatchFallback(array $batch, array $existingSkus): array
    {
        $batchCreated = 0;
        $batchUpdated = 0;

        foreach ($batch as $record) {
            $isUpdate = isset($existingSkus[$record['sku']]);

            try {
                Medicine::updateOrCreate(
                    ['sku' => $record['sku']],
                    [
                        'name' => $record['name'],
                        'generic_name' => $record['generic_name'],
                        'brand_id' => $record['brand_id'],
                        'category_id' => $record['category_id'],
                        'strength' => $record['strength'],
                        'selling_price' => $record['selling_price'],
                        'base_unit_id' => $record['base_unit_id'],
                        'purchase_unit_id' => $record['purchase_unit_id'],
                        'dispensing_unit_id' => $record['dispensing_unit_id'],
                        'reorder_level' => $record['reorder_level'],
                        'status' => $record['status'],
                        'manage_stock' => (bool) $record['manage_stock'],
                    ]
                );

                if ($isUpdate) {
                    $batchUpdated++;
                } else {
                    $batchCreated++;
                    $existingSkus[$record['sku']] = 1;
                }
            } catch (\Throwable $e) {
                Log::warning('[MedicineImport] Row fallback error', [
                    'sku' => $record['sku'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [$batchCreated, $batchUpdated];
    }

    /**
     * @param  array<string, int>  $brands
     */
    private function resolveBrandId(string $brandName, array $brands, int $rowNum, string $sku, array &$errors): ?int
    {
        $brandName = trim($brandName);
        if ($brandName === '') {
            return null;
        }

        $brandId = $brands[mb_strtolower($brandName)] ?? null;
        if ($brandId === null) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): brand '{$brandName}' not found — imported without brand.";
        }

        return $brandId;
    }

    /**
     * @param  array<string, int>  $categories
     */
    private function resolveCategoryId(string $categoryCode, array $categories, int $rowNum, string $sku, array &$errors): ?int
    {
        $categoryCode = strtoupper(trim($categoryCode));
        if ($categoryCode === '') {
            return null;
        }

        $categoryId = $categories[$categoryCode] ?? null;
        if ($categoryId === null) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): category '{$categoryCode}' not found — imported without category.";
        }

        return $categoryId;
    }

    /**
     * @param  array<string, int>  $units
     */
    private function resolveUnitId(string $abbreviation, array $units, string $column, int $rowNum, string $sku, array &$errors): ?int
    {
        if ($abbreviation === '') {
            return null;
        }

        $unitId = $units[$abbreviation] ?? null;
        if ($unitId === null) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): unit '{$abbreviation}' ({$column}) not found — imported without that unit.";
        }

        return $unitId;
    }

    private function parseSellingPrice(string $value, int $rowNum, string $sku, array &$errors): float|false
    {
        $value = trim($value);
        if ($value === '') {
            return 0.0;
        }

        if (! is_numeric($value)) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): invalid selling_price '{$value}' — row skipped.";
            return false;
        }

        return max(0.0, round((float) $value, 2));
    }

    private function parseReorderLevel(string $value, int $rowNum, string $sku, array &$errors): int|false
    {
        $value = trim($value);
        if ($value === '') {
            return 10;
        }

        if (! ctype_digit($value) && ! (is_numeric($value) && (int) $value == $value)) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): invalid reorder_level '{$value}' — row skipped.";
            return false;
        }

        return max(0, (int) $value);
    }

    private function parseStatus(string $value, int $rowNum, string $sku, array &$errors): string|false
    {
        $status = strtolower(trim($value ?: 'active'));
        if (! in_array($status, self::ALLOWED_STATUS, true)) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): invalid status '{$value}' — row skipped.";
            return false;
        }

        return $status;
    }

    private function parseManageStock(string $value): bool
    {
        return ! in_array(strtolower(trim($value)), ['0', 'false', 'no', 'inactive'], true);
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
     * @return array{created: int, updated: int, errors: string[], total: int}
     */
    private function abort(string $message): array
    {
        return ['created' => 0, 'updated' => 0, 'errors' => [$message], 'total' => 0];
    }
}
