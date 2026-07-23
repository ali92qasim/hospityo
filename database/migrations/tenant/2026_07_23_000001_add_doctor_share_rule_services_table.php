<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_share_rule_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_share_rule_id')
                ->constrained('doctor_share_rules')
                ->cascadeOnDelete();
            $table->foreignId('service_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->unique(['doctor_share_rule_id', 'service_id'], 'dsrs_rule_service_unique');
            $table->index('service_id', 'dsrs_service_idx');
        });

        DB::table('doctor_share_rules')
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->get(['id', 'service_id'])
            ->each(function ($rule) {
                DB::table('doctor_share_rule_service')->insertOrIgnore([
                    'doctor_share_rule_id' => $rule->id,
                    'service_id' => $rule->service_id,
                ]);
            });

        Schema::table('doctor_share_rules', function (Blueprint $table) {
            $table->dropUnique('dsr_unique_rule');
        });

        DB::table('doctor_share_rules')
            ->whereNotNull('service_id')
            ->update(['service_id' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_share_rule_service');

        Schema::table('doctor_share_rules', function (Blueprint $table) {
            $table->unique(
                ['doctor_id', 'service_id', 'investigation_id', 'applies_to'],
                'dsr_unique_rule'
            );
        });
    }
};
