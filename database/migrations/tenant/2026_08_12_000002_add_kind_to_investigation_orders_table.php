<?php

use App\Enums\InvestigationKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('investigation_orders', 'kind')) {
            Schema::table('investigation_orders', function (Blueprint $table) {
                $table->string('kind', 20)->nullable()->after('doctor_id');
            });
        }

        $mixedOrderIds = [];

        $orders = DB::table('investigation_orders')->orderBy('id')->get(['id']);

        foreach ($orders as $order) {
            $kinds = DB::table('investigation_order_items')
                ->join('investigations', 'investigations.id', '=', 'investigation_order_items.investigation_id')
                ->where('investigation_order_items.investigation_order_id', $order->id)
                ->distinct()
                ->pluck('investigations.kind')
                ->filter()
                ->values();

            if ($kinds->count() > 1) {
                $mixedOrderIds[] = $order->id;
                continue;
            }

            $kind = $kinds->first() ?? InvestigationKind::Lab->value;

            DB::table('investigation_orders')
                ->where('id', $order->id)
                ->update(['kind' => $kind]);
        }

        if ($mixedOrderIds !== []) {
            throw new RuntimeException(
                'Cannot backfill investigation_orders.kind; mixed-kind orders found: '.implode(', ', $mixedOrderIds)
            );
        }

        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('investigation_orders', function (Blueprint $table) {
                $table->string('kind', 20)->nullable(false)->change();
            });
        } else {
            DB::statement("ALTER TABLE investigation_orders MODIFY kind VARCHAR(20) NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('investigation_orders', 'kind')) {
            Schema::table('investigation_orders', function (Blueprint $table) {
                $table->dropColumn('kind');
            });
        }
    }
};
