<?php

use App\Models\Consultation;
use App\Models\OpdVisit;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config(['visits.dual_write_enabled' => false]);

    $this->user = User::create([
        'name' => 'Consultation Append User',
        'email' => 'consult-append@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Permission::findOrCreate('edit visits', 'web');
    $this->user->givePermissionTo(['edit visits']);

    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Append Consult Patient',
        'gender' => 'male',
        'age' => 40,
        'phone' => '03007770001',
        'emergency_name' => 'Relative',
        'emergency_phone' => '03007770002',
        'emergency_relation' => 'Sibling',
    ]);

    $this->visit = Visit::create([
        'patient_id' => $this->patient->id,
        'visit_type' => 'opd',
        'visit_datetime' => now(),
        'status' => 'with_doctor',
    ]);

    OpdVisit::create(['visit_id' => $this->visit->id, 'queue_priority' => 'medium']);
});

it('creates first consultation as current', function () {
    $consultation = Consultation::recordForVisit($this->visit, [
        'provisional_diagnosis' => 'Initial diagnosis',
    ]);

    expect($consultation->is_current)->toBeTrue()
        ->and($consultation->superseded_at)->toBeNull()
        ->and($this->visit->fresh()->currentConsultation?->id)->toBe($consultation->id);
});

it('supersedes prior consultation on second update', function () {
    $first = Consultation::recordForVisit($this->visit, [
        'provisional_diagnosis' => 'First diagnosis',
    ]);

    $second = Consultation::recordForVisit($this->visit->fresh(), [
        'provisional_diagnosis' => 'Revised diagnosis',
    ]);

    $first->refresh();

    expect(Consultation::where('visit_id', $this->visit->id)->count())->toBe(2)
        ->and($first->is_current)->toBeFalse()
        ->and($first->superseded_at)->not->toBeNull()
        ->and($second->is_current)->toBeTrue()
        ->and($this->visit->fresh()->consultation?->provisional_diagnosis)->toBe('Revised diagnosis');
});

it('workflow consultation update uses append-only versioning', function () {
    $this->post(route('visits.consultation', $this->visit), [
        'provisional_diagnosis' => 'First pass',
        'treatment' => 'Rest',
    ])->assertRedirect()->assertSessionHas('success');

    $this->post(route('visits.consultation', $this->visit), [
        'provisional_diagnosis' => 'Second pass',
        'treatment' => 'Medication',
    ])->assertRedirect()->assertSessionHas('success');

    $rows = Consultation::where('visit_id', $this->visit->id)->orderBy('id')->get();

    expect($rows)->toHaveCount(2)
        ->and($rows[0]->is_current)->toBeFalse()
        ->and($rows[0]->provisional_diagnosis)->toBe('First pass')
        ->and($rows[1]->is_current)->toBeTrue()
        ->and($rows[1]->provisional_diagnosis)->toBe('Second pass');
});
