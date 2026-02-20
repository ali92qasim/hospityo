<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_allergy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->onDelete('cascade');
            $table->foreignId('allergy_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate entries
            $table->unique(['consultation_id', 'allergy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_allergy');
    }
};
