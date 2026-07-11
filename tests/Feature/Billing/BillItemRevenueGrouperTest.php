<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Investigation;
use App\Models\Patient;
use App\Models\Service;
use App\Models\User;
use App\Models\Visit;
use App\Services\BillItemRevenueGrouper;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Report Tester',
        'email' => 'revenue-grouper@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $this->patient = Patient::create([
        'name' => 'Jane Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03001112222',
        'emergency_name' => 'John Patient',
        'emergency_phone' => '03003334444',
        'emergency_relation' => 'Spouse',
    ]);

    $this->department = Department::create([
        'name' => 'General Medicine',
        'code' => 'GM',
        'status' => 'active',
    ]);

    $this->doctor = Doctor::create([
        'user_id' => $this->user->id,
        'name' => 'Dr. Revenue Test',
        'doctor_no' => 'DOC-REV-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556666',
        'email' => 'doctor-rev@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $this->department->id,
    ]);

    $this->visit = Visit::create([
        'visit_no' => 'VIS-REV-001',
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'department_id' => $this->department->id,
        'visit_type' => 'opd',
        'status' => 'active',
        'visit_datetime' => now(),
    ]);

    $this->service = Service::create([
        'name' => 'Consultation',
        'code' => 'CONS-REV',
        'category' => 'consultation',
        'price' => 1000,
        'is_active' => true,
    ]);

    $this->investigation = Investigation::create([
        'code' => 'CBC-001',
        'name' => 'COMPLETE BLOOD COUNT(CBC)',
        'category' => 'hematology',
        'sample_type' => 'blood',
        'price' => 1000,
        'is_active' => true,
    ]);

    $this->investigationsByName = BillItemRevenueGrouper::investigationsByName();
});

it('groups linked services by service name', function () {
    $item = new BillItem([
        'service_id' => $this->service->id,
        'description' => 'Consultation',
        'item_category' => 'opd',
    ]);
    $item->setRelation('service', $this->service);

    expect(BillItemRevenueGrouper::groupLabel($item, $this->investigationsByName))->toBe('Consultation')
        ->and(BillItemRevenueGrouper::isInvestigation($item, $this->investigationsByName))->toBeFalse();
});

it('groups linked investigations by investigation name', function () {
    $item = new BillItem([
        'investigation_id' => $this->investigation->id,
        'description' => 'COMPLETE BLOOD COUNT(CBC)',
        'item_category' => 'investigation',
    ]);
    $item->setRelation('investigation', $this->investigation);

    expect(BillItemRevenueGrouper::groupLabel($item, $this->investigationsByName))->toBe('COMPLETE BLOOD COUNT(CBC)')
        ->and(BillItemRevenueGrouper::isInvestigation($item, $this->investigationsByName))->toBeTrue();
});

it('treats legacy unlinked lab lines as investigations when description matches', function () {
    $item = new BillItem([
        'description' => 'COMPLETE BLOOD COUNT(CBC)',
        'item_category' => 'opd',
    ]);

    expect(BillItemRevenueGrouper::groupLabel($item, $this->investigationsByName))->toBe('COMPLETE BLOOD COUNT(CBC)')
        ->and(BillItemRevenueGrouper::isInvestigation($item, $this->investigationsByName))->toBeTrue()
        ->and(BillItemRevenueGrouper::groupKey($item, $this->investigationsByName))->toBe('investigation:' . $this->investigation->id);
});

it('keeps non-investigation unlinked lines on their description', function () {
    $item = new BillItem([
        'description' => 'DR ALI SHAH VISIT FEES',
        'item_category' => 'ipd',
    ]);

    expect(BillItemRevenueGrouper::groupLabel($item, $this->investigationsByName))->toBe('DR ALI SHAH VISIT FEES')
        ->and(BillItemRevenueGrouper::isInvestigation($item, $this->investigationsByName))->toBeFalse();
});

it('does not produce an unknown group for investigation bill lines in revenue report', function () {
    $bill = Bill::create([
        'bill_number' => 'BILL-REV-001',
        'patient_id' => $this->patient->id,
        'visit_id' => $this->visit->id,
        'bill_type' => 'opd',
        'bill_date' => today(),
        'subtotal' => 3000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 3000,
        'paid_amount' => 3000,
        'due_amount' => 0,
        'status' => 'paid',
        'created_by' => $this->user->id,
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'investigation_id' => $this->investigation->id,
        'item_category' => 'investigation',
        'description' => 'COMPLETE BLOOD COUNT(CBC)',
        'quantity' => 2,
        'unit_price' => 1000,
        'total_price' => 2000,
    ]);

    BillItem::create([
        'bill_id' => $bill->id,
        'item_category' => 'opd',
        'description' => 'COMPLETE BLOOD COUNT(CBC)',
        'quantity' => 1,
        'unit_price' => 1000,
        'total_price' => 1000,
    ]);

    $items = BillItem::with(['service', 'investigation'])->get();
    $lookup = BillItemRevenueGrouper::investigationsByName();

    $groups = $items->groupBy(fn (BillItem $item) => BillItemRevenueGrouper::groupKey($item, $lookup))
        ->map(fn ($group) => BillItemRevenueGrouper::groupLabel($group->first(), $lookup));

    expect($groups->values()->all())->toBe(['COMPLETE BLOOD COUNT(CBC)'])
        ->and($groups->keys()->contains(fn ($key) => str_contains($key, 'unknown')))->toBeFalse();
});
