<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_result_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('lab_test_parameter_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->string('unit')->nullable();
            $table->enum('flag', ['N', 'H', 'L', 'HH', 'LL', 'A'])->nullable(); // Normal, High, Low, Critical High/Low, Abnormal
            $table->text('comment')->nullable();
            $table->foreignId('entered_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('entered_at');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->unique(['lab_order_id', 'lab_test_parameter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_items');
    }
};