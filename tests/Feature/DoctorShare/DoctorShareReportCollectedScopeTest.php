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
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        EnsureTenantActive::class,
        SetTenantTimezone::class,
    ]);
});

function bindDoctorShareReportTenant(): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => $module === 'doctor-share');

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function doctorShareReportUser(): User
{
    Permission::findOrCreate('view share reports', 'web');

    $user = User::create([
        'name' => 'Doctor Share Report User',
        'email' => 'doctor-share-report-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('view share reports');

    return $user;
}

it('scopes collected totals to share items in the report date range', function () {
    bindDoctorShareReportTenant();
    $user = doctorShareReportUser();
    $this->actingAs($user);

    $doctor = Doctor::create([
        'name' => 'Dr. Collected Scope',
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '03001234567',
        'email' => 'collected-scope@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);

    $patient = Patient::create([
        'name' => 'Collected Scope Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03007654321',
        'emergency_name' => 'Emergency Contact',
        'emergency_phone' => '03001111111',
        'emergency_relation' => 'Sibling',
    ]);

    $bill = Bill::create([
        'patient_id' => $patient->id,
        'bill_number' => 'BILL-COLLECTED-SCOPE',
        'bill_date' => today(),
        'bill_type' => 'opd',
        'subtotal' => 200,
        'total_amount' => 200,
        'due_amount' => 200,
        'status' => 'pending',
        'created_by' => $user->id,
    ]);

    $oldBillItem = BillItem::create([
        'bill_id' => $bill->id,
        'description' => 'Old service',
        'quantity' => 1,
        'unit_price' => 100,
        'total_price' => 100,
    ]);
    $newBillItem = BillItem::create([
        'bill_id' => $bill->id,
        'description' => 'New service',
        'quantity' => 1,
        'unit_price' => 100,
        'total_price' => 100,
    ]);

    $oldItem = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $oldBillItem->id,
        'doctor_id' => $doctor->id,
        'rule_snapshot' => [],
        'base_amount' => 100,
        'share_amount' => 100,
        'status' => 'pending',
    ]);
    $newItem = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $newBillItem->id,
        'doctor_id' => $doctor->id,
        'rule_snapshot' => [],
        'base_amount' => 100,
        'share_amount' => 5,
        'status' => 'pending',
    ]);

    DB::connection('tenant')->table('doctor_share_items')
        ->where('id', $oldItem->id)
        ->update([
            'created_at' => '2020-01-01 12:00:00',
            'updated_at' => '2020-01-01 12:00:00',
        ]);

    DoctorShareAllocation::create([
        'doctor_share_item_id' => $oldItem->id,
        'bill_id' => $bill->id,
        'doctor_id' => $doctor->id,
        'amount' => 100,
        'type' => 'collection',
    ]);
    DoctorShareAllocation::create([
        'doctor_share_item_id' => $newItem->id,
        'bill_id' => $bill->id,
        'doctor_id' => $doctor->id,
        'amount' => 5,
        'type' => 'collection',
    ]);

    $historyFingerprint = fingerprintDoctorShareHistory();
    $today = today()->toDateString();

    $response = $this->get(route('doctor-share.reports.index', [
        'date_from' => $today,
        'date_to' => $today,
    ]));

    $response->assertOk()
        ->assertViewHas('summary', function ($summary) use ($doctor) {
            $row = $summary->firstWhere('doctor_id', $doctor->id);

            return $row !== null && (float) $row->total_collected === 5.0;
        });

    expect(fingerprintDoctorShareHistory())->toBe($historyFingerprint);
});
