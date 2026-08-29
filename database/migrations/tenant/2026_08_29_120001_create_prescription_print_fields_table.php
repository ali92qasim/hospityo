<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_print_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('prescription_print_templates')->cascadeOnDelete();
            $table->string('field_key');
            $table->decimal('x_mm', 8, 2);
            $table->decimal('y_mm', 8, 2);
            $table->decimal('font_size', 8, 2);
            $table->string('font_weight')->default('normal');
            $table->enum('align', ['left', 'center', 'right'])->default('left');
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->unique(['template_id', 'field_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_print_fields');
    }
};
