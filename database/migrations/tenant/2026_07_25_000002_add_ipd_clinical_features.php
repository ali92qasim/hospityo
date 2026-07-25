<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('duty_doctor_id')
                ->nullable()
                ->after('doctor_id')
                ->constrained('doctors')
                ->nullOnDelete();
        });

        Schema::create('patient_complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->text('complaint');
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });

        Schema::create('ipd_gpe_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recorded_by')->constrained('users');
            $table->string('gpe_chest')->nullable();
            $table->string('gpe_abdomen')->nullable();
            $table->string('gpe_cvs')->nullable();
            $table->string('gpe_cns')->nullable();
            $table->string('gpe_pupils')->nullable();
            $table->string('gpe_conjunctiva')->nullable();
            $table->string('gpe_nails')->nullable();
            $table->string('gpe_throat')->nullable();
            $table->string('gpe_sclera')->nullable();
            $table->string('gpe_gcs')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('visit_id');
        });

        Schema::create('ipd_consultant_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultant_doctor_id')->constrained('doctors')->restrictOnDelete();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('visit_notes')->nullable();
            $table->text('orders')->nullable();
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('consultant_seen_at')->nullable();
            $table->timestamps();

            $table->index(['visit_id', 'consultant_doctor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipd_consultant_visits');
        Schema::dropIfExists('ipd_gpe_records');
        Schema::dropIfExists('patient_complaints');

        Schema::table('visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('duty_doctor_id');
        });
    }
};
