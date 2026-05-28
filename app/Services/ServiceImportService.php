<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Service;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Parses a CSV or Excel file and upserts Service records.
 *
 * Accepted columns: code, name, category, description, price, department_name, is_active
 * Upsert key: code (existing services with the same code are updated)
 *
 * Used by ImportServicesJob (background/deferred queue).
 */
class ServiceImportService
{
    private const ALLOWED_CATEGORIES = [
        'consultation', 'procedure', 'diagnostic', 'surgical', 'nursing', 'other',
    ];

    /**
     * Import services from an absolute file path (CSV or Excel).
     *
     * @return array{created: int, updated: int, errors: string[]}
     */
    public function importFromFile(string $absolutePath): array
    {
        if (!file_exists($absolutePath)) {
            return $this->abort("File not found: {$absolutePath}");
        }

        try {
            $rows = $this->readFile($absolutePath);
        } catch (\Throwable $e) {
            Log::error('[ServiceImport] Failed to read file', ['error' => $e->getMessage()]);
            return $this->abort('Could not read the uploaded file. It may be corrupt or in an unsupported format.');
        }

        if (empty($rows)) {
            return $this->abort('The uploaded file appears to be empty.');
        }

        return $this->process($rows);
    }

    // -------------------------------------------------------------------------
    // File reading — detects CSV vs Excel by MIME type, falls back to extension
    // -------------------------------------------------------------------------

    /**
     * Read the file and return rows as an array of arrays.
     * First row is the header.
     *
     * @return array<int, array<int, string>>
     */
    private function readFile(string $path): array
    {
        if ($this->isExcel($path)) {
            return $this->readExcel($path);
        }

        return $this->readCsv($path);
    }

    private function isExcel(string $path): bool
    {
        $excelMimes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/zip', // .xlsx files are ZIP archives
            'application/octet-stream',
        ];

        $mime = @mime_content_type($path) ?: '';
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($mime, $excelMimes, true) || in_array($ext, ['xlsx', 'xls'], true);
    }

    private function readCsv(string $path): array
    {
        $handle = @fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Could not open CSV file for reading.');
        }

        $rows = [];
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
        } finally {
            fclose($handle);
        }

        // Strip UTF-8 BOM from first cell of first row
        if (!empty($rows[0][0])) {
            $rows[0][0] = ltrim($rows[0][0], "\xEF\xBB\xBF");
        }

        return $rows;
    }

    private function readExcel(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rows        = [];

        foreach ($sheet->getRowIterator() as $row) {
            $cells = [];
            foreach ($row->getCellIterator() as $cell) {
                $cells[] = (string) ($cell->getValue() ?? '');
            }
            $rows[] = $cells;
        }

        return $rows;
    }

    // -------------------------------------------------------------------------
    // Processing
    // -------------------------------------------------------------------------

    /**
     * @param  array<int, array<int, string>>  $rows
     * @return array{created: int, updated: int, errors: string[]}
     */
    private function process(array $rows): array
    {
        // --- Header row ---
        $rawHeader = array_shift($rows);
        $header    = array_map(fn($h) => strtolower(trim($this->toUtf8((string) $h))), $rawHeader);

        if (!in_array('code', $header, true) || !in_array('name', $header, true)) {
            $found = implode(', ', array_filter($header));
            return $this->abort(
                "Invalid file format. The file must contain 'code' and 'name' columns. " .
                "Columns found: [{$found}]. " .
                "Please download the template and use it as a starting point."
            );
        }

        // Pre-load departments once for the whole import (avoid N+1)
        $departments = Department::pluck('id', 'name')->mapWithKeys(
            fn($id, $name) => [strtolower(trim($name)) => $id]
        )->toArray();

        $created = 0;
        $updated = 0;
        $errors  = [];
        $rowNum  = 1; // 1-based, header was row 1

        foreach ($rows as $rawRow) {
            $rowNum++;

            // Skip completely blank rows
            if (empty(array_filter(array_map('trim', $rawRow)))) {
                continue;
            }

            // Pad short rows to match header length
            $rawRow = array_pad($rawRow, count($header), '');
            $row    = array_map(fn($v) => $this->toUtf8((string) $v), $rawRow);
            $data   = array_combine($header, $row);

            $code = trim($data['code'] ?? '');
            $name = trim($data['name'] ?? '');

            if ($code === '') {
                $errors[] = "Row {$rowNum}: 'code' is required — row skipped.";
                continue;
            }

            if ($name === '') {
                $errors[] = "Row {$rowNum} (code: {$code}): 'name' is required — row skipped.";
                continue;
            }

            // Resolve category
            $rawCategory = strtolower(trim($data['category'] ?? ''));
            $category    = in_array($rawCategory, self::ALLOWED_CATEGORIES, true)
                ? $rawCategory
                : 'other';

            if ($rawCategory !== '' && $category === 'other' && $rawCategory !== 'other') {
                $errors[] = "Row {$rowNum} (code: {$code}): Unknown category '{$rawCategory}' — defaulted to 'other'.";
            }

            // Resolve price
            $rawPrice = trim($data['price'] ?? '');
            $price    = 0.0;
            if ($rawPrice !== '') {
                if (is_numeric($rawPrice)) {
                    $price = max(0.0, (float) $rawPrice);
                } else {
                    $errors[] = "Row {$rowNum} (code: {$code}): Invalid price '{$rawPrice}' — defaulted to 0.";
                }
            }

            // Resolve department
            $deptName = strtolower(trim($data['department_name'] ?? ''));
            $deptId   = null;
            if ($deptName !== '') {
                $deptId = $departments[$deptName] ?? null;
                if ($deptId === null) {
                    $errors[] = "Row {$rowNum} (code: {$code}): Department '{$data['department_name']}' not found — imported without department.";
                }
            }

            // Resolve is_active
            $rawActive = trim($data['is_active'] ?? '1');
            $isActive  = !in_array($rawActive, ['0', 'false', 'no', 'inactive'], true);

            try {
                $service = Service::updateOrCreate(
                    ['code' => $code],
                    [
                        'name'          => $name,
                        'category'      => $category,
                        'description'   => trim($data['description'] ?? '') ?: null,
                        'price'         => $price,
                        'department_id' => $deptId,
                        'is_active'     => $isActive,
                    ]
                );

                if ($service->wasRecentlyCreated) {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum} (code: {$code}): " . $e->getMessage();
                Log::warning('[ServiceImport] Row error', [
                    'row'   => $rowNum,
                    'code'  => $code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('created', 'updated', 'errors');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
