<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doctor_share_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('service_category', 30);
            $table->decimal('percentage', 8, 2);
            $table->timestamps();

            $table->unique(
                ['doctor_id', 'service_category'],
                'dsr_rates_doctor_category_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_share_rates');
    }
};
