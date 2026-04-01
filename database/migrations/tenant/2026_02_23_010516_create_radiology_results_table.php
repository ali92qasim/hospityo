<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('radiology_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investigation_order_id')
                  ->constrained('investigation_orders')
                  ->onDelete('cascade');
            $table->longText('report_text')->nullable();
            $table->text('impression')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['draft', 'final', 'amended'])->default('draft');
            $table->foreignId('radiologist_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('reported_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_results');
    }
};
