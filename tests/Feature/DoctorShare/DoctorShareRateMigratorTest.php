<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareAllocation;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\DoctorShareRule;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Services\DoctorShareRateMigrator;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Hassan Fixture User',
        'email' => 'hassan-share@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $department = Department::create([
        'name' => 'General Medicine',
        'code' => 'GM-HASSAN',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'user_id' => $this->user->id,
        'name' => 'Dr. Hassan',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'hassan@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->service = Service::create([
        'name' => 'Consultation',
        'code' => 'CONS-HASSAN',
        'category' => 'consultation',
        'price' => 1000,
        'is_active' => true,
    ]);

    $opdRule = DoctorShareRule::create([
        'doctor_id' => $this->doctor->id,
        'share_type' => 'percentage',
        'share_value' => 70,
        'applies_to' => 'opd',
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);
    $opdRule->services()->attach($this->service->id);

    foreach ([
        ['applies_to' => 'ipd', 'share_value' => 40],
        ['applies_to' => 'lab', 'share_value' => 20],
        ['applies_to' => 'imaging', 'share_value' => 20],
    ] as $rule) {
        DoctorShareRule::create([
            'doctor_id' => $this->doctor->id,
            'share_type' => 'percentage',
            'share_value' => $rule['share_value'],
            'applies_to' => $rule['applies_to'],
            'is_active' => true,
            'created_by' => $this->user->id,
        ]);
    }

    $patient = Patient::create([
        'name' => 'History Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112222',
        'emergency_name' => 'History Contact',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Brother',
    ]);

    $bill = Bill::create([
        'patient_id' => $patient->id,
        'bill_number' => 'BILL-HASSAN-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 1000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 1000,
        'paid_amount' => 1000,
        'due_amount' => 0,
        'status' => 'paid',
        'created_by' => $this->user->id,
    ]);

    $billItem = BillItem::create([
        'bill_id' => $bill->id,
        'service_id' => $this->service->id,
        'item_category' => 'opd',
        'description' => 'Consultation',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]);

    $shareItem = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $billItem->id,
        'doctor_id' => $this->doctor->id,
        'rule_id' => $opdRule->id,
        'rule_snapshot' => [
            'rule_id' => $opdRule->id,
            'share_type' => 'percentage',
            'share_value' => '70.00',
            'applies_to' => 'opd',
        ],
        'base_amount' => 1000,
        'share_amount' => 700,
        'status' => 'pending',
    ]);

    DoctorShareAllocation::create([
        'doctor_share_item_id' => $shareItem->id,
        'payment_id' => null,
        'bill_id' => $bill->id,
        'doctor_id' => $this->doctor->id,
        'amount' => 700,
        'type' => 'collection',
    ]);
});

it('copies hassan-shaped rules to rates and widens the catalog-item opd rule', function () {
    $inserted = DoctorShareRateMigrator::copyFromLegacyRules();

    expect($inserted)->toBe(4)
        ->and(DoctorShareRate::query()
            ->where('doctor_id', $this->doctor->id)
            ->orderBy('service_category')
            ->get(['service_category', 'percentage'])
            ->map(fn (DoctorShareRate $rate) => [
                'service_category' => $rate->service_category,
                'percentage' => $rate->percentage,
            ])
            ->all())->toBe([
                ['service_category' => 'imaging', 'percentage' => '20.00'],
                ['service_category' => 'ipd', 'percentage' => '40.00'],
                ['service_category' => 'lab', 'percentage' => '20.00'],
                ['service_category' => 'opd', 'percentage' => '70.00'],
            ])
        ->and(DoctorShareRule::query()->count())->toBe(4)
        ->and(DoctorShareRule::query()->where('applies_to', 'opd')->first()->services()->count())->toBe(1);
});

it('does not rewrite historical share rows when copying rates', function () {
    $before = fingerprintDoctorShareHistory();

    DoctorShareRateMigrator::copyFromLegacyRules();

    expect(fingerprintDoctorShareHistory())->toBe($before);
});

it('aborts when two legacy rules collapse to different percentages', function () {
    DoctorShareRule::create([
        'doctor_id' => $this->doctor->id,
        'share_type' => 'percentage',
        'share_value' => 50,
        'applies_to' => 'opd',
        'is_active' => true,
        'created_by' => $this->user->id,
    ]);
    $before = fingerprintDoctorShareHistory();

    try {
        DoctorShareRateMigrator::copyFromLegacyRules();
        $this->fail('Expected conflicting percentages to abort the copy.');
    } catch (RuntimeException $exception) {
        expect($exception->getMessage())
            ->toContain('70.00')
            ->toContain('50.00');
    }

    expect(fingerprintDoctorShareHistory())->toBe($before)
        ->and(DoctorShareRate::query()->count())->toBe(0);
});

it('is idempotent when rates already match', function () {
    $before = fingerprintDoctorShareHistory();

    expect(DoctorShareRateMigrator::copyFromLegacyRules())->toBe(4)
        ->and(DoctorShareRateMigrator::copyFromLegacyRules())->toBe(0)
        ->and(DoctorShareRate::query()->count())->toBe(4)
        ->and(fingerprintDoctorShareHistory())->toBe($before);
});
