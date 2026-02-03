<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('visits');
        
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->string('visit_no')->unique();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->enum('visit_type', ['opd', 'ipd', 'emergency']);
            $table->enum('status', ['registered', 'vitals_recorded', 'with_doctor', 'tests_ordered', 'tests_completed', 'completed'])->default('registered');
            $table->datetime('visit_datetime');
            $table->timestamps();
        });

        Schema::create('vital_signs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('blood_pressure')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('pulse_rate')->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->decimal('height', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->text('presenting_complaints')->nullable();
            $table->text('history')->nullable();
            $table->text('examination')->nullable();
            $table->text('provisional_diagnosis')->nullable();
            $table->text('treatment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('test_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('test_name');
            $table->text('instructions')->nullable();
            $table->enum('status', ['ordered', 'completed'])->default('ordered');
            $table->text('results')->nullable();
            $table->datetime('ordered_at');
            $table->datetime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_orders');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('vital_signs');
        Schema::dropIfExists('visits');
    }
};