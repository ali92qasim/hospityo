<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (Schema::hasColumn('medicines', 'dosage_form')) {
                $table->dropColumn('dosage_form');
            }

            if (Schema::hasColumn('medicines', 'manufacturer')) {
                $table->dropColumn('manufacturer');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            if (!Schema::hasColumn('medicines', 'dosage_form')) {
                $table->string('dosage_form')->nullable()->after('category_id');
            }

            if (!Schema::hasColumn('medicines', 'manufacturer')) {
                $table->string('manufacturer')->nullable()->after('reorder_level');
            }
        });
    }
};
