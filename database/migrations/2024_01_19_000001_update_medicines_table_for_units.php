<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // First, create units if they don't exist
        if (!Schema::hasTable('units')) {
            // Run units migration first
            return;
        }

        Schema::table('medicines', function (Blueprint $table) {
            // Check if unit column exists before dropping it
            if (Schema::hasColumn('medicines', 'unit')) {
                $table->dropColumn('unit');
            }
            // Add columns as nullable first
            $table->unsignedBigInteger('base_unit_id')->nullable()->after('strength');
            $table->unsignedBigInteger('purchase_unit_id')->nullable()->after('base_unit_id');
            $table->unsignedBigInteger('dispensing_unit_id')->nullable()->after('purchase_unit_id');
        });

        // Get the first unit ID (piece) as default
        $pieceUnitId = DB::table('units')->where('name', 'Piece')->value('id');
        
        if ($pieceUnitId) {
            // Update existing records with default unit
            DB::table('medicines')->update([
                'base_unit_id' => $pieceUnitId,
                'purchase_unit_id' => $pieceUnitId,
                'dispensing_unit_id' => $pieceUnitId
            ]);
        }

        // Now add foreign key constraints
        Schema::table('medicines', function (Blueprint $table) {
            $table->foreign('base_unit_id')->references('id')->on('units');
            $table->foreign('purchase_unit_id')->references('id')->on('units');
            $table->foreign('dispensing_unit_id')->references('id')->on('units');
        });
    }

    public function down()
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->dropForeign(['base_unit_id']);
            $table->dropForeign(['purchase_unit_id']);
            $table->dropForeign(['dispensing_unit_id']);
            $table->dropColumn(['base_unit_id', 'purchase_unit_id', 'dispensing_unit_id']);
            if (!Schema::hasColumn('medicines', 'unit')) {
                $table->string('unit')->after('strength')->default('pc');
            }
        });
    }
};