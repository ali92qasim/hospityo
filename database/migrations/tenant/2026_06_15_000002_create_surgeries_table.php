<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surgeries', function (Blueprint $table) {
            $table->id();
            $table->string('surgery_number')->unique();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('operation_theatre_id')->nullable()->constrained('operation_theatres')->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained('visits')->nullOnDelete();
            $table->string('surgery_type')->default('elective'); // elective, emergency
            $table->string('procedure_name');
            $table->string('procedure_code')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_start_time')->nullable();
            $table->time('scheduled_end_time')->nullable();
            $table->timestamp('actual_start_time')->nullable();
            $table->timestamp('actual_end_time')->nullable();
            $table->text('pre_op_diagnosis')->nullable();
            $table->text('post_op_diagnosis')->nullable();
            $table->text('procedure_notes')->nullable();
            $table->text('complications')->nullable();
            $table->string('anesthesia_type')->nullable(); // general, local, spinal, epidural, sedation
            $table->string('status')->default('scheduled'); // scheduled, in_progress, completed, cancelled, postponed
            $table->text('cancelled_reason')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('surgery_team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role'); // lead_surgeon, assistant_surgeon, anesthetist, nurse, technician
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surgery_team_members');
        Schema::dropIfExists('surgeries');
    }
};
