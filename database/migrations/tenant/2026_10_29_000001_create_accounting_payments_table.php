<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_type', 16);   // income|expense
            $table->string('payment_method', 16); // cash|bank
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('journal_entry_line_id')->constrained('journal_entry_lines')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('journal_entry_line_id');
            $table->index('payment_date');
            $table->index('payment_type');
            $table->index('payment_method');
            $table->index(['payment_type', 'payment_method', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payments');
    }
};
