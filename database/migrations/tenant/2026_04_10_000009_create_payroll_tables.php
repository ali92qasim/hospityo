<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Salary Components (allowances & deductions templates)
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // House Rent, Medical, EOBI, Tax
            $table->string('code', 20)->unique();        // HRA, MED, EOBI, TAX
            $table->enum('type', ['allowance', 'deduction']);
            $table->enum('calculation', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('default_amount', 12, 2)->default(0);  // fixed amount or percentage value
            $table->string('percentage_of')->nullable();  // basic_salary, gross_salary
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Employee Salary Structure (per-employee overrides)
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('salary_component_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'salary_component_id'], 'emp_sal_comp_unique');
        });

        // Payroll Runs (monthly batch)
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->string('title');                     // Payroll - January 2026
            $table->integer('year');
            $table->integer('month');
            $table->enum('status', ['draft', 'processing', 'completed', 'cancelled'])->default('draft');
            $table->integer('total_employees')->default(0);
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        // Payslips (per employee per payroll run)
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payslip_no')->unique();

            // Attendance summary
            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);

            // Earnings
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('total_allowances', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('gross_salary', 12, 2)->default(0);

            // Deductions
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('absent_deduction', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);

            // Net
            $table->decimal('net_salary', 12, 2)->default(0);

            // Details
            $table->json('earnings_breakdown')->nullable();   // [{component, amount}]
            $table->json('deductions_breakdown')->nullable();  // [{component, amount}]

            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
    }
};
