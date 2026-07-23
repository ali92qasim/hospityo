<?php

namespace App\Services;

use App\Models\MedicineBrand;
use Illuminate\Support\Facades\Log;

/**
 * Parses CSV or Excel files and upserts MedicineBrand records.
 *
 * Accepted columns: name, description, is_active
 * Upsert key: name (case-insensitive)
 */
class MedicineBrandImportService
{
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
            Log::error('[MedicineBrandImport] Failed to read file', [
                'path' => $absolutePath,
                'error' => $e->getMessage(),
            ]);

            return $this->abort($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[MedicineBrandImport] Unexpected read failure', [
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

        $created = 0;
        $updated = 0;
        $errors = [];
        $rowNum = 1;

        foreach ($rows as $rawRow) {
            $rowNum++;

            if ($this->isBlankRow($rawRow)) {
                continue;
            }

            $rawRow = array_pad($rawRow, count($header), '');
            $row = array_map(fn ($value) => $this->toUtf8((string) $value), $rawRow);
            $data = array_combine($header, $row);

            $name = trim($data['name'] ?? '');

            if ($name === '') {
                $errors[] = "Row {$rowNum}: 'name' is required — row skipped.";
                continue;
            }

            if (strlen($name) > 255) {
                $errors[] = "Row {$rowNum} (name: {$name}): name exceeds 255 characters — row skipped.";
                continue;
            }

            $description = trim($data['description'] ?? '') ?: null;
            $isActive = $this->parseIsActive($data['is_active'] ?? '1');

            try {
                $brand = MedicineBrand::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

                if ($brand) {
                    $brand->update([
                        'name' => $name,
                        'description' => $description,
                        'is_active' => $isActive,
                    ]);
                    $updated++;
                } else {
                    MedicineBrand::create([
                        'name' => $name,
                        'description' => $description,
                        'is_active' => $isActive,
                    ]);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Row {$rowNum} (name: {$name}): {$e->getMessage()}";
                Log::warning('[MedicineBrandImport] Row error', [
                    'row' => $rowNum,
                    'name' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('created', 'updated', 'errors');
    }

    private function parseIsActive(string $value): bool
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
     * @return array{created: int, updated: int, errors: string[]}
     */
    private function abort(string $message): array
    {
        return ['created' => 0, 'updated' => 0, 'errors' => [$message]];
    }
}
