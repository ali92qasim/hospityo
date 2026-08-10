<?php

namespace App\Console\Commands;

use App\Models\EmergencyVisit;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Visit;
use Illuminate\Console\Command;

class BackfillVisitTypeDetails extends Command
{
    protected $signature = 'visits:backfill-type-details
                            {type? : opd, ipd, emergency, or omit for all}
                            {--dry-run : Show what would be created without writing}
                            {--verify : Verify row counts and field mappings}';

    protected $description = 'Backfill CTI child rows from existing visits spine data';

    public function handle(): int
    {
        if ($this->option('verify')) {
            return $this->verify();
        }

        $types = $this->argument('type')
            ? [strtolower((string) $this->argument('type'))]
            : ['opd', 'ipd', 'emergency'];

        foreach ($types as $type) {
            if (! in_array($type, ['opd', 'ipd', 'emergency'], true)) {
                $this->error("Invalid type: {$type}");

                return self::FAILURE;
            }

            $this->backfillType($type);
        }

        return self::SUCCESS;
    }

    private function backfillType(string $type): void
    {
        $query = Visit::query()->where('visit_type', $type);
        $count = 0;

        $query->chunkById(100, function ($visits) use ($type, &$count) {
            foreach ($visits as $visit) {
                if ($this->option('dry-run')) {
                    $this->line("Would backfill {$type} visit #{$visit->id}");
                    $count++;

                    continue;
                }

                if ($type === 'opd') {
                    OpdVisit::updateOrCreate(
                        ['visit_id' => $visit->id],
                        ['queue_priority' => $this->mapPriority($visit->priority)]
                    );
                } elseif ($type === 'ipd') {
                    IpdVisit::firstOrCreate(['visit_id' => $visit->id]);
                } else {
                    EmergencyVisit::firstOrCreate(['visit_id' => $visit->id]);
                }

                if ($visit->discharge_datetime && ! $visit->closed_at) {
                    $visit->updateQuietly(['closed_at' => $visit->discharge_datetime]);
                }

                $count++;
            }
        });

        $this->info("Backfilled {$count} {$type} visit(s)" . ($this->option('dry-run') ? ' (dry run)' : ''));
    }

    private function verify(): int
    {
        $mismatches = 0;

        foreach (['opd', 'ipd', 'emergency'] as $type) {
            $spineCount = Visit::where('visit_type', $type)->count();
            $childCount = match ($type) {
                'opd' => OpdVisit::count(),
                'ipd' => IpdVisit::count(),
                'emergency' => EmergencyVisit::count(),
            };

            if ($spineCount !== $childCount) {
                $this->error("{$type}: spine={$spineCount}, child={$childCount}");
                $mismatches++;
            } else {
                $this->info("{$type}: {$spineCount} rows matched");
            }

            if ($type === 'opd') {
                $priorityMismatches = Visit::where('visit_type', 'opd')
                    ->with('opdDetails')
                    ->get()
                    ->filter(function (Visit $visit) {
                        if (! $visit->opdDetails) {
                            return true;
                        }

                        return $visit->opdDetails->queue_priority !== $this->mapPriority($visit->priority);
                    })
                    ->count();

                if ($priorityMismatches > 0) {
                    $this->error("opd queue_priority mismatches: {$priorityMismatches}");
                    $mismatches += $priorityMismatches;
                }
            }
        }

        if ($mismatches > 0) {
            $this->error("Verification failed with {$mismatches} mismatch(es)");

            return self::FAILURE;
        }

        $this->info('Verification passed: 0 mismatches');

        return self::SUCCESS;
    }

    private function mapPriority(?string $priority): string
    {
        return in_array($priority, ['low', 'medium', 'high', 'critical'], true)
            ? $priority
            : 'medium';
    }
}
