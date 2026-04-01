<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Medicine;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('name');
        });
        
        // Generate SKU for existing medicines
        $medicines = Medicine::all();
        foreach ($medicines as $medicine) {
            $medicine->sku = Medicine::generateSKU(
                $medicine->name,
                $medicine->strength,
                $medicine->dosage_form,
                $medicine->brand_id
            );
            $medicine->save();
        }
        
        // Now make SKU unique
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('sku')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
    }
};
