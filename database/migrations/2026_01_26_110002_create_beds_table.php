<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beds', function (Blueprint $table) {
            $table->id();
            $table->string('bed_number');
            $table->foreignId('ward_id')->constrained()->onDelete('cascade');
            $table->enum('bed_type', ['general', 'private', 'icu', 'emergency']);
            $table->enum('status', ['available', 'occupied', 'maintenance'])->default('available');
            $table->decimal('daily_rate', 8, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['ward_id', 'bed_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beds');
    }
};