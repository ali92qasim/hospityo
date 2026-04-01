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
        Schema::table('consultations', function (Blueprint $table) {
            // Drop old JSON column
            $table->dropColumn('provisional_diagnosis_conditions');
            
            // Add new individual fields
            $table->string('diagnosis_dm')->nullable()->after('provisional_diagnosis');
            $table->string('diagnosis_htn')->nullable()->after('diagnosis_dm');
            $table->string('diagnosis_ihd')->nullable()->after('diagnosis_htn');
            $table->string('diagnosis_asthma')->nullable()->after('diagnosis_ihd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            // Drop new fields
            $table->dropColumn([
                'diagnosis_dm',
                'diagnosis_htn',
                'diagnosis_ihd',
                'diagnosis_asthma'
            ]);
            
            // Restore old JSON column
            $table->json('provisional_diagnosis_conditions')->nullable()->after('provisional_diagnosis');
        });
    }
};
