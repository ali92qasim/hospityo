<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('document_type'); // CNIC number, license number
            $table->date('issue_date')->nullable()->after('file_path');
            $table->string('issuing_authority')->nullable()->after('issue_date');
            $table->boolean('is_mandatory')->default(false)->after('notes');
            $table->boolean('is_verified')->default(false)->after('is_mandatory');
            $table->foreignId('verified_by')->nullable()->after('is_verified')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
        });

        // Document type templates (what documents are required per designation)
        Schema::create('document_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');              // cnic, degree, pmdc, nursing_license
            $table->string('label');                      // CNIC Copy, Medical Degree, PMDC Registration
            $table->string('applicable_to');              // all, medical, nursing, technical, designation:5
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('has_expiry')->default(false);
            $table->integer('expiry_reminder_days')->default(30); // days before expiry to alert
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_requirements');
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropColumn(['document_number', 'issue_date', 'issuing_authority', 'is_mandatory', 'is_verified', 'verified_by', 'verified_at']);
        });
    }
};
