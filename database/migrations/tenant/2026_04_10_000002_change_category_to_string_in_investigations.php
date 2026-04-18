<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Change ENUM to VARCHAR for category and sample_type
        DB::statement("ALTER TABLE `investigations` MODIFY `category` VARCHAR(50) NOT NULL DEFAULT 'hematology'");
        DB::statement("ALTER TABLE `investigations` MODIFY `sample_type` VARCHAR(50) NULL DEFAULT 'other'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `investigations` MODIFY `category` ENUM('hematology','biochemistry','microbiology','immunology','pathology','molecular') NOT NULL");
        DB::statement("ALTER TABLE `investigations` MODIFY `sample_type` ENUM('blood','urine','stool','sputum','csf','tissue','swab','other') NOT NULL");
    }
};
