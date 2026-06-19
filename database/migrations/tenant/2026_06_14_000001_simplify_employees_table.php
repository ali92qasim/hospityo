<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('name')->nullable()->after('employee_no');
        });

        // Backfill: combine first_name + last_name into name for existing records
        DB::table('employees')->whereNull('name')->update([
            'name' => DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))"),
        ]);

        // Trim any extra spaces
        DB::table('employees')->update([
            'name' => DB::raw("TRIM(name)"),
        ]);

        // Make first_name and last_name nullable (kept for backward compat)
        Schema::table('employees', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('name');
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
        });
    }
};
