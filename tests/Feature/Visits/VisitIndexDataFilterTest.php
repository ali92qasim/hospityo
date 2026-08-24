<?php

use App\Models\EmergencyVisit;
use App\Models\IpdVisit;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config([
        'visits.require_typed_visit_routes' => true,
        'visits.dual_write_enabled' => false,
    ]);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('view visits', 'web');

    $this->user = User::create([
        'name' => 'Visit Data Filter User',
        'email' => 'visit-data-filter-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['view visits']);

    $this->patient = Patient::create([
        'name' => 'Filter Test Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112233',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03001112234',
        'emergency_relation' => 'Sibling',
    ]);
});

function datatablesParams(array $extra = []): array
{
    return array_merge([
        'draw' => 1,
        'start' => 0,
        'length' => 100,
        'search' => ['value' => ''],
    ], $extra);
}

function createTypedVisit(Patient $patient, string $visitType): Visit
{
    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => $visitType,
        'status' => 'registered',
        'visit_datetime' => now(),
    ]);

    match ($visitType) {
        'opd' => OpdVisit::create(['visit_id' => $visit->id, 'queue_priority' => 'medium']),
        'ipd' => IpdVisit::create(['visit_id' => $visit->id]),
        'emergency' => EmergencyVisit::create(['visit_id' => $visit->id]),
        default => null,
    };

    return $visit;
}

it('returns only opd visits when visit_type filter is opd', function () {
    createTypedVisit($this->patient, 'opd');
    createTypedVisit($this->patient, 'ipd');
    createTypedVisit($this->patient, 'emergency');

    $response = $this->actingAs($this->user)
        ->getJson(route('visits.data', datatablesParams(['visit_type' => 'opd'])));

    $response->assertOk();

    $visitTypes = collect($response->json('data'))->pluck('visit_type')->unique()->values()->all();

    expect($visitTypes)->toBe(['opd']);
});

it('returns empty data when visit_type is missing and typed routes are required', function () {
    createTypedVisit($this->patient, 'opd');
    createTypedVisit($this->patient, 'ipd');

    $response = $this->actingAs($this->user)
        ->getJson(route('visits.data', datatablesParams()));

    $response->assertOk()
        ->assertJsonPath('recordsTotal', 0)
        ->assertJsonPath('recordsFiltered', 0)
        ->assertJsonPath('data', []);
});

it('includes data-visit-type on typed visit index pages', function () {
    $this->actingAs($this->user)
        ->get(route('visits.index', ['visit_type' => 'ipd']))
        ->assertOk()
        ->assertSee('data-visit-type="ipd"', false);
});
