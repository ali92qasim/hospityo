<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make dosage_form, manufacturer, and reorder_level nullable on the medicines table.
     * These fields are optional during initial medicine creation and can be filled in later.
     */
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('dosage_form')->nullable()->change();
            $table->string('manufacturer')->nullable()->change();
            $table->integer('reorder_level')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations — restore the columns to NOT NULL.
     * Note: existing rows with NULL values will need to be updated before rolling back.
     */
    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('dosage_form')->nullable(false)->change();
            $table->string('manufacturer')->nullable(false)->change();
            $table->integer('reorder_level')->default(10)->nullable(false)->change();
        });
    }
};
