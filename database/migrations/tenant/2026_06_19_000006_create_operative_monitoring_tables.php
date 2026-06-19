<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Anaesthesia record for the surgery
        Schema::create('anaesthesia_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->foreignId('anaesthetist_id')->constrained('users');
            $table->string('anaesthesia_type'); // general, regional, local, sedation, combined
            $table->string('airway_management')->nullable(); // ETT, LMA, facemask, tracheostomy
            $table->string('ett_size')->nullable();
            $table->string('induction_agent')->nullable();
            $table->string('induction_dose')->nullable();
            $table->string('maintenance_agent')->nullable();
            $table->string('muscle_relaxant')->nullable();
            $table->string('reversal_agent')->nullable();
            $table->text('regional_technique')->nullable(); // spinal level, epidural, nerve block details
            $table->text('iv_fluids')->nullable();
            $table->integer('estimated_blood_loss_ml')->nullable();
            $table->integer('urine_output_ml')->nullable();
            $table->text('intra_op_medications')->nullable();
            $table->text('intra_op_events')->nullable(); // complications, interventions
            $table->timestamp('induction_time')->nullable();
            $table->timestamp('intubation_time')->nullable();
            $table->timestamp('extubation_time')->nullable();
            $table->string('recovery_status')->nullable(); // awake, drowsy, intubated
            $table->text('post_op_instructions')->nullable();
            $table->text('pain_management_plan')->nullable();
            $table->timestamps();
        });

        // Intra-operative vital signs — time-series entries
        Schema::create('operative_vitals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->string('blood_pressure_systolic')->nullable();
            $table->string('blood_pressure_diastolic')->nullable();
            $table->string('heart_rate')->nullable();
            $table->string('spo2')->nullable();
            $table->string('etco2')->nullable();
            $table->string('respiratory_rate')->nullable();
            $table->string('temperature')->nullable();
            $table->string('mac_value')->nullable(); // minimum alveolar concentration
            $table->string('fio2')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });

        // Post-operative monitoring
        Schema::create('post_op_monitoring', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->string('phase'); // pacu (recovery), ward
            $table->string('consciousness_level')->nullable(); // alert, verbal, pain, unresponsive (AVPU)
            $table->string('blood_pressure')->nullable();
            $table->string('heart_rate')->nullable();
            $table->string('spo2')->nullable();
            $table->string('respiratory_rate')->nullable();
            $table->string('temperature')->nullable();
            $table->string('pain_score')->nullable(); // 0-10 NRS
            $table->string('nausea_vomiting')->nullable(); // none, mild, moderate, severe
            $table->text('wound_status')->nullable();
            $table->text('drain_output')->nullable();
            $table->text('iv_fluids_given')->nullable();
            $table->text('medications_given')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_op_monitoring');
        Schema::dropIfExists('operative_vitals');
        Schema::dropIfExists('anaesthesia_records');
    }
};
