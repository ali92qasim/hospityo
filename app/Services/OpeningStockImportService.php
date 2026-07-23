<?php

namespace App\Services;

use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk opening stock import — creates stock_in batches (one-time per tenant).
 *
 * Required columns: sku, quantity, unit_abbreviation, unit_cost, batch_no, expiry_date
 * Optional columns: supplier, reference_no, notes
 */
class OpeningStockImportService
{
    private const CHUNK_SIZE = 200;

    public function __construct(
        private readonly SpreadsheetImportReader $reader,
    ) {}

    /**
     * @param  callable(int $processed, int $total, int $created): void|null  $onProgress
     * @return array{created: int, errors: string[], total: int}
     */
    public function importFromFile(string $absolutePath, int $userId, ?callable $onProgress = null): array
    {
        if (OpeningStockService::isLocked()) {
            return $this->abort('Opening stock has already been imported for this clinic. Further bulk imports are not allowed.');
        }

        try {
            $rows = $this->reader->read($absolutePath);
        } catch (\RuntimeException $e) {
            Log::error('[OpeningStockImport] Failed to read file', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            return $this->abort($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[OpeningStockImport] Unexpected read failure', [
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

        return $this->process($rows, $userId, $onProgress);
    }

    /**
     * @param  array<int, array<int, string>>  $rows
     * @param  callable(int, int, int): void|null  $onProgress
     * @return array{created: int, errors: string[], total: int}
     */
    private function process(array $rows, int $userId, ?callable $onProgress): array
    {
        $rawHeader = array_shift($rows);
        $header = array_map(
            fn ($column) => strtolower(trim($this->toUtf8((string) $column))),
            $rawHeader ?? []
        );

        $required = ['sku', 'quantity', 'unit_abbreviation', 'unit_cost', 'batch_no', 'expiry_date'];
        $missing = array_diff($required, $header);

        if ($missing !== []) {
            $found = implode(', ', array_filter($header));

            return $this->abort(
                'Invalid file format. Required columns: ' . implode(', ', $required) . ". " .
                "Columns found: [{$found}]. Please download the template and use it as a starting point."
            );
        }

        $dataRows = array_values(array_filter($rows, fn ($row) => ! $this->isBlankRow($row)));
        $total = count($dataRows);

        if ($total === 0) {
            return $this->abort('The uploaded file contains no data rows.');
        }

        $medicines = Medicine::query()
            ->where('manage_stock', true)
            ->get(['id', 'sku', 'name', 'manage_stock'])
            ->mapWithKeys(fn (Medicine $medicine) => [strtoupper(trim($medicine->sku)) => $medicine])
            ->all();

        $units = Unit::query()
            ->where('is_active', true)
            ->get(['id', 'abbreviation', 'conversion_factor'])
            ->mapWithKeys(fn (Unit $unit) => [strtoupper(trim($unit->abbreviation)) => $unit])
            ->all();

        $errors = [];
        $pendingRecords = [];
        $processed = 0;
        $rowNum = 1;

        foreach ($dataRows as $rawRow) {
            $rowNum++;
            $processed++;

            $rawRow = array_pad($rawRow, count($header), '');
            $row = array_map(fn ($value) => $this->toUtf8((string) $value), $rawRow);
            $data = array_combine($header, $row);

            $record = $this->parseRow($data, $rowNum, $medicines, $units, $errors, $userId);

            if ($record !== null) {
                $pendingRecords[] = $record;
            }

            if ($onProgress !== null && ($processed % self::CHUNK_SIZE === 0 || $processed === $total)) {
                $onProgress($processed, $total, 0);
            }
        }

        if ($pendingRecords === []) {
            return [
                'created' => 0,
                'errors' => $errors ?: ['No valid rows found to import.'],
                'total' => $total,
            ];
        }

        if ($errors !== []) {
            return [
                'created' => 0,
                'errors' => array_merge(
                    ['Import aborted — fix the errors below and upload again. No stock was recorded.'],
                    $errors
                ),
                'total' => $total,
            ];
        }

        try {
            $created = DB::transaction(function () use ($pendingRecords, $userId) {
                if (OpeningStockService::isLocked()) {
                    throw new \RuntimeException('Opening stock has already been imported for this clinic.');
                }

                foreach ($pendingRecords as $record) {
                    InventoryTransaction::create($record);
                }

                $count = count($pendingRecords);
                OpeningStockService::lock($userId, $count);

                return $count;
            });
        } catch (\RuntimeException $e) {
            return $this->abort($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[OpeningStockImport] Transaction failed', ['error' => $e->getMessage()]);

            return $this->abort('Import failed while saving stock. No changes were recorded. Please try again.');
        }

        if ($onProgress !== null) {
            $onProgress($total, $total, $created);
        }

        return [
            'created' => $created,
            'errors' => $errors,
            'total' => $total,
        ];
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<string, Medicine>  $medicines
     * @param  array<string, Unit>  $units
     * @param  string[]  $errors
     * @return array<string, mixed>|null
     */
    private function parseRow(
        array $data,
        int $rowNum,
        array $medicines,
        array $units,
        array &$errors,
        int $userId,
    ): ?array {
        $sku = strtoupper(trim($data['sku'] ?? ''));

        if ($sku === '') {
            $errors[] = "Row {$rowNum}: 'sku' is required — row skipped.";
            return null;
        }

        $medicine = $medicines[$sku] ?? null;

        if ($medicine === null) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): medicine not found or stock management is disabled.";
            return null;
        }

        $quantityRaw = trim($data['quantity'] ?? '');
        if ($quantityRaw === '' || ! is_numeric($quantityRaw) || (float) $quantityRaw <= 0 || (int) $quantityRaw != $quantityRaw) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'quantity' must be a whole number greater than zero.";
            return null;
        }
        $quantity = (int) $quantityRaw;

        $unitAbbrev = strtoupper(trim($data['unit_abbreviation'] ?? ''));
        $unit = $units[$unitAbbrev] ?? null;

        if ($unit === null) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): unit '{$unitAbbrev}' not found — import units first.";
            return null;
        }

        $unitCostRaw = trim($data['unit_cost'] ?? '');
        if ($unitCostRaw === '' || ! is_numeric($unitCostRaw) || (float) $unitCostRaw < 0) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'unit_cost' must be a number zero or greater.";
            return null;
        }
        $unitCost = round((float) $unitCostRaw, 2);

