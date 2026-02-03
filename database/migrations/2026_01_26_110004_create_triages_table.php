<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->enum('priority_level', ['critical', 'urgent', 'less_urgent', 'non_urgent']);
            $table->string('chief_complaint');
            $table->integer('pain_scale')->nullable();
            $table->text('triage_notes')->nullable();
            $table->foreignId('triaged_by')->constrained('users');
            $table->timestamp('triaged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triages');
    }
};