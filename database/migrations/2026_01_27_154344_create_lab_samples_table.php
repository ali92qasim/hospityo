<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_samples', function (Blueprint $table) {
            $table->id();
            $table->string('sample_id')->unique();
            $table->foreignId('lab_order_id')->constrained()->onDelete('cascade');
            $table->enum('sample_type', ['blood', 'urine', 'stool', 'sputum', 'csf', 'tissue', 'swab', 'other']);
            $table->enum('status', ['collected', 'received', 'processing', 'completed', 'rejected']);
            $table->timestamp('collected_at');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('collected_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('collection_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('storage_conditions')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_samples');
    }
};