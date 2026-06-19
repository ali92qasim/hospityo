<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OT Consumable catalog — instruments, implants, disposables
        Schema::create('ot_consumables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->string('category'); // instrument, implant, disposable, suture, drape, other
            $table->string('unit')->default('pcs'); // pcs, pack, box, set
            $table->integer('current_stock')->default(0);
            $table->integer('reorder_level')->default(5);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('supplier_name')->nullable();
            $table->boolean('is_reusable')->default(false); // instruments are reusable
            $table->boolean('requires_serial_tracking')->default(false); // implants
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Stock-in transactions for OT consumables (FIFO batches)
        Schema::create('ot_consumable_stock_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ot_consumable_id')->constrained('ot_consumables')->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('remaining_quantity');
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->string('batch_no')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('serial_number')->nullable(); // for implants
            $table->string('supplier_name')->nullable();
            $table->string('reference_no')->nullable(); // PO reference
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // Per-surgery usage log — links consumed items to a specific surgery
        Schema::create('ot_consumable_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
            $table->foreignId('ot_consumable_id')->constrained('ot_consumables')->cascadeOnDelete();
            $table->foreignId('stock_in_id')->nullable()->constrained('ot_consumable_stock_ins')->nullOnDelete();
            $table->integer('quantity_used')->default(1);
            $table->string('serial_number')->nullable(); // implant serial for traceability
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ot_consumable_usages');
        Schema::dropIfExists('ot_consumable_stock_ins');
        Schema::dropIfExists('ot_consumables');
    }
};
