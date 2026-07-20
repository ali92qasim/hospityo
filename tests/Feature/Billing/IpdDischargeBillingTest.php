<?php

use App\Models\Admission;
use App\Models\Bed;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use App\Services\IpdDischargeBillingService;
use App\Services\IpdDraftBillService;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'IPD Discharge Clerk',
        'email' => 'ipd-discharge@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Discharge Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112222',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Brother',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED-D', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. Discharge',
        'doctor_no' => 'DOC-IPD-D',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'dr-discharge@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $ward = Ward::create([
        'name' => 'General Ward D',
        'department_id' => $department->id,
        'capacity' => 10,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    $this->bed = Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'GW-D1',
        'bed_type' => 'general',
        'daily_rate' => 1500,
        'status' => 'occupied',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'ipd',
        'status' => 'admitted',
        'visit_datetime' => now(),
    ]);

    $this->admission = $this->visit->admission()->create([
        'bed_id' => $this->bed->id,
        'admission_date' => now(),
        'admission_notes' => 'For discharge billing test',
        'status' => 'active',
    ]);

    $this->bill = IpdDraftBillService::ensureForVisit($this->visit);
});

it('finalizes draft bill, applies advances, and refunds leftover credit', function () {
    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 10000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $this->bill->billItems()->create([
        'description' => 'Room charges',
        'quantity' => 1,
        'unit_price' => 7000,
        'total_price' => 7000,
        'item_category' => 'room',
    ]);
    $this->bill->calculateTotals();

    $result = IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill']),
        'cash'
    );

    $bill = $result['bill']->fresh(['payments']);

    expect($bill->status)->toBe('paid')
        ->and($bill->isDraft())->toBeFalse()
        ->and((float) $bill->paid_amount)->toBe(7000.0)
        ->and((float) $bill->due_amount)->toBe(0.0)
        ->and($result['advances_applied'])->toBe(7000.0)
        ->and($result['refund_amount'])->toBe(3000.0)
        ->and($bill->payments()->where('payment_method', 'advance')->count())->toBe(1);

    $this->admission->refresh();
    expect((float) $this->admission->refund_amount)->toBe(3000.0)
        ->and($this->admission->refund_method)->toBe('cash')
        ->and($this->admission->refunded_at)->not->toBeNull();
});

it('leaves remaining due when advances are less than bill total', function () {
    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 2000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $this->bill->billItems()->create([
        'description' => 'Procedure',
        'quantity' => 1,
        'unit_price' => 5000,
        'total_price' => 5000,
        'item_category' => 'procedure',
    ]);
    $this->bill->calculateTotals();

    $result = IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill'])
    );

    expect($result['bill']->status)->toBe('partial')
        ->and($result['advances_applied'])->toBe(2000.0)
        ->and($result['refund_amount'])->toBe(0.0)
        ->and($result['amount_due'])->toBe(3000.0);
});

it('requires refund method when credit exceeds bill', function () {
    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 5000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $this->bill->billItems()->create([
        'description' => 'Consult',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
        'item_category' => 'consultation',
    ]);
    $this->bill->calculateTotals();

    IpdDischargeBillingService::finalizeForDischarge(
        $this->visit->fresh(['admission.advances', 'draftBill'])
    );
})->throws(\RuntimeException::class);

it('previews refund and due amounts correctly before discharge', function () {
    $this->admission->advances()->create([
        'patient_id' => $this->patient->id,
        'amount' => 8000,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);

    $this->bill->billItems()->create([
        'description' => 'Stay charges',
        'quantity' => 1,
        'unit_price' => 5000,
        'total_price' => 5000,
        'item_category' => 'room',
    ]);
    $this->bill->calculateTotals();

    $preview = IpdDischargeBillingService::preview($this->visit->fresh(['admission.advances', 'draftBill']));

    expect($preview['bill_total'])->toBe(5000.0)
        ->and($preview['total_advances'])->toBe(8000.0)
        ->and($preview['advances_applied'])->toBe(5000.0)
        ->and($preview['refund_amount'])->toBe(3000.0)
        ->and($preview['amount_due'])->toBe(0.0);
});
