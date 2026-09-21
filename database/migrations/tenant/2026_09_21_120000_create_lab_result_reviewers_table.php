<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_result_reviewers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained('lab_results')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['lab_result_id', 'doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_reviewers');
    }
};
