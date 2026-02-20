<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('allergies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('category')->nullable(); // drug, food, environmental, other
            $table->boolean('is_standard')->default(false); // true for seeded, false for custom
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('allergies');
    }
};
