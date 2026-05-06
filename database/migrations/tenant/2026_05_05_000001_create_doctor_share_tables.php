<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Doctor Share Rules ────────────────────────────────────────────────
        // Three-level configuration hierarchy:
        //   Level 1 (most specific): doctor_id + service_id/investigation_id
        //   Level 2 (doctor default): doctor_id only, service/investigation NULL
        //   Level 3 (global default): doctor_id NULL, service/investigation NULL
        Schema::create('doctor_share_rules', function (Blueprint $table) {
            $table->id();

            // NULL = global default rule (Level 3)
            $table->foreignId('doctor_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            // Exactly one of these is set for Level 1 rules; both NULL for Level 2/3
            $table->foreignId('service_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->foreignId('investigation_id')
                  ->nullable()
                  ->constrained()
                  ->nullOnDelete();

            $table->enum('share_type', ['percentage', 'fixed']);
            // percentage: 0.00–100.00 | fixed: PKR amount
            $table->decimal('share_value', 8, 2);

            // 'all' = applies to every bill type for this doctor/rule
            $table->enum('applies_to', ['opd', 'ipd', 'investigation', 'emergency', 'all'])
                  ->default('all');

            $table->boolean('is_active')->default(true);

            $table->foreignId('created_by')->constrained('users');

            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate rules for the same combination
            $table->unique(
                ['doctor_id', 'service_id', 'investigation_id', 'applies_to'],
                'dsr_unique_rule'
            );

            // Performance: rule resolution queries filter by doctor + active
            $table->index(['doctor_id', 'is_active'], 'dsr_doctor_active_idx');
            $table->index(['service_id', 'is_active'], 'dsr_service_active_idx');
            $table->index(['investigation_id', 'is_active'], 'dsr_investigation_active_idx');
        });

        // ── Doctor Share Items ────────────────────────────────────────────────
        // One row per bill_item where a doctor share was calculated.
        //
        // This table defines the EARNED SHARE LIABILITY only.
        // It answers: "how much does the hospital owe this doctor for this item?"
        //
        // It does NOT track collection. Collection is tracked in the immutable
        // event ledger: doctor_share_allocations.
        //
        // To compute collected amount for a share item:
        //   SELECT SUM(amount) FROM doctor_share_allocations
        //   WHERE doctor_share_item_id = ?
        //
        // This separation means:
        //   - share_amount is written once and never mutated
        //   - refunds are first-class events in the allocation ledger
        //   - settlement runs read a point-in-time SUM, not a mutable column
        //   - disputes are resolved by replaying the allocation log
        Schema::create('doctor_share_items', function (Blueprint $table) {
            $table->id();

            // RESTRICT: share records must never be silently deleted when a bill
            // is deleted. The service layer voids them first; if any settled items
            // remain, the bill delete is blocked at the application layer.
            $table->foreignId('bill_id')
                  ->constrained()
                  ->restrictOnDelete();

            // RESTRICT: bill_items are deleted during bill updates. The service
            // layer voids share items before bill_items are deleted, so this FK
            // should never be violated in normal flow. RESTRICT makes violations
            // visible rather than silently destroying financial records.
            $table->unsignedBigInteger('bill_item_id')->unique();
            $table->foreign('bill_item_id')
                  ->references('id')
                  ->on('bill_items')
                  ->restrictOnDelete();

            $table->foreignId('doctor_id')
                  ->constrained()
                  ->restrictOnDelete();

            // Nullable: rule may be deleted after calculation; snapshot preserves history
            $table->foreignId('rule_id')
                  ->nullable()
                  ->constrained('doctor_share_rules')
                  ->nullOnDelete();

            // Frozen copy of the rule at calculation time — audit trail
            // Structure: {rule_id, level, share_type, share_value, applies_to,
            //             doctor_id, service_id, investigation_id}
            $table->json('rule_snapshot');

            // Base amount the share was calculated on (item qty × unit_price, pre-tax)
            $table->decimal('base_amount', 10, 2);

            // Earned share — set once at bill creation, immutable thereafter.
            // This is the maximum amount the doctor can receive for this item.
            $table->decimal('share_amount', 10, 2);

            // Status lifecycle:
            //   pending  — calculated, not yet included in a settlement run
            //   settled  — included in a settlement run (disbursed to doctor)
            //   voided   — bill updated or cancelled; record preserved for audit
            //
            // NOTE: there is no 'partially_collected' or 'fully_collected' status.
            // Collection state is derived from doctor_share_allocations, not stored here.
            // A share item moves from 'pending' to 'settled' only when a settlement
            // run explicitly processes it.
            $table->enum('status', ['pending', 'settled', 'voided'])
                  ->default('pending');

            // Void audit trail
            $table->enum('void_reason', ['bill_updated', 'bill_cancelled', 'manual'])
                  ->nullable();
            $table->timestamp('voided_at')->nullable();

            // Populated when a settlement run processes this item (Phase 2)
            $table->unsignedBigInteger('settlement_id')->nullable();

            $table->timestamps();

            // Performance: settlement queries filter by doctor + status
            $table->index(['doctor_id', 'status'], 'dsi_doctor_status_idx');

            // Performance: bill-level void/recalculate operations
            $table->index('bill_id', 'dsi_bill_idx');

            // Performance: settlement run processing
            $table->index(['status', 'settlement_id'], 'dsi_status_settlement_idx');
        });
    }

        // ── Doctor Share Allocations ──────────────────────────────────────────
        // Immutable event ledger. One row per payment event that allocates
        // collected cash toward a specific share item.
        //
        // This table is append-only. Rows are NEVER updated or deleted.
        //
        // Positive amount = cash collected toward the share (payment received).
        // Negative amount = reversal (refund issued against a prior payment).
        //
        // To compute total collected for a share item:
        //   SELECT SUM(amount) FROM doctor_share_allocations
        //   WHERE doctor_share_item_id = ?
        //
        // To compute total collected for a doctor in a period:
        //   SELECT SUM(amount) FROM doctor_share_allocations
        //   WHERE doctor_id = ? AND created_at BETWEEN ? AND ?
        //
        // To reproduce a settlement run exactly:
        //   SELECT SUM(amount) FROM doctor_share_allocations
        //   WHERE doctor_share_item_id = ? AND created_at <= <settlement_date>
        //
        // This design means:
        //   - No mutable column is ever updated after insert
        //   - Refunds are first-class events, not corrections to existing rows
        //   - Any point-in-time balance is computable by filtering on created_at
        //   - Disputes are resolved by replaying the log, not reading a cached value
        Schema::create('doctor_share_allocations', function (Blueprint $table) {
            $table->id();

            // The share item this allocation is against
            $table->foreignId('doctor_share_item_id')
                  ->constrained('doctor_share_items')
                  ->restrictOnDelete();

            // The payment that triggered this allocation
            // Nullable for manual adjustments (e.g., write-offs, corrections)
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->restrictOnDelete();

            // Denormalized for query performance — avoids joining through
            // doctor_share_items on every settlement/reporting query
            $table->foreignId('bill_id')
                  ->constrained()
                  ->restrictOnDelete();

            $table->foreignId('doctor_id')
                  ->constrained()
                  ->restrictOnDelete();

            // Positive = collection, Negative = reversal/refund
            // Stored as DECIMAL not FLOAT — financial precision required
            $table->decimal('amount', 10, 2);

            // Event type for reporting and audit filtering
            $table->enum('type', ['collection', 'reversal'])
                  ->default('collection');

            // Human-readable reason — required for reversals, optional for collections
            $table->string('notes')->nullable();

            // created_at only — no updated_at. This row is immutable after insert.
            $table->timestamp('created_at')->useCurrent();

            // Performance: settlement run queries by doctor + date range
            $table->index(['doctor_id', 'created_at'], 'dsa_doctor_date_idx');

            // Performance: per-item balance queries
            $table->index('doctor_share_item_id', 'dsa_item_idx');

            // Performance: payment-level lookup (idempotency check)
            $table->unique(['payment_id', 'doctor_share_item_id'], 'dsa_payment_item_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_share_allocations');
        Schema::dropIfExists('doctor_share_items');
        Schema::dropIfExists('doctor_share_rules');
    }
};
