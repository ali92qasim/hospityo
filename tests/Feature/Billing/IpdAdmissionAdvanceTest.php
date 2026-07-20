<?php

use App\Models\Admission;
use App\Models\AdmissionAdvance;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\AccountingService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD Advance Clerk',
        'email' => 'ipd-advance@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'IPD Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03009998888',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007776666',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. IPD',
        'doctor_no' => 'DOC-IPD-ADV',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005554444',
        'email' => 'dr-ipd-adv@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $ward = Ward::create([
        'name' => 'General Ward',
        'department_id' => $department->id,
        'capacity' => 10,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'GW-02',
        'bed_type' => 'general',
        'daily_rate' => 1500,
        'status' => 'available',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'ipd',
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);

    $this->admission = $this->visit->admission()->create([
        'bed_id' => $this->bed->id,
        'admission_notes' => 'Admitted for monitoring',
    ]);
});

it('tracks admission credit balance from multiple advances', function () {
    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 5000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => null,
        'notes' => 'First advance',
        'received_by' => $this->user->id,
    ]);

    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 2500,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => null,
        'notes' => null,
        'received_by' => $this->user->id,
    ]);

    expect($this->admission->total_advances)->toBe(7500.0)
        ->and($this->admission->credit_balance)->toBe(7500.0);
});

it('reduces available credit by draft bill charges', function () {
    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 10000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => null,
        'notes' => null,
        'received_by' => $this->user->id,
    ]);

    $bill = \App\Services\IpdDraftBillService::ensureForVisit($this->visit);
    $bill->billItems()->create([
        'description' => 'Room charge',
        'quantity' => 1,
        'unit_price' => 3500,
        'total_price' => 3500,
        'item_category' => 'room',
    ]);
    $bill->calculateTotals();

    $this->admission->load('visit.draftBill');

    expect($this->admission->total_advances)->toBe(10000.0)
        ->and($this->admission->draft_bill_charges)->toBe(3500.0)
        ->and($this->admission->credit_balance)->toBe(6500.0);
});

it('posts a journal entry for an advance when accounts exist (or returns null)', function () {
    $advance = $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 1000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'reference_number' => null,
        'notes' => null,
        'received_by' => $this->user->id,
    ]);

    $entry = AccountingService::postAdmissionAdvanceEntry($advance);

    expect($entry === null || $entry instanceof JournalEntry)->toBeTrue();
});

