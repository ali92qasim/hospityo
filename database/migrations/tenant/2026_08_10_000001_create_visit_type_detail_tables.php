<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opd_visits', function (Blueprint $table) {
            $table->foreignId('visit_id')->primary()->constrained('visits')->cascadeOnDelete();
            $table->enum('queue_priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->timestamps();
        });

        Schema::create('ipd_visits', function (Blueprint $table) {
            $table->foreignId('visit_id')->primary()->constrained('visits')->cascadeOnDelete();
            $table->date('expected_discharge_date')->nullable();
            $table->timestamps();
        });

        Schema::create('emergency_visits', function (Blueprint $table) {
            $table->foreignId('visit_id')->primary()->constrained('visits')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('visit_class_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained('visits')->cascadeOnDelete();
            $table->enum('from_type', ['opd', 'ipd', 'emergency']);
            $table->enum('to_type', ['opd', 'ipd', 'emergency']);
            $table->datetime('changed_at');
            $table->foreignId('changed_by')->constrained('users');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->index(['visit_id', 'changed_at']);
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->datetime('closed_at')->nullable()->after('visit_datetime');
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });

        Schema::dropIfExists('visit_class_histories');
        Schema::dropIfExists('emergency_visits');
        Schema::dropIfExists('ipd_visits');
        Schema::dropIfExists('opd_visits');
    }
};
