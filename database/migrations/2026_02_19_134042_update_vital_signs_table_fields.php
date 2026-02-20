<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            // Rename respiratory_rate to spo2
            $table->renameColumn('respiratory_rate', 'spo2');
            
            // Add BSR field (Blood Sugar Random)
            $table->decimal('bsr', 5, 2)->nullable()->after('spo2');
            
            // Change height to store feet (decimal for feet and inches like 5.6 = 5'6")
            $table->decimal('height', 4, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vital_signs', function (Blueprint $table) {
            // Reverse the changes
            $table->renameColumn('spo2', 'respiratory_rate');
            $table->dropColumn('bsr');
            $table->decimal('height', 5, 2)->nullable()->change();
        });
    }
};
