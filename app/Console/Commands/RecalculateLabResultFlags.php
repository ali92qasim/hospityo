<?php

namespace App\Console\Commands;

use App\Models\LabResultItem;
use App\Models\LabTestParameter;
use App\Models\Patient;
use Illuminate\Console\Command;

class RecalculateLabResultFlags extends Command
{
    protected $signature   = 'lab:recalculate-flags {--dry-run : Show what would change without saving}';
    protected $description = 'Recalculate abnormal flags for all existing lab result items';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $items = LabResultItem::with([
            'parameter',
            'labResult.investigationOrder.patient',
        ])->get();

        $this->info("Processing {$items->count()} result items...");

        $changed = 0;

        foreach ($items as $item) {
            if (!$item->parameter || !is_numeric($item->value)) {
                continue;
            }

            $patient = $item->labResult?->investigationOrder?->patient;
            $newFlag = $item->parameter->calculateFlag(
                $item->value,
                $patient?->age,
                $patient?->gender,
            );

            if ($newFlag !== $item->flag) {
                $changed++;
                $this->line(sprintf(
                    '  [%s] %s: value=%s, old flag=%s → new flag=%s',
                    $item->id,
                    $item->parameter->parameter_name,
                    $item->value,
                    $item->flag ?? 'null',
                    $newFlag,
                ));

                if (!$dryRun) {
                    $item->update(['flag' => $newFlag]);
                }
            }
        }

        $action = $dryRun ? 'Would update' : 'Updated';
        $this->info("{$action} {$changed} result item(s).");

        return self::SUCCESS;
    }
}
