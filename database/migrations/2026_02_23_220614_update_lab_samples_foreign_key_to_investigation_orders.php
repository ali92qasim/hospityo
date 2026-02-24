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
        // Step 1: Drop foreign key constraint on lab_order_id
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropForeign(['lab_order_id']);
        });

        // Step 2: Rename the column lab_order_id to investigation_order_id
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->renameColumn('lab_order_id', 'investigation_order_id');
        });

        // Step 3: Add foreign key constraint with new column and table names
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->foreign('investigation_order_id')->references('id')->on('investigation_orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Step 1: Drop foreign key constraint on investigation_order_id
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->dropForeign(['investigation_order_id']);
        });

        // Step 2: Rename the column investigation_order_id back to lab_order_id
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->renameColumn('investigation_order_id', 'lab_order_id');
        });

        // Step 3: Add foreign key constraint with original column and table names
        Schema::table('lab_samples', function (Blueprint $table) {
            $table->foreign('lab_order_id')->references('id')->on('investigation_orders')->onDelete('cascade');
        });
    }
};
