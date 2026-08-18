<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['lab_order_items', 'imaging_order_items'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'test_location')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('test_location');
            });
        }
    }

    public function down(): void
    {
        foreach (['lab_order_items', 'imaging_order_items'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'test_location')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->string('test_location', 20)->default('outdoor');
            });
        }
    }
};
