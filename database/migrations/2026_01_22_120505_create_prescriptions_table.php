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
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('prescription_number')->unique();
            $table->foreignId('medical_record_id')->constrained()->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained()->onDelete('cascade');
            $table->string('medication_name');
            $table->string('generic_name')->nullable();
            $table->string('strength');
            $table->string('dosage_form'); // tablet, capsule, liquid, etc.
            $table->string('route'); // oral, topical, injection, etc.
            $table->string('frequency'); // BID, TID, QID, etc.
            $table->string('dosage_instructions');
            $table->integer('quantity');
            $table->integer('refills')->default(0);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('indication')->nullable();
            $table->text('special_instructions')->nullable();
            $table->enum('status', ['active', 'completed', 'discontinued', 'on_hold'])->default('active');
            $table->boolean('is_controlled_substance')->default(false);
            $table->string('dea_schedule')->nullable(); // I, II, III, IV, V
            $table->timestamp('dispensed_at')->nullable();
            $table->string('pharmacy_name')->nullable();
            $table->timestamps();
            
            $table->index(['patient_id', 'status']);
            $table->index(['doctor_id', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
