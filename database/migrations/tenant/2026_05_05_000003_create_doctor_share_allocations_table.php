<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * doctor_share_allocations — immutable payment event ledger.
     *
     * Each row records a single collection event: a payment that contributed
     * a specific amount toward a specific doctor share item.
     *
     * This table is append-only. Rows are NEVER updated or deleted.
     * Refunds are handled by inserting a new row with a negative amount.
     *
     * The total collected for a share item is always:
     *   SELECT SUM(amount) FROM doctor_share_allocations
     *   WHERE doctor_share_item_id = ?
     *
     * This design means:
     *   - No mutable state on doctor_share_items
     *   - Full audit trail of every payment event
     *   - Refunds are first-class events, not corrections
     *   - Settlement runs can snapshot the SUM at the time they run
     *   - Disputes can be resolved by replaying the event log
     */
    public function up(): void
    {
        Schema::create('doctor_share_allocations', function (Blueprint $table) {
            $table->id();

            // The share item this allocation belongs to
            $table->foreignId('doctor_share_item_id')
                  ->constrained('doctor_share_items')
                  ->restrictOnDelete();

            // The payment that triggered this allocation
            // Nullable to support manual adjustments (e.g., write-offs)
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->restrictOnDelete();

            // Denormalised for query performance — avoids joining through
            // doctor_share_items on every settlement/reporting query
            $table->foreignId('bill_id')
                  ->constrained()
                  ->restrictOnDelete();

            // Positive = collection from payment
            // Negative = reversal from refund
            // Scale 10,2 matches doctor_share_items.share_amount
            $table->decimal('amount', 10, 2);

            // created_at only — this table is immutable, updated_at is meaningless
            $table->timestamp('created_at')->useCurrent();

            // Performance: sum allocations per share item (settlement, reporting)
            $table->index('doctor_share_item_id', 'dsa_item_idx');

            // Performance: find all allocations for a bill (void check, reporting)
            $table->index('bill_id', 'dsa_bill_idx');

            // Performance: find all allocations for a payment (idempotency check)
            $table->index('payment_id', 'dsa_payment_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_share_allocations');
    }
};
