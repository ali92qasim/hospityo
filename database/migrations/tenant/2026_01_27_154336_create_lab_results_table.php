<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->onDelete('cascade');
            $table->json('results'); // parameter values
            $table->json('flags')->nullable(); // abnormal flags
            $table->text('interpretation')->nullable();
            $table->text('comments')->nullable();
            $table->enum('status', ['preliminary', 'final', 'corrected', 'cancelled']);
            $table->foreignId('technician_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pathologist_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('tested_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_results');
    }
};