<?php

namespace App\Services;

use App\Models\ImagingStudy;
use App\Models\LabTest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvestigationImportService
{
    /**
     * @param  string  $absolutePath
     * @param  string|null  $catalog  lab|imaging. When set, every row is imported into that table.
     * @return array{created: int, updated: int, errors: string[]}
     */
    public function importFromFile(string $absolutePath, ?string $catalog = null): array
    {
        if (! file_exists($absolutePath)) {
            return ['created' => 0, 'updated' => 0, 'errors' => ["File not found: {$absolutePath}"]];
        }

        $handle = fopen($absolutePath, 'r');
        if (! $handle) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['Could not open import file.']];
        }

        try {
            return $this->process($handle, $catalog);
        } finally {
            fclose($handle);
        }
    }

    private function process($handle, ?string $catalog): array
    {
        $rawHeader = fgetcsv($handle);
        if (! $rawHeader) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['The file appears to be empty.']];
        }

        $rawHeader[0] = ltrim($rawHeader[0], "\xEF\xBB\xBF");
        $header = array_map(fn ($h) => $this->toUtf8(trim($h)), $rawHeader);

        if (! in_array('code', $header) || ! in_array('name', $header)) {
            return ['created' => 0, 'updated' => 0, 'errors' => [
                'Invalid file format. The file must contain "code" and "name" columns.',
            ]];
        }

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
            $header, $maxParam, $catalog, &$created, &$updated, &$errors
        ) {
            foreach ($rows as [$rowNum, $rawRow]) {
                if (! array_filter($rawRow)) {
                    continue;
                }

                $rawRow = array_pad($rawRow, count($header), '');
                $row    = array_map(fn ($v) => $this->toUtf8($v), $rawRow);
                $data   = array_combine($header, $row);

                $code = trim($data['code'] ?? '');
                $name = trim($data['name'] ?? '');

                if ($code === '' || $name === '') {
                    $errors[] = "Row {$rowNum}: 'code' and 'name' are required.";
                    continue;
                }

                try {
                    $category = $this->sanitize($data['category'] ?? '');
                    $target = $this->resolveCatalog($data, $category, $catalog, $rowNum, $errors);
                    if ($target === null) {
                        continue;
                    }

                    $allowed = $target === 'lab' ? LabTest::categories() : ImagingStudy::categories();
                    $category = $category !== '' ? strtolower($category) : ($target === 'lab' ? 'hematology' : 'radiology');

                    if (! in_array($category, $allowed, true)) {
                        $errors[] = "Row {$rowNum} ({$code}): category '{$category}' is not valid for {$target}.";
                        continue;
                    }

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
                            'lab_test_id'      => null,
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

                    if ($target === 'imaging' && $paramRows !== []) {
                        $errors[] = "Row {$rowNum} ({$code}): Imaging studies cannot include lab parameters.";
                        continue;
                    }

                    if ($target === 'lab' && ImagingStudy::query()->where('code', $code)->exists()) {
                        $errors[] = "Row {$rowNum} ({$code}): existing imaging study cannot be imported as a lab test.";
                        continue;
                    }

                    if ($target === 'imaging' && LabTest::query()->where('code', $code)->exists()) {
                        $errors[] = "Row {$rowNum} ({$code}): existing lab test cannot be imported as an imaging study.";
                        continue;
                    }

                    $payload = [
                        'name'            => $name,
                        'category'        => $category,
                        'price'           => (float) ($data['price'] ?? 0),
                        'turnaround_time' => $this->sanitize($data['turnaround_time'] ?? '') ?: null,
                        'description'     => $this->sanitize($data['description'] ?? '') ?: null,
                        'instructions'    => $this->sanitize($data['instructions'] ?? '') ?: null,
                        'is_active'       => true,
                    ];

                    if ($target === 'lab') {
                        $payload['sample_type'] = $this->sanitize($data['sample_type'] ?? '') ?: null;
                        $record = LabTest::updateOrCreate(['code' => $code], $payload);
                    } else {
                        $record = ImagingStudy::updateOrCreate(['code' => $code], $payload);
                    }

                    if ($record->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    if ($target === 'lab' && ! empty($paramRows)) {
                        foreach ($paramRows as &$paramRow) {
                            $paramRow['lab_test_id'] = $record->id;
                        }
                        unset($paramRow);

                        DB::table('lab_test_parameters')
                            ->where('lab_test_id', $record->id)
                            ->delete();

                        foreach (array_chunk($paramRows, 100) as $slice) {
                            DB::table('lab_test_parameters')->insert($slice);
                        }
                    }
                } catch (\Throwable $e) {
                    $errors[] = "Row {$rowNum} ({$code}): ".$e->getMessage();
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

        if (! empty($chunk)) {
            $processChunk($chunk);
        }

        return compact('created', 'updated', 'errors');
    }

    /**
     * @param  array<string, string>  $data
     * @param  list<string>  $errors
     */
    private function resolveCatalog(
        array $data,
        string $category,
        ?string $forced,
        int $rowNum,
        array &$errors
    ): ?string {
        if ($forced !== null) {
            return $forced === 'imaging' ? 'imaging' : 'lab';
        }

        $normalized = strtolower($category);
        if (in_array($normalized, LabTest::categories(), true)) {
            return 'lab';
        }
        if (in_array($normalized, ImagingStudy::categories(), true)) {
            return 'imaging';
        }

        $errors[] = "Row {$rowNum}: cannot determine catalog from category '{$category}'.";

        return null;
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

    private function sanitize(string $value, string $default = ''): string
    {
        $clean = trim($this->toUtf8($value));

        return $clean !== '' ? $clean : $default;
    }
}
