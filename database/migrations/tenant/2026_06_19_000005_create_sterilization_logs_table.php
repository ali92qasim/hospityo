<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sterilization_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_number')->unique();
            $table->string('target_type'); // theatre, instrument_set, individual_instrument
            $table->foreignId('operation_theatre_id')->nullable()->constrained('operation_theatres')->nullOnDelete();
            $table->foreignId('ot_consumable_id')->nullable()->constrained('ot_consumables')->nullOnDelete();
            $table->string('instrument_set_name')->nullable(); // for instrument sets not in catalog
            $table->string('method'); // autoclave, chemical, dry_heat, ethylene_oxide, plasma
            $table->string('cycle_number')->nullable(); // autoclave cycle #
            $table->integer('temperature')->nullable(); // °C
            $table->integer('duration_minutes')->nullable();
            $table->string('chemical_indicator_result')->nullable(); // pass, fail
            $table->string('biological_indicator_result')->nullable(); // pass, fail, pending
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, failed
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sterilization_logs');
    }
};
