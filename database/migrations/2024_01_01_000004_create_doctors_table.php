<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('doctor_no')->unique();
            $table->string('specialization');
            $table->string('qualification');
            $table->string('phone');
            $table->string('email')->unique();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->integer('experience_years');
            $table->text('address')->nullable();
            $table->decimal('consultation_fee', 8, 2);
            $table->json('available_days')->nullable();
            $table->time('shift_start');
            $table->time('shift_end');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};