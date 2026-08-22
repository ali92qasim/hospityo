<?php

use App\Models\Account;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\InventoryTransaction;
use App\Models\Medicine;
use App\Models\MedicineCategory;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

function seedPharmacyPosAccounts(): void
{
    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '4400', 'name' => 'Pharmacy Revenue', 'type' => 'revenue', 'is_system' => true]);
}

beforeEach(function () {
    $tenant = new Tenant;
    $tenant->id = 1;
    $tenant->status = 'active';
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    seedPharmacyPosAccounts();

    $this->user = User::create([
        'name' => 'POS Pharmacist',
        'email' => 'pos-pharmacist@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('dispense pharmacy', 'web');
    $this->user->givePermissionTo('dispense pharmacy');

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'POS Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03005556677',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03005556678',
        'emergency_relation' => 'Spouse',
    ]);

    $department = Department::create(['name' => 'Medicine', 'code' => 'MED', 'status' => 'active']);

    $this->doctor = Doctor::create([
        'name' => 'Dr. POS',
        'doctor_no' => 'DOC-POS-001',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03005556679',
        'email' => 'dr-pos@example.com',
        'gender' => 'male',
        'experience_years' => 6,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);

    $tab = Unit::create([
        'name' => 'TAB/CAP',
        'abbreviation' => 'TAB',
        'conversion_factor' => 1,
        'type' => 'solid',
        'is_active' => true,
    ]);

    $category = MedicineCategory::create(['code' => 'TAB', 'name' => 'Tablets', 'is_active' => true]);

    $this->medicine = Medicine::create([
        'name' => 'POS Medicine',
        'sku' => 'POS-MED-001',
        'category_id' => $category->id,
        'base_unit_id' => $tab->id,
        'dispensing_unit_id' => $tab->id,
        'manage_stock' => true,
        'status' => 'active',
        'selling_price' => 50,
    ]);

    $this->batch = InventoryTransaction::create([
        'medicine_id' => $this->medicine->id,
        'type' => 'stock_in',
        'quantity' => 100,
        'remaining_quantity' => 100,
        'unit_cost' => 30,
        'total_cost' => 3000,
        'batch_no' => 'BATCH-POS-1',
        'expiry_date' => now()->addYear(),
        'created_by' => $this->user->id,
    ]);
});

it('allows pharmacist to access pos screen', function () {
    $this->get(route('pharmacy.pos.index'))->assertOk()->assertSee('POS Counter');
});

it('includes zero stock medicines in pos medicine search', function () {
    $this->batch->update(['remaining_quantity' => 0]);

    $response = $this->getJson(route('pharmacy.pos.medicines.search', ['q' => 'POS Medicine']));

    $response->assertOk();
    expect($response->json('results'))->toHaveCount(1)
        ->and($response->json('results.0.available_stock'))->toBe(0)
        ->and($response->json('results.0.manage_stock'))->toBeTrue();
});

it('lists only in house pending prescriptions on pos screen', function () {
    $pending = Prescription::create([
        'visit_id' => $this->visit->id,
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'in_house',
        'status' => 'pending',
        'prescribed_date' => now(),
        'total_amount' => 100,
    ]);

    $externalPatient = Patient::create([
        'name' => 'External Pharmacy Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03009998877',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03009998878',
        'emergency_relation' => 'Spouse',
    ]);

    Prescription::create([
        'visit_id' => $this->visit->id,
        'patient_id' => $externalPatient->id,
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'external',
        'status' => 'external',
        'prescribed_date' => now(),
        'total_amount' => 50,
    ]);

    $this->get(route('pharmacy.pos.index'))
        ->assertSee($pending->prescription_no);

    expect(Prescription::inHousePending()->count())->toBe(1);
});

it('checkout fulfills prescription creates bill and stock out', function () {
    $prescription = Prescription::create([
        'visit_id' => $this->visit->id,
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'in_house',
        'status' => 'pending',
        'prescribed_date' => now(),
        'total_amount' => 100,
    ]);

    PrescriptionItem::create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $this->medicine->id,
        'quantity' => 2,
        'unit_price' => 50,
        'total_price' => 100,
    ]);

    $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'prescription',
        'prescription_id' => $prescription->id,
        'patient_id' => $this->patient->id,
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ])->assertRedirect(route('bills.print', \App\Models\Bill::where('prescription_id', $prescription->id)->first()));

    expect($prescription->fresh()->status)->toBe('dispensed')
        ->and(\App\Models\Bill::where('prescription_id', $prescription->id)->exists())->toBeTrue()
        ->and(InventoryTransaction::where('type', 'stock_out')->count())->toBe(1)
        ->and($this->batch->fresh()->remaining_quantity)->toBe(98);
});

