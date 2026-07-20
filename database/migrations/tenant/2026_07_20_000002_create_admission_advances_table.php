<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');

            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->string('payment_method', 30);
            $table->string('reference_number', 255)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');

            $table->timestamps();

            $table->index(['admission_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_advances');
    }
};

