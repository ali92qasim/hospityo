<?php

use App\Http\Requests\StoreVisitRequest;
use App\Models\Patient;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);

    $this->artisan('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
    ]);

    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')->andReturn(true);
    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);
});

it('still resolves visit.patient after the patient is soft-deleted', function () {
    $patient = Patient::create([
        'name' => 'Soft Delete Rel Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03001112233',
    ]);

    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'open',
        'doctor_id' => null,
    ]);

    $patient->delete();

    $reloaded = Visit::query()->findOrFail($visit->id);
    expect($reloaded->patient)->not->toBeNull()
        ->and($reloaded->patient->id)->toBe($patient->id)
        ->and($reloaded->patient->trashed())->toBeTrue()
        ->and($reloaded->patient->name)->toBe('Soft Delete Rel Patient');
});

it('rejects store-visit validation when patient_id points at a soft-deleted patient', function () {
    Permission::findOrCreate('create visits', 'web');

    $user = User::create([
        'name' => 'Soft Delete Validator',
        'email' => 'soft-del-val-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $user->givePermissionTo('create visits');

    $patient = Patient::create([
        'name' => 'Trashed For Exists',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03009998877',
    ]);
    $patient->delete();

    $this->actingAs($user);

    $request = StoreVisitRequest::create('/visits', 'POST', [
        'patient_id' => $patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now()->toDateTimeString(),
    ]);
    $request->setUserResolver(fn () => $user);

    $validator = Validator::make($request->all(), (new StoreVisitRequest)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('patient_id'))->toBeTrue();
});
