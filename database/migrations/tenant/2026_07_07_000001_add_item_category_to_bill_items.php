<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->string('item_category', 30)->default('opd')->after('investigation_id');
        });

        $items = DB::table('bill_items')
            ->join('bills', 'bills.id', '=', 'bill_items.bill_id')
            ->select(
                'bill_items.id',
                'bill_items.investigation_id',
                'bill_items.service_id',
                'bills.bill_type'
            )
            ->get();

        foreach ($items as $item) {
            $category = $item->investigation_id
                ? 'investigation'
                : ($item->bill_type ?: 'opd');

            DB::table('bill_items')
                ->where('id', $item->id)
                ->update(['item_category' => $category]);
        }
    }

    public function down(): void
    {
        Schema::table('bill_items', function (Blueprint $table) {
            $table->dropColumn('item_category');
        });
    }
};
