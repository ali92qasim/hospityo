<?php

namespace Database\Seeders;

use App\Services\InvestigationImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

/**
 * Seeds investigations (and their parameters) from the bundled CSV template.
 *
 * Replaces the old hardcoded array seeder and the separate LabTestParameterSeeder.
 * The CSV at public/templates/investigations.csv is the single source of truth.
 */
class InvestigationSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = public_path('templates/investigations.csv');

        if (!file_exists($csvPath)) {
            $this->command?->warn(
                "[InvestigationSeeder] Template CSV not found at: {$csvPath}. Skipping."
            );
            Log::warning('[InvestigationSeeder] Template CSV not found.', ['path' => $csvPath]);
            return;
        }

        $service = new InvestigationImportService();
        $result  = $service->importFromFile($csvPath);

        foreach ($result['errors'] as $error) {
            $this->command?->warn("[InvestigationSeeder] {$error}");
            Log::warning('[InvestigationSeeder] Import error', ['error' => $error]);
        }

        $this->command?->info(
            "[InvestigationSeeder] Done — created: {$result['created']}, updated: {$result['updated']}, errors: " . count($result['errors'])
        );

        Log::info('[InvestigationSeeder] Completed', $result);
    }
}
