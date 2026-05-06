<?php

use App\Models\EmployeeDocument;
use App\Models\DocumentRequirement;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->department = Department::create([
        'name' => 'Emergency',
        'status' => 'active',
    ]);

    $this->designation = Designation::create([
        'name' => 'Consultant',
        'category' => 'medical',
        'is_active' => true,
    ]);

    $this->employee = Employee::create([
        'first_name' => 'Dr. Ahmed',
        'last_name' => 'Khan',
        'department_id' => $this->department->id,
        'designation_id' => $this->designation->id,
        'joining_date' => '2026-01-01',
        'basic_salary' => 150000,
        'status' => 'active',
    ]);
});

it('can create employee document', function () {
    $doc = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'PMDC Registration',
        'document_type' => 'pmdc',
        'document_number' => 'PMDC-12345',
        'file_path' => 'documents/pmdc-12345.pdf',
        'issue_date' => '2024-01-01',
        'expiry_date' => '2027-01-01',
        'issuing_authority' => 'PMDC Pakistan',
        'is_mandatory' => true,
        'is_verified' => false,
    ]);

    expect($doc->employee->full_name)->toBe('Dr. Ahmed Khan')
        ->and($doc->document_number)->toBe('PMDC-12345')
        ->and($doc->is_mandatory)->toBeTrue()
        ->and($doc->is_verified)->toBeFalse();
});

it('detects expired documents', function () {
    $expired = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Expired License',
        'document_type' => 'license',
        'file_path' => 'documents/license.pdf',
        'expiry_date' => now()->subDays(10),
    ]);

    $valid = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Valid License',
        'document_type' => 'license',
        'file_path' => 'documents/license2.pdf',
        'expiry_date' => now()->addYear(),
    ]);

    expect($expired->isExpired())->toBeTrue()
        ->and($valid->isExpired())->toBeFalse()
        ->and(EmployeeDocument::expired()->count())->toBe(1);
});

it('detects documents expiring soon', function () {
    EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Expiring Soon',
        'document_type' => 'cnic',
        'file_path' => 'documents/cnic.pdf',
        'expiry_date' => now()->addDays(15),
    ]);

    EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Not Expiring Soon',
        'document_type' => 'degree',
        'file_path' => 'documents/degree.pdf',
        'expiry_date' => now()->addYear(),
    ]);

    expect(EmployeeDocument::expiringSoon(30)->count())->toBe(1);
});

it('filters mandatory documents', function () {
    EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'CNIC',
        'document_type' => 'cnic',
        'file_path' => 'documents/cnic.pdf',
        'is_mandatory' => true,
    ]);

    EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Optional Cert',
        'document_type' => 'certification',
        'file_path' => 'documents/cert.pdf',
        'is_mandatory' => false,
    ]);

    expect(EmployeeDocument::mandatory()->count())->toBe(1);
});

it('filters unverified documents', function () {
    EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Verified',
        'document_type' => 'cnic',
        'file_path' => 'documents/cnic.pdf',
        'is_verified' => true,
        'verified_by' => $this->user->id,
        'verified_at' => now(),
    ]);

    EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Unverified',
        'document_type' => 'degree',
        'file_path' => 'documents/degree.pdf',
        'is_verified' => false,
    ]);

    expect(EmployeeDocument::unverified()->count())->toBe(1);
});

it('calculates days until expiry', function () {
    $doc = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'PMDC',
        'document_type' => 'pmdc',
        'file_path' => 'documents/pmdc.pdf',
        'expiry_date' => now()->addDays(60),
    ]);

    expect($doc->days_until_expiry)->toBe(60);
});

it('returns null days until expiry when no expiry date', function () {
    $doc = EmployeeDocument::create([
        'employee_id' => $this->employee->id,
        'title' => 'Degree',
        'document_type' => 'degree',
        'file_path' => 'documents/degree.pdf',
        'expiry_date' => null,
    ]);

    expect($doc->days_until_expiry)->toBeNull();
});

it('creates document requirements', function () {
    $req = DocumentRequirement::create([
        'document_type' => 'pmdc',
        'label' => 'PMDC Registration',
        'applicable_to' => 'medical',
        'is_mandatory' => true,
        'has_expiry' => true,
        'expiry_reminder_days' => 60,
        'is_active' => true,
    ]);

    expect($req->is_mandatory)->toBeTrue()
        ->and($req->has_expiry)->toBeTrue()
        ->and($req->applicable_to)->toBe('medical');
});

it('gets requirements applicable to medical staff', function () {
    DocumentRequirement::create([
        'document_type' => 'cnic',
        'label' => 'CNIC Copy',
        'applicable_to' => 'all',
        'is_mandatory' => true,
        'is_active' => true,
    ]);

    DocumentRequirement::create([
        'document_type' => 'pmdc',
        'label' => 'PMDC Registration',
        'applicable_to' => 'medical',
        'is_mandatory' => true,
        'is_active' => true,
    ]);

    DocumentRequirement::create([
        'document_type' => 'pnc',
        'label' => 'PNC License',
        'applicable_to' => 'nursing',
        'is_mandatory' => true,
        'is_active' => true,
    ]);

    $requirements = DocumentRequirement::getForEmployee($this->employee);

    // Should get 'all' + 'medical' = 2 (not nursing)
    expect($requirements)->toHaveCount(2);
});
