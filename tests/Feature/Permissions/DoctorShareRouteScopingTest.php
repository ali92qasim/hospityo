<?php

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Doctor;
use App\Models\DoctorShareAllocation;
use App\Models\DoctorShareItem;
use App\Models\DoctorShareRate;
use App\Models\DoctorShareSettlement;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function dsScopeTenant(array $modules = ['settings', 'settings.doctor-share']): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function dsScopeUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Doctor Share Scope User',
        'email' => 'doctor-share-scope-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function dsScopeDoctor(string $suffix = 'Primary'): Doctor
{
    return Doctor::create([
        'name' => "Dr Scope {$suffix}",
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '0300'.random_int(1000000, 9999999),
        'email' => 'scope-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
    ]);
}

/**
 * One pending share item (created today) with a collection allocation,
 * i.e. eligible for a settlement run over today's date range.
 */
function dsScopePendingShareItem(User $creator): DoctorShareItem
{
    $doctor = dsScopeDoctor('Settlement');

    $patient = Patient::create([
        'name' => 'Scope Settlement Patient',
        'gender' => 'female',
        'age' => 30,
        'phone' => '03007654321',
        'emergency_name' => 'Emergency Contact',
        'emergency_phone' => '03001111111',
        'emergency_relation' => 'Sibling',
    ]);

    $bill = Bill::create([
        'patient_id' => $patient->id,
        'bill_number' => 'BILL-DS-SCOPE-'.uniqid(),
        'bill_date' => today(),
        'bill_type' => 'opd',
        'subtotal' => 100,
        'total_amount' => 100,
        'due_amount' => 100,
        'status' => 'pending',
        'created_by' => $creator->id,
    ]);

    $billItem = BillItem::create([
        'bill_id' => $bill->id,
        'description' => 'Scope settlement service',
        'quantity' => 1,
        'unit_price' => 100,
        'total_price' => 100,
    ]);

    $item = DoctorShareItem::create([
        'bill_id' => $bill->id,
        'bill_item_id' => $billItem->id,
        'doctor_id' => $doctor->id,
        'rule_snapshot' => [],
        'base_amount' => 100,
        'share_amount' => 25,
        'status' => 'pending',
    ]);

    DoctorShareAllocation::create([
        'doctor_share_item_id' => $item->id,
        'bill_id' => $bill->id,
        'doctor_id' => $doctor->id,
        'amount' => 25,
        'type' => 'collection',
    ]);

    return $item;
}

function dsScopeSettlement(User $creator): DoctorShareSettlement
{
    return DoctorShareSettlement::create([
        'doctor_id' => dsScopeDoctor('Settled')->id,
        'date_from' => today()->subDays(7)->toDateString(),
        'date_to' => today()->toDateString(),
        'item_count' => 0,
        'total_settled_amount' => 0,
        'created_by' => $creator->id,
    ]);
}

function dsScopeRateSnapshot(): array
{
    return DoctorShareRate::query()
        ->orderBy('id')
        ->get(['doctor_id', 'service_category', 'percentage'])
        ->map(fn ($rate) => [$rate->doctor_id, $rate->service_category, (string) $rate->percentage])
        ->all();
}

// ── Denied: a wrong-verb permission that passed before the fix → 403 ─────────

dataset('doctor share escalations', [
    'rates page via create share rules' => ['get', 'doctor-share.rates.index', ['create share rules']],
    'rates page via edit share rules' => ['get', 'doctor-share.rates.index', ['edit share rules']],
    'rules redirect via create share rules' => ['get', 'doctor-share.rules.index', ['create share rules']],
    'rates sync via create share rules' => ['put', 'doctor-share.rates.sync', ['create share rules']],
    'items via create share items' => ['get', 'doctor-share.items.index', ['create share items']],
    'items via delete share items' => ['get', 'doctor-share.items.index', ['delete share items']],
    'settlements via create settlements' => ['get', 'doctor-share.settlements.index', ['create settlements']],
    'settlements via approve settlements' => ['get', 'doctor-share.settlements.index', ['approve settlements']],
    'preview via create settlements' => ['get', 'doctor-share.settlements.preview', ['create settlements']],
    'store via create settlements' => ['post', 'doctor-share.settlements.store', ['create settlements']],
]);

it('denies a wrong-verb permission on each doctor share route', function (string $method, string $route, array $perms) {
    dsScopeTenant();
    $this->actingAs(dsScopeUser($perms));

    $this->{$method}(route($route), [])->assertForbidden();
})->with('doctor share escalations');

it('denies settlements show to a create settlements only user', function () {
    dsScopeTenant();
    $user = dsScopeUser(['create settlements']);
    $settlement = dsScopeSettlement($user);
    $this->actingAs($user);

    $this->get(route('doctor-share.settlements.show', $settlement))->assertForbidden();
});

it('allows settlements show with view settlements', function () {
    dsScopeTenant();
    $user = dsScopeUser(['view settlements']);
    $settlement = dsScopeSettlement($user);
    $this->actingAs($user);

    $this->get(route('doctor-share.settlements.show', $settlement))->assertOk();
});

// ── Allowed: the single correct permission (positive control) ────────────────

