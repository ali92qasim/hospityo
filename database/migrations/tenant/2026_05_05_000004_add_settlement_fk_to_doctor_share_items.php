<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_share_items', function (Blueprint $table) {
            // Add FK constraint on the existing settlement_id column
            // (column was added as unsignedBigInteger nullable in the original migration)
            $table->foreign('settlement_id')
                  ->references('id')
                  ->on('doctor_share_settlements')
                  ->nullOnDelete();

            // Snapshot of SUM(allocations.amount) captured at settlement time.
            // Stored so settlement reports can reproduce the exact collected amount
            // without replaying the allocation ledger.
            $table->decimal('collected_at_settlement', 10, 2)->nullable()->after('settlement_id');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_share_items', function (Blueprint $table) {
            $table->dropForeign(['settlement_id']);
            $table->dropColumn('collected_at_settlement');
        });
    }
};