it('checkout creates walk in pharmacy bill without prescription', function () {
    $response = $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'walk_in',
        'patient_id' => $this->patient->id,
        'items' => [
            ['medicine_id' => $this->medicine->id, 'quantity' => 2, 'unit_price' => 50],
        ],
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ]);

    $bill = \App\Models\Bill::where('bill_type', 'pharmacy')->first();
    $response->assertRedirect(route('bills.print', $bill));

    expect($bill)->not->toBeNull()
        ->and($bill->prescription_id)->toBeNull()
        ->and($bill->billItems()->where('medicine_id', $this->medicine->id)->exists())->toBeTrue()
        ->and(InventoryTransaction::where('type', 'stock_out')->count())->toBe(1);
});

it('rolls back checkout when stock is insufficient', function () {
    $prescription = Prescription::create([
        'visit_id' => $this->visit->id,
        'patient_id' => $this->patient->id,
        'doctor_id' => $this->doctor->id,
        'fulfillment_type' => 'in_house',
        'status' => 'pending',
        'prescribed_date' => now(),
        'total_amount' => 5000,
    ]);

    PrescriptionItem::create([
        'prescription_id' => $prescription->id,
        'medicine_id' => $this->medicine->id,
        'quantity' => 500,
        'unit_price' => 10,
        'total_price' => 5000,
    ]);

    $this->from(route('pharmacy.pos.index'))
        ->post(route('pharmacy.pos.checkout'), [
            'mode' => 'prescription',
            'prescription_id' => $prescription->id,
            'patient_id' => $this->patient->id,
            'payment_amount' => 5000,
            'payment_method' => 'cash',
        ])
        ->assertRedirect(route('pharmacy.pos.index'));

    expect($prescription->fresh()->status)->toBe('pending')
        ->and(\App\Models\Bill::count())->toBe(0)
        ->and(InventoryTransaction::where('type', 'stock_out')->count())->toBe(0);
});

it('includes walk in pharmacy bill items in medicine sales report stats', function () {
    Permission::findOrCreate('view reports', 'web');
    $this->user->givePermissionTo('view reports');

    $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'walk_in',
        'patient_id' => $this->patient->id,
        'items' => [
            ['medicine_id' => $this->medicine->id, 'quantity' => 2, 'unit_price' => 50],
        ],
        'payment_amount' => 100,
        'payment_method' => 'cash',
    ])->assertRedirect();

    $response = $this->get(route('reports.medicine-sales', [
        'start_date' => today()->format('Y-m-d'),
        'end_date' => today()->format('Y-m-d'),
    ]));

    $response->assertOk();
    expect($response->viewData('stats')['pos_line_items'])->toBe(1)
        ->and($response->viewData('stats')['total_quantity'])->toBe(2);
});

it('checkout records credit sale without payment and redirects to print', function () {
    $response = $this->post(route('pharmacy.pos.checkout'), [
        'mode' => 'walk_in',
        'patient_id' => $this->patient->id,
        'items' => [
            ['medicine_id' => $this->medicine->id, 'quantity' => 2, 'unit_price' => 50],
        ],
        'payment_amount' => 0,
        'payment_method' => 'credit',
    ]);

    $bill = \App\Models\Bill::where('bill_type', 'pharmacy')->first();

    $response->assertRedirect(route('bills.print', $bill));

    expect($bill)->not->toBeNull()
        ->and($bill->status)->toBe('pending')
        ->and((float) $bill->paid_amount)->toBe(0.0)
        ->and((float) $bill->due_amount)->toBe(100.0)
        ->and($bill->payments()->count())->toBe(0);
});

it('rejects unsupported payment methods such as upi', function () {
    $this->from(route('pharmacy.pos.index'))
        ->post(route('pharmacy.pos.checkout'), [
            'mode' => 'walk_in',
            'patient_id' => $this->patient->id,
            'items' => [
                ['medicine_id' => $this->medicine->id, 'quantity' => 1, 'unit_price' => 50],
            ],
            'payment_amount' => 50,
            'payment_method' => 'upi',
        ])
        ->assertRedirect(route('pharmacy.pos.index'))
        ->assertSessionHasErrors('payment_method');
});
