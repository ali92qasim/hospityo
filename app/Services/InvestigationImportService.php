<?php

namespace App\Services;

use App\Models\Investigation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared CSV import logic for investigations.
 *
 * Used by:
 *  - ImportInvestigationsJob  (background queue job for manual uploads)
 *  - InvestigationSeeder      (tenant provisioning / install wizard)
 */
class InvestigationImportService
{
    /**
     * Import investigations from a CSV file path (absolute or storage-relative).
     *
     * @param  string  $absolutePath  Full filesystem path to the CSV file.
     * @return array{created: int, updated: int, errors: string[]}
     */
    public function importFromFile(string $absolutePath): array
    {
        if (!file_exists($absolutePath)) {
            return ['created' => 0, 'updated' => 0, 'errors' => ["File not found: {$absolutePath}"]];
        }

        $handle = fopen($absolutePath, 'r');
        if (!$handle) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Could not open import file.']];
        }

        try {
            return $this->process($handle);
        } finally {
            fclose($handle);
        }
    }

    // -------------------------------------------------------------------------

    private function process($handle): array
    {
        // --- Header ---
        $rawHeader = fgetcsv($handle);
        if (!$rawHeader) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['The file appears to be empty.']];
        }

        // Strip UTF-8 BOM from first column
        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $header = array_map(fn($h) => $this->toUtf8(trim($h)), $rawHeader);

        if (!in_array('code', $header) || !in_array('name', $header)) {
            return ['created' => 0, 'updated' => 0, 'errors' => [
                'Invalid file format. The file must contain "code" and "name" columns.',
            ]];
        }

        // Detect how many param_N columns exist dynamically from the header
        $maxParam = 0;
        foreach ($header as $col) {
            if (preg_match('/^param_(\d+)_name$/', $col, $m)) {
                $maxParam = max($maxParam, (int) $m[1]);
            }
        }

        $created = 0;
        $updated = 0;
        $errors  = [];
        $rowNum  = 1;
        $chunk   = [];

        $processChunk = function (array $rows) use (
            $header, $maxParam, &$created, &$updated, &$errors
        ) {
            foreach ($rows as [$rowNum, $rawRow]) {
                if (!array_filter($rawRow)) {
                    continue;
                }

                $rawRow = array_pad($rawRow, count($header), '');
                $row    = array_map(fn($v) => $this->toUtf8($v), $rawRow);
                $data   = array_combine($header, $row);

                $code = trim($data['code'] ?? '');
                $name = trim($data['name'] ?? '');

                if ($code === '' || $name === '') {
                    $errors[] = "Row {$rowNum}: 'code' and 'name' are required.";
                    continue;
                }

                try {
                    $investigation = Investigation::updateOrCreate(
                        ['code' => $code],
                        [
                            'name'            => $name,
                            'category'        => $this->sanitize($data['category'] ?? '', 'hematology'),
                            'sample_type'     => $this->sanitize($data['sample_type'] ?? '') ?: null,
                            'price'           => (float) ($data['price'] ?? 0),
                            'turnaround_time' => $this->sanitize($data['turnaround_time'] ?? '') ?: null,
                            'description'     => $this->sanitize($data['description'] ?? '') ?: null,
                            'instructions'    => $this->sanitize($data['instructions'] ?? '') ?: null,
                            'is_active'       => true,
                        ]
                    );

                    if ($investigation->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    // Build parameter rows dynamically
                    $paramRows = [];
                    $now       = now()->toDateTimeString();

                    for ($i = 1; $i <= $maxParam; $i++) {
                        $paramName = $this->sanitize($data["param_{$i}_name"] ?? '');
                        if ($paramName === '') {
                            continue;
                        }

                        $unit     = $this->sanitize($data["param_{$i}_unit"] ?? '') ?: null;
                        $rangeRaw = $this->sanitize($data["param_{$i}_reference_range"] ?? '');

                        $paramRows[] = [
                            'lab_test_id'      => $investigation->id,
                            'parameter_name'   => $paramName,
                            'unit'             => $unit,
                            'data_type'        => 'numeric',
                            'reference_ranges' => json_encode(['range' => $rangeRaw]),
                            'display_order'    => $i,
                            'is_active'        => 1,
                            'created_at'       => $now,
                            'updated_at'       => $now,
                        ];
                    }

                    if (!empty($paramRows)) {
                        DB::table('lab_test_parameters')
                            ->where('lab_test_id', $investigation->id)
                            ->delete();

                        foreach (array_chunk($paramRows, 100) as $slice) {
                            DB::table('lab_test_parameters')->insert($slice);
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Row {$rowNum} ({$code}): " . $e->getMessage();
                    Log::warning('[InvestigationImport] Row error', [
                        'row'   => $rowNum,
                        'code'  => $code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        };

        while (($rawRow = fgetcsv($handle)) !== false) {
            $rowNum++;
            $chunk[] = [$rowNum, $rawRow];

            if (count($chunk) >= 50) {
                $processChunk($chunk);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $processChunk($chunk);
        }

        return compact('created', 'updated', 'errors');
    }

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

    private function sanitize(string $value, string $default = ''): string
    {
        $clean = trim($this->toUtf8($value));
        return $clean !== '' ? $clean : $default;
    }
}
