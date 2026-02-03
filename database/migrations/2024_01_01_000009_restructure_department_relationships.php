<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove department_id from appointments table
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        // Remove department_id from visits table
        Schema::table('visits', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        // Add department_id to doctors table
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained()->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Add back department_id to appointments table
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
        });

        // Add back department_id to visits table
        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
        });

        // Remove department_id from doctors table
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }
};