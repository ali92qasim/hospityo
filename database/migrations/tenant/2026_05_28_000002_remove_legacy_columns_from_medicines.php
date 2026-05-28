<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            // batch_number — per-batch concern, lives on inventory_transactions.batch_no
            if (Schema::hasColumn('medicines', 'batch_number')) {
                $table->dropColumn('batch_number');
            }

            // stock_quantity — replaced by computed stock via InventoryTransaction
            if (Schema::hasColumn('medicines', 'stock_quantity')) {
                $table->dropColumn('stock_quantity');
            }

            // expiry_date — per-batch concern, lives on inventory_transactions.expiry_date
            if (Schema::hasColumn('medicines', 'expiry_date')) {
                $table->dropColumn('expiry_date');
            }

            // unit_price — replaced by selling_price (set below) or derived from stock_in cost
            if (Schema::hasColumn('medicines', 'unit_price')) {
                $table->dropColumn('unit_price');
            }
        });

        // Add selling_price as the clinic-controlled selling price per base unit
        // Nullable so existing medicines don't break — set it manually or derive from stock-in
        if (!Schema::hasColumn('medicines', 'selling_price')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->decimal('selling_price', 10, 2)->nullable()->after('strength')
                    ->comment('Clinic-set selling price per base unit. If null, falls back to latest stock-in unit_cost.');
            });
        }
    }

    public function down(): void
    {
        Schema::table('medicines', function (Blueprint $table) {
            $table->string('batch_number')->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->date('expiry_date')->nullable();
            $table->decimal('unit_price', 8, 2)->nullable();
        });

        if (Schema::hasColumn('medicines', 'selling_price')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->dropColumn('selling_price');
            });
        }
    }
};
