<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \App\Models\Plan::query()->each(function (\App\Models\Plan $plan) {
            $plan->modules = \App\Models\ModuleRegistry::backfillReportChildren($plan->modules ?? []);
            $plan->save();
        });
    }

    public function down(): void
    {
        // no-op: do not strip purchased report slugs
    }
};
