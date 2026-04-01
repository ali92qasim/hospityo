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
        Schema::table('lab_results', function (Blueprint $table) {
            // Drop existing foreign key constraint
            $table->dropForeign(['lab_order_id']);
        });

        Schema::table('lab_results', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('lab_order_id', 'investigation_order_id');
        });

        Schema::table('lab_results', function (Blueprint $table) {
            // Add new foreign key constraint
            $table->foreign('investigation_order_id')
                  ->references('id')
                  ->on('investigation_orders')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['investigation_order_id']);
        });

        Schema::table('lab_results', function (Blueprint $table) {
            // Rename the column back
            $table->renameColumn('investigation_order_id', 'lab_order_id');
        });

        Schema::table('lab_results', function (Blueprint $table) {
            // Restore original foreign key constraint
            $table->foreign('lab_order_id')
                  ->references('id')
                  ->on('lab_orders')
                  ->onDelete('cascade');
        });
    }
};
