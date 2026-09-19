<?php

use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorShareRate;
use App\Models\DoctorShareRule;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    $this->department = Department::create([
        'name' => 'Matrix Department',
        'code' => 'MATRIX',
        'status' => 'active',
    ]);

    $this->doctor = matrixDoctor($this->department, 'Primary');
    $this->actingAs(matrixUser([
        'view share rules',
        'create share rules',
        'edit share rules',
    ]));
});

function bindMatrixTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function matrixUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Matrix User',
        'email' => 'matrix-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo($permissions);

    return $user;
}

function matrixDoctor(Department $department, string $suffix): Doctor
{
    return Doctor::create([
        'name' => "Dr Matrix {$suffix}",
        'specialization' => 'General',
        'qualification' => 'MBBS',
        'phone' => '0300'.random_int(1000000, 9999999),
        'email' => 'matrix-doctor-'.uniqid().'@example.com',
        'gender' => 'male',
        'experience_years' => 5,
        'consultation_fee' => 1000,
        'shift_start' => '09:00:00',
        'shift_end' => '17:00:00',
        'status' => 'active',
        'department_id' => $department->id,
    ]);
}

it('stores only non-empty matrix cells', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits', 'pharmacy']);

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $this->doctor->id,
            'general' => '',
            'opd' => '70',
            'pharmacy' => '',
        ]],
    ])->assertRedirect(route('doctor-share.rates.index'));

    expect(DoctorShareRate::query()->get())->toHaveCount(1);
    $this->assertDatabaseHas('doctor_share_rates', [
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 70,
    ], 'tenant');
});

it('stores numeric zero as an explicit rate', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits']);

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $this->doctor->id,
            'opd' => '0.00',
        ]],
    ])->assertRedirect(route('doctor-share.rates.index'));

    $rate = DoctorShareRate::query()->sole();

    expect($rate->service_category)->toBe('opd')
        ->and($rate->percentage)->toBe('0.00');
});

it('accepts an empty browser matrix without deleting existing rates', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits']);
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 35,
    ]);

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => '',
    ])->assertRedirect(route('doctor-share.rates.index'));

    expect(DoctorShareRate::query()->sole()->percentage)->toBe('35.00');
});

it('rejects duplicate doctors without changing rates', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits']);
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 35,
    ]);

    $this->putJson(route('doctor-share.rates.sync'), [
        'doctors' => [
            ['doctor_id' => $this->doctor->id, 'opd' => 50],
            ['doctor_id' => $this->doctor->id, 'opd' => 60],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('doctors.0.doctor_id');

    expect(DoctorShareRate::query()->sole()->percentage)->toBe('35.00');
});

it('rejects a value for an unentitled category', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share']);

    $this->putJson(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $this->doctor->id,
            'lab' => 25,
        ]],
    ])->assertForbidden();

    expect(DoctorShareRate::query()->count())->toBe(0);
});

it('rejects a non-numeric value for an unentitled category before validation', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share']);

    $this->putJson(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $this->doctor->id,
            'lab' => 'abc',
        ]],
    ])->assertForbidden();

    expect(DoctorShareRate::query()->count())->toBe(0);
});

it('hydrates every existing category rate into the matrix form', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits', 'ipd', 'laboratory', 'imaging']);

    foreach ([
        'opd' => '70.00',
        'ipd' => '40.00',
        'lab' => '20.00',
        'imaging' => '20.00',
    ] as $category => $percentage) {
        DoctorShareRate::create([
            'doctor_id' => $this->doctor->id,
            'service_category' => $category,
            'percentage' => $percentage,
        ]);
    }

    $html = $this->get(route('doctor-share.rates.index'))
        ->assertOk()
        ->assertViewHas('rateRows', function ($rows) {
            $rates = $rows->first()['rates'] ?? [];

            return $rows->count() === 1
                && ($rates['opd'] ?? null) === '70.00'
                && ($rates['ipd'] ?? null) === '40.00'
                && ($rates['lab'] ?? null) === '20.00'
                && ($rates['imaging'] ?? null) === '20.00';
        })
        ->getContent();

    expect($html)
        ->toMatch('/name="doctors\[0\]\[opd\]"[^>]*value="70.00"/')
        ->toMatch('/name="doctors\[0\]\[ipd\]"[^>]*value="40.00"/')
        ->toMatch('/name="doctors\[0\]\[lab\]"[^>]*value="20.00"/')
        ->toMatch('/name="doctors\[0\]\[imaging\]"[^>]*value="20.00"/');
});

it('shows only entitled category columns and maps missing rates to null', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits']);
    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 45,
    ]);
    $doctorWithoutRates = matrixDoctor($this->department, 'No Rates');

    $this->get(route('doctor-share.rates.index'))
        ->assertOk()
        ->assertSee('General')
        ->assertSee('OPD')
        ->assertSee('name="doctors[0][opd]"', false)
        ->assertSee('data-category="opd"', false)
        ->assertDontSee('data-category="lab"', false)
        ->assertDontSee('Create Share Rule')
        ->assertSee('data-added-ids="['.$this->doctor->id.']"', false)
        ->assertSee('value="'.$doctorWithoutRates->id.'"', false)
        ->assertViewHas('categoryOptions', fn (array $options) => array_keys($options) === ['general', 'opd'])
        ->assertViewHas('doctors', fn ($doctors) => $doctors->contains('id', $doctorWithoutRates->id))
        ->assertViewHas('rateRows', function ($rows) {
            return $rows->count() === 1
                && $rows->first()['doctor_id'] === $this->doctor->id
                && $rows->first()['rates']['general'] === null
                && $rows->first()['rates']['opd'] === '45.00';
        });
});

it('removes the legacy share rule create screen', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits']);

    $this->get('/doctor-share/rules/create')->assertNotFound();
});

it('does not change another doctor\'s rates when one doctor is saved', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits', 'laboratory']);
    $otherDoctor = matrixDoctor($this->department, 'Other');

    DoctorShareRate::create([
        'doctor_id' => $this->doctor->id,
        'service_category' => 'opd',
        'percentage' => 70,
    ]);
    DoctorShareRate::create([
        'doctor_id' => $otherDoctor->id,
        'service_category' => 'lab',
        'percentage' => 20,
    ]);

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $this->doctor->id,
            'opd' => 55,
        ]],
    ])->assertRedirect(route('doctor-share.rates.index'));

    expect(DoctorShareRate::where('doctor_id', $this->doctor->id)->sole()->percentage)->toBe('55.00')
        ->and(DoctorShareRate::where('doctor_id', $otherDoctor->id)->get())
        ->toHaveCount(1)
        ->and(DoctorShareRate::where('doctor_id', $otherDoctor->id)->sole()->service_category)->toBe('lab')
        ->and(DoctorShareRate::where('doctor_id', $otherDoctor->id)->sole()->percentage)->toBe('20.00');
});

it('leaves doctor share history unchanged after sync', function () {
    bindMatrixTenant(['settings', 'settings.doctor-share', 'visits']);
    DoctorShareRule::create([
        'doctor_id' => $this->doctor->id,
        'share_type' => 'percentage',
        'share_value' => 40,
        'applies_to' => 'opd',
        'is_active' => true,
        'created_by' => auth()->id(),
    ]);
    $before = fingerprintDoctorShareHistory();

    $this->put(route('doctor-share.rates.sync'), [
        'doctors' => [[
            'doctor_id' => $this->doctor->id,
            'opd' => 25,
        ]],
    ])->assertRedirect(route('doctor-share.rates.index'));

    expect(fingerprintDoctorShareHistory())->toBe($before);
});
