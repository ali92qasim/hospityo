<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of Accounts
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();           // 1000, 1100, 4100
            $table->string('name');                      // Cash, Bank, OPD Revenue
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // system accounts can't be deleted
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active']);
        });

        // General Ledger / Journal Entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->string('reference_type')->nullable(); // Bill, Payment, PurchaseOrder
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->boolean('is_auto')->default(false);  // auto-generated vs manual
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_date');
        });

        // Journal Entry Lines (double-entry)
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('account_id')->constrained();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('narration')->nullable();
            $table->timestamps();

            $table->index('account_id');
        });

        // Sub-ledgers (Patient, Vendor, Insurance)
        Schema::create('sub_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->string('ledger_type');               // patient, vendor, insurance
            $table->unsignedBigInteger('ledger_id');     // patient_id, supplier_id
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->text('narration')->nullable();
            $table->timestamps();

            $table->index(['ledger_type', 'ledger_id']);
        });

        // Fiscal Years
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // FY 2026-27
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_ledger_entries');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('fiscal_years');
        Schema::dropIfExists('accounts');
    }
};
