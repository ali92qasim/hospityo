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
            $table->string('gpe_chest')->nullable()->after('allergy_notes');
            $table->string('gpe_abdomen')->nullable()->after('gpe_chest');
            $table->string('gpe_cvs')->nullable()->after('gpe_abdomen');
            $table->string('gpe_cns')->nullable()->after('gpe_cvs');
            $table->string('gpe_pupils')->nullable()->after('gpe_cns');
            $table->string('gpe_conjunctiva')->nullable()->after('gpe_pupils');
            $table->string('gpe_nails')->nullable()->after('gpe_conjunctiva');
            $table->string('gpe_throat')->nullable()->after('gpe_nails');
            $table->string('gpe_sclera')->nullable()->after('gpe_throat');
            $table->string('gpe_gcs')->nullable()->after('gpe_sclera');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'gpe_chest',
                'gpe_abdomen',
                'gpe_cvs',
                'gpe_cns',
                'gpe_pupils',
                'gpe_conjunctiva',
                'gpe_nails',
                'gpe_throat',
                'gpe_sclera',
                'gpe_gcs'
            ]);
        });
    }
};
