<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pre_anaesthesia_checkups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('anaesthetist_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('requested_by')->constrained('users');

            // Patient condition
            $table->string('asa_grade')->nullable(); // ASA I-VI
            $table->text('medical_history')->nullable();
            $table->text('current_medications')->nullable();
            $table->text('allergies')->nullable();
            $table->text('airway_assessment')->nullable();
            $table->string('mallampati_class')->nullable(); // I, II, III, IV
            $table->text('cardiovascular_status')->nullable();
            $table->text('respiratory_status')->nullable();
            $table->text('renal_hepatic_status')->nullable();

            // Vitals at PAC
            $table->string('blood_pressure')->nullable();
            $table->string('heart_rate')->nullable();
            $table->string('spo2')->nullable();
            $table->string('weight_kg')->nullable();

            // Lab results & investigations reviewed
            $table->text('investigations_reviewed')->nullable();

            // Anaesthesia plan
            $table->string('proposed_anaesthesia_type')->nullable(); // general, regional, local, sedation
            $table->text('special_precautions')->nullable();
            $table->text('fasting_instructions')->nullable();
            $table->text('premedication')->nullable();

            // Clearance
            $table->string('status')->default('pending'); // pending, cleared, not_cleared, requires_further_evaluation
            $table->text('clearance_notes')->nullable();
            $table->timestamp('cleared_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pre_anaesthesia_checkups');
    }
};
