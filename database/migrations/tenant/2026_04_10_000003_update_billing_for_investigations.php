<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change bill_type ENUM to VARCHAR and update 'lab' to 'investigation'
        DB::statement("ALTER TABLE `bills` MODIFY `bill_type` VARCHAR(30) NOT NULL DEFAULT 'opd'");
        DB::table('bills')->where('bill_type', 'lab')->update(['bill_type' => 'investigation']);

        // Add investigation_id to bill_items
        Schema::table('bill_items', function (Blueprint $table) {
            $table->foreignId('investigation_id')->nullable()->after('service_id')
                  ->constrained('investigations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('bills')->where('bill_type', 'investigation')->update(['bill_type' => 'lab']);

        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('investigation_id');
        });
    }
};
