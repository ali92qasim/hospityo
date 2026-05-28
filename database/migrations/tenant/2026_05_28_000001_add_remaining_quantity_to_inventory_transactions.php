<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->integer('remaining_quantity')->nullable()->after('quantity');
        });

        // Backfill: every existing stock_in row starts with remaining_quantity = quantity
        // (assumes no stock has been consumed yet via FIFO — safe for dev environment)
        DB::table('inventory_transactions')
            ->where('type', 'stock_in')
            ->update(['remaining_quantity' => DB::raw('quantity')]);
    }

    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropColumn('remaining_quantity');
        });
    }
};
