<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('dosage')->nullable()->change();
            $table->string('frequency')->nullable()->change();
            $table->string('duration')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->string('dosage')->nullable(false)->default('As directed')->change();
            $table->string('frequency')->nullable(false)->default('As directed')->change();
            $table->string('duration')->nullable(false)->default('As prescribed')->change();
        });
    }
};
