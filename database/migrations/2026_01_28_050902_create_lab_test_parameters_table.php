<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_test_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_test_id')->constrained()->onDelete('cascade');
            $table->string('parameter_name');
            $table->string('unit')->nullable();
            $table->enum('data_type', ['numeric', 'text', 'select']);
            $table->json('reference_ranges'); // {male: "13.5-17.5", female: "12.0-15.5", pediatric: "11.0-16.0"}
            $table->json('critical_values')->nullable(); // {low: "<7.0", high: ">20.0"}
            $table->json('select_options')->nullable(); // for dropdown parameters
            $table->boolean('is_calculated')->default(false);
            $table->text('calculation_formula')->nullable();
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_test_parameters');
    }
};