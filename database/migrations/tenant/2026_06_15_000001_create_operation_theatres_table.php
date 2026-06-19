<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operation_theatres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('general'); // general, cardiac, ortho, ent, ophthalmic
            $table->string('status')->default('available'); // available, occupied, maintenance
            $table->string('floor')->nullable();
            $table->json('equipment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_theatres');
    }
};
