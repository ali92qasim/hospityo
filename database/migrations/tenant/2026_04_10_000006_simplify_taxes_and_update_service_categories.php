<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Remove effective dates from taxes — unnecessary complexity
        Schema::table('taxes', function (Blueprint $table) {
            $table->dropColumn(['effective_from', 'effective_to']);
        });

        // Update service categories: lab_test and imaging → investigation
        DB::table('services')->where('category', 'lab_test')->update(['category' => 'investigation']);
        DB::table('services')->where('category', 'imaging')->update(['category' => 'investigation']);

        // Change services.category from ENUM to VARCHAR to support new values
        DB::statement("ALTER TABLE `services` MODIFY `category` VARCHAR(50) NOT NULL DEFAULT 'other'");

        // Update tax_mappings: lab_test and imaging → investigation
        DB::table('tax_mappings')
            ->where('applicable_on', 'service_category')
            ->whereIn('applicable_value', ['lab_test', 'imaging'])
            ->update(['applicable_value' => 'investigation']);
    }

    public function down(): void
    {
        Schema::table('taxes', function (Blueprint $table) {
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
        });
    }
};
