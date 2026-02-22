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
        // Step 1: Drop foreign key constraint on lab_test_id
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->dropForeign(['lab_test_id']);
        });

        // Step 2: Rename the table
        Schema::rename('lab_orders', 'investigation_orders');

        // Step 3: Rename the column lab_test_id to investigation_id
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->renameColumn('lab_test_id', 'investigation_id');
        });

        // Step 4: Add foreign key constraint with new column and table names
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->foreign('investigation_id')->references('id')->on('investigations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop foreign key constraint on investigation_id
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->dropForeign(['investigation_id']);
        });

        // Step 2: Rename the column investigation_id back to lab_test_id
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->renameColumn('investigation_id', 'lab_test_id');
        });

        // Step 3: Rename the table back
        Schema::rename('investigation_orders', 'lab_orders');

        // Step 4: Add foreign key constraint with original column and table names
        Schema::table('lab_orders', function (Blueprint $table) {
            $table->foreign('lab_test_id')->references('id')->on('investigations')->onDelete('cascade');
        });
    }
};
