<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_print_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('name');
            $table->enum('mode', ['overlay_physical', 'digitized_background']);
            $table->string('paper_size');
            $table->string('orientation');
            $table->string('background_image_path')->nullable();
            $table->boolean('is_active')->default(false);
            $table->decimal('rx_start_y', 8, 2);
            $table->decimal('rx_row_height', 8, 2);
            $table->unsignedInteger('rx_max_rows');
            $table->enum('rx_overflow_policy', ['second_page_plain', 'shrink_font', 'cap_with_note']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_print_templates');
    }
};
