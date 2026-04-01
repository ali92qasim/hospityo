<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            // Add category_id as foreign key
            $table->foreignId('category_id')->nullable()->after('brand')->constrained('medicine_categories')->onDelete('set null');
            
            // Drop the old category column if it exists (it was a string)
            if (Schema::hasColumn('medicines', 'category')) {
                $table->dropColumn('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
            
            // Restore the old category column
            $table->string('category')->nullable();
        });
    }
};
