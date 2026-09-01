<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;

class SyncPlanModules extends Command
{
    /**
     * Core starter-tier modules (excluding departments, which this command may add).
     */
    protected const STARTER_LEVEL_MODULES = [
        'patients',
        'doctors',
        'appointments',
        'visits',
        'billing',
    ];

    protected $signature   = 'plans:sync-modules';
    protected $description = 'Merge missing module slugs into existing plans after registry changes';

    public function handle(): int
    {
        $updated = 0;

        foreach (Plan::all() as $plan) {
            $modules   = $plan->modules ?? [];
            $merged    = $this->mergeMissingModules($modules);
            $added     = array_values(array_diff($merged, $modules));

            if ($added === []) {
                continue;
            }

            $plan->update(['modules' => $merged]);
            $updated++;

            $this->info("Updated {$plan->slug}: added " . implode(', ', $added));
        }

        $this->info($updated === 0
            ? 'All plans already up to date.'
            : "Synced {$updated} plan(s).");

        return self::SUCCESS;
    }

    /**
     * Merge required module slugs into the plan list without removing custom selections.
     *
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    protected function mergeMissingModules(array $modules): array
    {
        $toAdd = [];

        if (in_array('laboratory', $modules, true) && ! in_array('imaging', $modules, true)) {
            $toAdd[] = 'imaging';
        }

        if (in_array('visits', $modules, true) && ! in_array('emergency', $modules, true)) {
            $toAdd[] = 'emergency';
        }

        if (! in_array('settings', $modules, true)
            && ! in_array('settings.hospital-info', $modules, true)
            && ! in_array('settings.prescription-print', $modules, true)
        ) {
            $toAdd[] = 'settings';
            $toAdd[] = 'settings.hospital-info';
            $toAdd[] = 'settings.prescription-print';
        }

        if ($this->needsDepartments($modules)) {
            $toAdd[] = 'departments';
        }

        if ($toAdd === []) {
            return $modules;
        }

        return array_values(array_unique(array_merge($modules, $toAdd)));
    }

    /**
     * Plans with the full starter module set should include departments.
     *
     * @param  array<int, string>  $modules
     */
    protected function needsDepartments(array $modules): bool
    {
        if (in_array('departments', $modules, true)) {
            return false;
        }

        foreach (self::STARTER_LEVEL_MODULES as $starterModule) {
            if (! in_array($starterModule, $modules, true)) {
                return false;
            }
        }

        return true;
    }
}
