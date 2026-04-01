<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration is redundant as department_id was already removed
        // in 2024_01_01_000009_restructure_department_relationships.php
        // Keeping it for migration history but making it safe to run
        if (Schema::hasTable('appointments') && Schema::hasColumn('appointments', 'department_id')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropForeign(['department_id']);
                $table->dropColumn('department_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('doctor_id')->constrained()->onDelete('cascade');
            }
        });
    }
};