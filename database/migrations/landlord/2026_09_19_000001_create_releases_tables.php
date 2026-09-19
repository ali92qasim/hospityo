<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('landlord')->create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->text('summary')->nullable();
            $table->timestamp('released_at');
            $table->timestamps();
        });

        Schema::connection('landlord')->create('changelog_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('release_id')->constrained('releases')->cascadeOnDelete();
            $table->string('category');
            $table->text('description');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('landlord')->dropIfExists('changelog_entries');
        Schema::connection('landlord')->dropIfExists('releases');
    }
};
