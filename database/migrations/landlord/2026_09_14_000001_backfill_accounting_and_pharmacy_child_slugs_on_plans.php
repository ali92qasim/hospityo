<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        \App\Models\Plan::query()->each(function (\App\Models\Plan $plan) {
            $modules = $plan->modules ?? [];
            $modules = \App\Models\ModuleRegistry::backfillAccountingChildren($modules);
            $modules = \App\Models\ModuleRegistry::backfillPharmacyChildren($modules);
            $plan->modules = $modules;
            $plan->save();
        });
    }

    public function down(): void
    {
        // no-op: do not strip purchased child slugs
    }
};
