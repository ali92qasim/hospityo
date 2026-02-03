<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['hematology', 'biochemistry', 'microbiology', 'immunology', 'pathology', 'molecular']);
            $table->enum('sample_type', ['blood', 'urine', 'stool', 'sputum', 'csf', 'tissue', 'swab', 'other']);
            $table->decimal('price', 10, 2);
            $table->integer('turnaround_time'); // in hours
            $table->json('parameters')->nullable(); // test parameters with reference ranges
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};