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
        // Step 1: Drop foreign key constraints that reference lab_tests
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['lab_test_id']);
        });

        Schema::table('lab_test_parameters', function (Blueprint $table) {
            $table->dropForeign(['lab_test_id']);
        });

        // Step 2: Rename the table
        Schema::rename('lab_tests', 'investigations');

        // Step 3: Recreate foreign key constraints with new table name
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->foreign('lab_test_id')->references('id')->on('investigations')->onDelete('cascade');
        });

        Schema::table('lab_test_parameters', function (Blueprint $table) {
            $table->foreign('lab_test_id')->references('id')->on('investigations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop foreign key constraints that reference investigations
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['lab_test_id']);
        });

        Schema::table('lab_test_parameters', function (Blueprint $table) {
            $table->dropForeign(['lab_test_id']);
        });

        // Step 2: Rename the table back
        Schema::rename('investigations', 'lab_tests');

        // Step 3: Recreate foreign key constraints with original table name
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->foreign('lab_test_id')->references('id')->on('lab_tests')->onDelete('cascade');
        });

        Schema::table('lab_test_parameters', function (Blueprint $table) {
            $table->foreign('lab_test_id')->references('id')->on('lab_tests')->onDelete('cascade');
        });
    }
};
