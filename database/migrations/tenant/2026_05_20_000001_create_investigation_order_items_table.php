<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create the order items table — one row per investigation per order
        Schema::create('investigation_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('investigation_order_id')
                  ->constrained('investigation_orders')
                  ->cascadeOnDelete();

            $table->foreignId('investigation_id')
                  ->constrained('investigations')
                  ->restrictOnDelete();

            $table->unsignedSmallInteger('quantity')->default(1);

            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');

            // Per-item status so each test can progress independently
            $table->enum('status', ['ordered', 'collected', 'testing', 'verified', 'reported', 'cancelled'])
                  ->default('ordered');

            $table->text('clinical_notes')->nullable();

            $table->enum('test_location', ['indoor', 'outdoor'])->default('outdoor');

            $table->timestamps();

            // Prevent duplicate investigation on the same order
            $table->unique(['investigation_order_id', 'investigation_id'], 'ioi_order_investigation_unique');

            $table->index('investigation_order_id', 'ioi_order_idx');
            $table->index('investigation_id',       'ioi_investigation_idx');
            $table->index('status',                 'ioi_status_idx');
        });

        // Migrate existing single-investigation orders into items
        if (Schema::hasColumn('investigation_orders', 'investigation_id')) {
            $orders = DB::connection('tenant')
                ->table('investigation_orders')
                ->whereNotNull('investigation_id')
                ->get(['id', 'investigation_id', 'quantity', 'priority', 'status', 'clinical_notes', 'test_location']);

            foreach ($orders as $order) {
                DB::connection('tenant')->table('investigation_order_items')->insert([
                    'investigation_order_id' => $order->id,
                    'investigation_id'       => $order->investigation_id,
                    'quantity'               => $order->quantity ?? 1,
                    'priority'               => $order->priority ?? 'routine',
                    'status'                 => $order->status ?? 'ordered',
                    'clinical_notes'         => $order->clinical_notes ?? null,
                    'test_location'          => $order->test_location ?? 'outdoor',
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }

            // Drop the now-redundant columns from the parent order
            Schema::table('investigation_orders', function (Blueprint $table) {
                $table->dropForeign(['investigation_id']);
                $table->dropColumn(['investigation_id', 'quantity', 'test_location']);
            });
        }
    }

    public function down(): void
    {
        // Restore columns on investigation_orders
        Schema::table('investigation_orders', function (Blueprint $table) {
            $table->foreignId('investigation_id')->nullable()->constrained('investigations')->nullOnDelete();
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->enum('test_location', ['indoor', 'outdoor'])->default('outdoor');
        });

        Schema::dropIfExists('investigation_order_items');
    }
};