dataset('doctor share proper access', [
    'rates page via view share rules' => ['get', 'doctor-share.rates.index', ['view share rules']],
    'rules redirect via view share rules' => ['get', 'doctor-share.rules.index', ['view share rules']],
    'items via view share items' => ['get', 'doctor-share.items.index', ['view share items']],
    'settlements via view settlements' => ['get', 'doctor-share.settlements.index', ['view settlements']],
    'preview via approve settlements' => ['get', 'doctor-share.settlements.preview', ['approve settlements']],
    'reports via view share reports' => ['get', 'doctor-share.reports.index', ['view share reports']],
    'reports print via view share reports' => ['get', 'doctor-share.reports.print', ['view share reports']],
]);

it('allows the single correct permission on each doctor share route', function (string $method, string $route, array $perms) {
    dsScopeTenant();
    $this->actingAs(dsScopeUser($perms));

    $status = $this->{$method}(route($route))->status();

    expect($status)->not->toBe(403)->toBeIn([200, 302]);
})->with('doctor share proper access');

it('lets manage doctor shares reach every doctor share route (superset)', function () {
    dsScopeTenant();
    $user = dsScopeUser(['manage doctor shares']);
    $settlement = dsScopeSettlement($user);
    $this->actingAs($user);

    $requests = [
        ['get', route('doctor-share.rates.index')],
        ['get', route('doctor-share.rules.index')],
        ['get', route('doctor-share.items.index')],
        ['get', route('doctor-share.settlements.index')],
        ['get', route('doctor-share.settlements.preview')],
        ['get', route('doctor-share.settlements.show', $settlement)],
        ['get', route('doctor-share.reports.index')],
        ['get', route('doctor-share.reports.print')],
        ['put', route('doctor-share.rates.sync')],
        ['post', route('doctor-share.settlements.store')],
    ];

    foreach ($requests as [$method, $url]) {
        $status = $this->{$method}($url, [])->status();

        expect($status)->not->toBe(403, "{$method} {$url} was forbidden")->toBeIn([200, 302]);
    }
});

// ── Behaviour: rates.sync ────────────────────────────────────────────────────

it('rejects rates sync from a create share rules only user and leaves rates unchanged', function () {
    dsScopeTenant(['settings', 'settings.doctor-share', 'visits']);
    $doctor = dsScopeDoctor();
    DoctorShareRate::create([
        'doctor_id' => $doctor->id,
        'service_category' => 'opd',
        'percentage' => 35,
    ]);
    $before = dsScopeRateSnapshot();
    $this->actingAs(dsScopeUser(['create share rules']));

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $doctor->id,
            'general' => '10',
            'opd' => '80',
        ]],
    ])->assertForbidden();

    expect(DoctorShareRate::count())->toBe(1)
        ->and(dsScopeRateSnapshot())->toBe($before);
});

it('lets an edit share rules user replace rates via rates sync', function () {
    dsScopeTenant(['settings', 'settings.doctor-share', 'visits']);
    $doctor = dsScopeDoctor();
    DoctorShareRate::create([
        'doctor_id' => $doctor->id,
        'service_category' => 'opd',
        'percentage' => 35,
    ]);
    $this->actingAs(dsScopeUser(['edit share rules']));

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $doctor->id,
            'general' => '10',
            'opd' => '80',
        ]],
    ])->assertRedirect(route('doctor-share.rates.index'));

    $rates = DoctorShareRate::query()->orderBy('service_category')->get();

    expect($rates)->toHaveCount(2)
        ->and($rates->pluck('service_category')->all())->toBe(['general', 'opd'])
        ->and((float) $rates->firstWhere('service_category', 'opd')->percentage)->toBe(80.0)
        ->and((float) $rates->firstWhere('service_category', 'general')->percentage)->toBe(10.0);
});

// ── Behaviour: settlements.store ─────────────────────────────────────────────

it('rejects settlements store from a create settlements only user and leaves the item pending', function () {
    dsScopeTenant();
    $user = dsScopeUser(['create settlements']);
    $item = dsScopePendingShareItem($user);
    $this->actingAs($user);

    $this->post(route('doctor-share.settlements.store'), [
        'date_from' => today()->toDateString(),
        'date_to' => today()->toDateString(),
    ])->assertForbidden();

    expect(DoctorShareSettlement::count())->toBe(0)
        ->and($item->fresh()->status)->toBe('pending')
        ->and($item->fresh()->settlement_id)->toBeNull();
});

it('lets an approve settlements user run a settlement', function () {
    dsScopeTenant();
    $user = dsScopeUser(['approve settlements']);
    $item = dsScopePendingShareItem($user);
    $this->actingAs($user);

    $this->post(route('doctor-share.settlements.store'), [
        'date_from' => today()->toDateString(),
        'date_to' => today()->toDateString(),
    ])->assertRedirect(route('doctor-share.settlements.index'));

    $settlement = DoctorShareSettlement::query()->sole();

    expect($settlement->item_count)->toBe(1)
        ->and($item->fresh()->status)->toBe('settled')
        ->and($item->fresh()->settlement_id)->toBe($settlement->id);
});
