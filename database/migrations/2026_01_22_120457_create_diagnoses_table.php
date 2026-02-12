<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('icd_10_code')->nullable();
            $table->string('diagnosis_name');
            $table->text('description')->nullable();
            $table->enum('type', ['primary', 'secondary', 'differential'])->default('primary');
            $table->enum('status', ['active', 'resolved', 'chronic', 'rule_out'])->default('active');
            $table->date('onset_date')->nullable();
            $table->date('resolved_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['visit_id', 'type']);
            $table->index('icd_10_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnoses');
    }
};