        $batchNo = trim($data['batch_no'] ?? '');
        if ($batchNo === '') {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'batch_no' is required.";
            return null;
        }
        if (strlen($batchNo) > 100) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'batch_no' exceeds 100 characters.";
            return null;
        }

        $expiryRaw = trim($data['expiry_date'] ?? '');
        $expiryDate = $this->parseDate($expiryRaw);
        if ($expiryDate === false) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): invalid 'expiry_date' '{$expiryRaw}' — use YYYY-MM-DD.";
            return null;
        }

        $supplier = trim($data['supplier'] ?? '') ?: OpeningStockService::DEFAULT_SUPPLIER;
        if (strlen($supplier) > 255) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'supplier' exceeds 255 characters.";
            return null;
        }

        $referenceNo = trim($data['reference_no'] ?? '') ?: ('OPENING-' . now()->format('Y-m'));
        if (strlen($referenceNo) > 100) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'reference_no' exceeds 100 characters.";
            return null;
        }

        $notes = trim($data['notes'] ?? '') ?: OpeningStockService::DEFAULT_NOTES;
        if (strlen($notes) > 1000) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): 'notes' exceeds 1000 characters.";
            return null;
        }

        $baseQuantity = (int) round($unit->convertToBaseUnit($quantity));
        if ($baseQuantity <= 0) {
            $errors[] = "Row {$rowNum} (sku: {$sku}): quantity converts to zero in base units.";
            return null;
        }

        $baseUnitCost = $unitCost / (float) $unit->conversion_factor;
        $totalCost = round($quantity * $unitCost, 2);

        return [
            'medicine_id' => $medicine->id,
            'type' => 'stock_in',
            'quantity' => $baseQuantity,
            'remaining_quantity' => $baseQuantity,
            'unit_cost' => round($baseUnitCost, 4),
            'total_cost' => $totalCost,
            'supplier' => $supplier,
            'batch_no' => $batchNo,
            'expiry_date' => $expiryDate,
            'reference_no' => $referenceNo,
            'notes' => $notes,
            'created_by' => $userId,
        ];
    }

    private function parseDate(string $value): string|false
    {
        if ($value === '') {
            return false;
        }

        try {
            if (is_numeric($value)) {
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value);

                return $date->format('Y-m-d');
            }

            return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return false;
        }
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
     * @return array{created: int, errors: string[], total: int}
     */
    private function abort(string $message): array
    {
        return ['created' => 0, 'errors' => [$message], 'total' => 0];
    }
}
