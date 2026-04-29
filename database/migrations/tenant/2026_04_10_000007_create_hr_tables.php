<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Designations (Job Titles)
        Schema::create('designations', function (Blueprint $table) {
            $table->id();
            $table->string('name');                      // Consultant, Nurse, Lab Technician
            $table->string('category');                   // medical, nursing, admin, technical, support
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained()->nullOnDelete();

            // Personal Info
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('cnic', 15)->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relation')->nullable();

            // Employment Info
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern'])->default('full_time');
            $table->date('joining_date');
            $table->date('probation_end_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->enum('status', ['active', 'on_leave', 'suspended', 'terminated', 'resigned'])->default('active');

            // Salary Info (base for payroll)
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_no')->nullable();
            $table->string('bank_branch')->nullable();

            // Shift defaults
            $table->string('default_shift')->nullable();  // morning, evening, night
            $table->time('shift_start')->nullable();
            $table->time('shift_end')->nullable();

            $table->string('photo')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'department_id']);
            $table->index('employee_no');
        });

        // Employee Documents
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('title');                     // CNIC Copy, Degree, Contract
            $table->string('document_type');             // cnic, degree, contract, certification, other
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('designations');
    }
};
