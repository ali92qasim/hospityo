<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_report_roster_doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique('doctor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_report_roster_doctors');
    }
};
