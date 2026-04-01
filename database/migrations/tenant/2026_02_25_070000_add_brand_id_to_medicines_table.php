<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            // Add brand_id as foreign key
            $table->foreignId('brand_id')->nullable()->after('category_id')->constrained('medicine_brands')->onDelete('set null');
            
            // Drop the old brand column if it exists (it was a string)
            if (Schema::hasColumn('medicines', 'brand')) {
                $table->dropColumn('brand');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
            
            // Restore the old brand column
            $table->string('brand')->nullable();
        });
    }
};
