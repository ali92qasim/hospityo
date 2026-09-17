<?php

use App\Http\Middleware\EnsureTenantActive;
use App\Http\Middleware\SetTenantTimezone;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Doctor;
use App\Models\DoctorShareAllocation;
use App\Models\DoctorShareItem;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        EnsureTenantActive::class,
        SetTenantTimezone::class,
    ]);
});

function bindDoctorShareCollectedColumnTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, ['settings', 'settings.doctor-share'], true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function doctorShareCollectedColumnUser(): User
{
    Permission::findOrCreate('view share reports', 'web');

    $user = User::create([
        'name' => 'Doctor Share Collected Column User',
        'email' => 'doctor-share-collected-column-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('view share reports');

    return $user;
}

it('shows allocation totals in the report detail collected column', function () {
    bindDoctorShareCollectedColumnTenant();
    $user = doctorShareCollectedColumnUser();
    $this->actingAs($user);

    $doctor = Doctor::create([
        'name' => 'Dr. Detail Collected',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234567',
        'email' => 'detail-collected@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);

    $patient = Patient::create([
        'name' => 'Detail Collected Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03007654321',
        'emergency_name' => 'Emergency Contact',
        'emergency_phone' => '03001111111',
        'emergency_relation' => 'Sibling',
    ]);

    $bill = Bill::create([
        'patient_id' => $patient->id,
        'bill_number' => 'BILL-DETAIL-COLLECTED',
        'bill_date' => today(),
        'bill_type' => 'opd',
        'subtotal' => 100,
        'total_amount' => 100,
        'due_amount' => 100,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $billItem = BillItem::create([
        'bill_id' => $bill->id,
        'description' => 'Detail collected service',
        'quantity' => 1,
        'unit_price' => 100,
        'total_price' => 100,
    ]);

    $shareItem = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $billItem->id,
        'doctor_id' => $doctor->id,
        'rule_snapshot' => [],
        'base_amount' => 100,
        'share_amount' => 25,
        'status' => 'pending',
    ]);

    DoctorShareAllocation::create([
        'doctor_share_item_id' => $shareItem->id,
        'bill_id' => $bill->id,
        'doctor_id' => $doctor->id,
        'amount' => 12.50,
        'type' => 'collection',
    ]);

    $historyFingerprint = fingerprintDoctorShareHistory();
    $formattedAmount = currency_symbol().number_format(12.50, 2);

    $response = $this->get(route('doctor-share.reports.index'));

    $response->assertOk()
        ->assertSee($formattedAmount)
        ->assertViewHas('details', function ($details) {
            $item = $details->first();

            return $item !== null && (float) $item->allocations_sum_amount === 12.50;
        });

    expect(fingerprintDoctorShareHistory())->toBe($historyFingerprint);
});
