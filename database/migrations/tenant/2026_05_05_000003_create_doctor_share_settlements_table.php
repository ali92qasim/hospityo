<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Doctor Share Settlements ──────────────────────────────────────────
        // One row per settlement run. A settlement run aggregates all pending
        // doctor_share_items for a given doctor over a date range and records
        // the total amount disbursed.
        //
        // After a settlement run:
        //   - All included doctor_share_items are marked 'settled'
        //   - item_count reflects how many items were processed
        //   - total_settled_amount is the sum of share_amount for those items
        Schema::create('doctor_share_settlements', function (Blueprint $table) {
            $table->id();

            // NULL if the doctor record is later deleted; settlement history is preserved
            $table->foreignId('doctor_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // Inclusive date range covered by this settlement run
            $table->date('date_from');
            $table->date('date_to');

            // Number of doctor_share_items included in this settlement
            $table->unsignedInteger('item_count');

            // Sum of share_amount for all included items
            $table->decimal('total_settled_amount', 10, 2);

            // The user who initiated the settlement run
            $table->foreignId('created_by')->constrained('users');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Performance: per-doctor settlement history queries
            $table->index('doctor_id', 'dss_doctor_idx');

            // Performance: date-range listing of settlement runs
            $table->index('created_at', 'dss_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_share_settlements');
    }
};
