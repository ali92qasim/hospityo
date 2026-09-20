# OPD / IPD / Emergency HTTP Identity Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> Do **not** start execution until a human confirms this plan. Do **not** merge any branch into `main` until the user explicitly asks — and only after every task is implemented, tested, and free of known bugs.
>
> **New feature branch:** create `feat/http-identity-resolution` from current HEAD **before Task 1**. Do not work on `main`.
>
> **Highest-risk work in this program.** Every task must prove **OPD and Emergency behavior is unchanged** (before/after or paired regression cases), not only that new IPD / test-orders cases pass.
>
> **Phase-boundary reports (override SDD “don’t pause” at these points only):**
> 1. After Task 1 (L4 fix-first: dead routes + duplicate imaging registration) — regression checkpoint before identity work.
> 2. After Task 2 (P1: `test-orders.*` → owning visit type).
> 3. After Task 3 (P2: admit / triage `assertVisitType`).
> 4. After Task 4 (P4: sidebar Admitted Patients → `ipd`-only).
> 5. After Task 5 (P5: IPD-list CheckModule feature coverage — only gaps left).
> 6. After Task 6 (full regression). **No merge.**

**Goal:** Make visit-type identity reliable for CheckModule and type-specific actions without renaming any `visits.*` / `test-orders.*` routes: resolve `test-orders.*` from the owning Visit, guard admit/triage by spine type, align IPD sidebar with Emergency’s single-module pattern, and close the IPD-list CheckModule feature-test gap — after a separate L4 dead-route cleanup.

**Architecture:** Keep shared route names (L1). Extend `ModuleRegistry::visitTypeFromRequest` so a bound `TestOrder` contributes `visit.visit_type` before query/body (P1 Option A), aborting **403** when the bound TestOrder/Visit cannot yield a whitelisted type (Q1/Q4 fail-closed). Add controller-level `VisitTypeGuard::assert` (**403**) on admit/triage (P2). Relax SidebarService Admitted Patients to `ipd` + Spatie `view visits` only (P4 Option B). L4 removes dead registrations first; CTI ceremonial bridge stays untouched (L5).

**Tech Stack:** Laravel 12, Pest, Spatie Permission / multitenancy, existing `CheckModule`, `ModuleRegistry`, `SidebarService`, `VisitController`.

**Spec:** `docs/superpowers/specs/2026-09-20-opd-ipd-emergency-http-identity-design.md` (confirmed). Investigation (do not re-open): `docs/superpowers/specs/2026-09-20-opd-ipd-emergency-http-identity-investigation.md`.

## Global Constraints

- **L1 — No route renaming.** Do not rename, alias, or path-split any of the ~34 `visits.*` or 2 `test-orders.*` names. URIs stay as registered (except removals in Task 1).
- **L5 — No CTI bridge edits.** Do not touch `dual_write_legacy_columns`, `read_from_child`, `VisitClassHistory`, `VisitTypeDetailSyncService::syncLegacyToChild`, or `config/visits.php` ceremonial flags.
- **Confirmed decisions (verbatim):** Q1 orphan/missing visit on bound `TestOrder` → **403 fail-closed**; Q2 admit/triage mismatch → **403**; Q3 quick-register stays **`opd|emergency` only**; Q4 whitelist query/input; bound Visit / TestOrder→visit type must be in `{opd,ipd,emergency}` else **403 fail-closed**. P1 = Option A; P4 = Option B.
- **Fail-closed vs default-OPD:** When a **bound** `Visit` or `TestOrder` is present, never fall through to query/body/`visits` default if type cannot be resolved. When **neither** is bound, keep today’s chain: whitelisted query/input, else `null` → module `visits`.
- **Do not change** `IpdClinicalService::ensureIpdVisit` (still `ValidationException` / 422 for care-team cluster). Admit/triage use the new **403** guard only.
- **P3 entry points:** No dedicated task. Preserve current index/create/data/quick-register contracts. Q4 hardening lives inside Task 2’s `visitTypeFromRequest`. Do not add IPD to quick-register.
- **Doctor Share / bill calculate:** out of scope.
- **Parent-route entitlement** (lab/imaging/pharmacy nested aborts): unchanged.
- Tests: `php artisan test --compact`. `Feature/Module`, `Feature/Visits`, `Feature/Navigation` already in `tests/Pest.php` tenant `in()` list — do not remove. Force-add `docs/superpowers/` (`git add -f`). Do not merge to `main`.
- **OPD/Emergency regression rule:** every phase report must list the OPD + Emergency commands run and PASS. Prefer reusing existing `CheckModuleTest` emergency/opd cases and `SidebarVisitNavigationTest` OPD/Emergency cases.

## Confirmed decisions (baked in)

| ID | Decision |
|---|---|
| P1 | Option A — resolve via bound `TestOrder→visit.visit_type` inside `visitTypeFromRequest` |
| P4 | Option B — sidebar Admitted Patients requires `ipd` only (+ `view visits`) |
| Q1 | Orphan / missing visit on bound TestOrder → **403** |
| Q2 | Admit/triage type mismatch → **403** |
| Q3 | Quick-register remains `opd\|emergency` only |
| Q4 | Query/input whitelist; invalid/missing bound DB type → **403** |
| L4 | Fix-first, separate risk envelope, **before** identity tasks |

## File map

**Create:**

- `app/Support/VisitTypeGuard.php`
- `tests/Feature/Module/VisitRouteHygieneTest.php` (Task 1)
- `tests/Feature/Module/TestOrderModuleResolutionTest.php` (Task 2 feature matrix)
- `tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php` (Task 3)

**Modify:**

- `routes/web.php` — Task 1 only: exclude `destroy` from visits resource; remove `visits.order-test`; delete duplicate imaging registration line
- `app/Models/ModuleRegistry.php` — `visitTypeFromRequest` / `moduleForRequest` (Task 2)
- `tests/Unit/ModuleRegistryTest.php` — TestOrder param + fail-closed unit cases (Task 2)
- `app/Http/Controllers/VisitController.php` — `admitPatient` / `triagePatient` call `VisitTypeGuard` (Task 3)
- `app/Services/SidebarService.php` — Admitted Patients gate (Task 4)
- `tests/Feature/Navigation/SidebarVisitNavigationTest.php` — IPD-only Admitted Patients (Task 4)
- `tests/Feature/Module/CheckModuleTest.php` — IPD list feature cases (Task 5)

**Do not modify:** CTI bridge files, `IpdClinicalService::ensureIpdVisit`, Doctor Share services, bill calculate paths, route names for remaining `visits.*` / `test-orders.*`.

**Shared types (lock now):**

```php
namespace App\Support;

use App\Models\Visit;

final class VisitTypeGuard
{
    /** @param 'ipd'|'emergency'|'opd' $expectedType */
    public static function assert(Visit $visit, string $expectedType): void
    {
        // abort(403, ...) when $visit->visit_type !== $expectedType
    }
}
```

Abort copy (verbatim):

- Admit mismatch: `This action requires an IPD visit.`
- Triage mismatch: `This action requires an Emergency visit.`
- Bound type unresolved (TestOrder orphan / invalid bound type): `Unable to resolve visit type for this request.`

`visitTypeFromRequest` resolution order (verbatim):

1. Bound route param `visit` (object with `visit_type`) → whitelist or **403**
2. Bound route param `testOrder` (`TestOrder` model or id→load) → `$testOrder->visit?->visit_type` → whitelist or **403** (do **not** fall through)
3. Query `visit_type` if in `{opd,ipd,emergency}`
4. Input `visit_type` if in `{opd,ipd,emergency}`
5. Else `null` → `moduleForRequest` default branch → `'visits'`

---

### Task 1: L4 fix-first — dead routes + duplicate imaging registration

**Files:**

- Create: `tests/Feature/Module/VisitRouteHygieneTest.php`
- Modify: `routes/web.php` (visits resource, `order-test` line ~299, duplicate imaging lines ~590–591)

**Interfaces:**

- Consumes: current `Route::resource('visits', …)`, `visits.order-test`, duplicate `visits.order-multiple-imaging-studies`.
- Produces: `Route::has('visits.destroy') === false`; `Route::has('visits.order-test') === false`; exactly one named registration for `visits.order-multiple-imaging-studies`; live replacements (`visits.add-test-orders`, imaging multi-order) unchanged.

**L4 decision (locked by inventory; re-confirm with `rg` in Step 1):**

| Registration | Callers in app/Blade/JS | Intended live path | Action |
|---|---|---|---|
| `visits.destroy` (from resource) | **None** (`rg` hits only resource registration); controller method **missing** | Visits are not deleted via HTTP today | **Remove** — `->except(['destroy'])` on the resource |
| `visits.order-test` | **None** outside `routes/web.php`; method **missing** | `visits.add-test-orders` (+ modern lab/imaging order routes) | **Remove** the route line |
| Duplicate `order-multiple-imaging-studies` | Same handler twice at lines 590–591 | Keep **one** registration | **Delete the duplicate line** |

Do **not** implement stub `destroy` / `orderTest` methods — there is no product behavior to restore.

- [ ] **Step 1: Create branch + re-confirm remove decision**

```bash
git checkout -b feat/http-identity-resolution
rg -n "visits\.destroy|visits\.order-test|orderTest|function destroy" app resources routes tests --glob '!vendor/**'
```

Expected: on `feat/http-identity-resolution`; no production callers of `visits.destroy` / `visits.order-test` / `orderTest` outside the broken route line; no `VisitController::destroy`. Proceed with **remove** (not implement).

- [ ] **Step 2: Write the failing hygiene tests**

`tests/Feature/Module/VisitRouteHygieneTest.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

it('does not register visits.destroy', function () {
    expect(Route::has('visits.destroy'))->toBeFalse();
});

it('does not register visits.order-test', function () {
    expect(Route::has('visits.order-test'))->toBeFalse();
});

it('registers visits.order-multiple-imaging-studies exactly once', function () {
    $matches = collect(Route::getRoutes())->filter(
        fn ($route) => $route->getName() === 'visits.order-multiple-imaging-studies'
    );

    expect($matches)->toHaveCount(1)
        ->and($matches->first()->methods())->toContain('POST');
});

it('still registers the live legacy and imaging order routes', function () {
    expect(Route::has('visits.add-test-orders'))->toBeTrue()
        ->and(Route::has('visits.order-multiple-lab-tests'))->toBeTrue()
        ->and(Route::has('visits.order-multiple-imaging-studies'))->toBeTrue()
        ->and(Route::has('test-orders.remove'))->toBeTrue()
        ->and(Route::has('test-orders.result'))->toBeTrue();
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Module/VisitRouteHygieneTest.php`

Expected: FAIL — `visits.destroy` and/or `visits.order-test` still registered; imaging name may already “has” but count is 2 (assert count 1 fails).

- [ ] **Step 4: Minimal route cleanup**

In `routes/web.php`:

1. Change the visits resource to exclude destroy:

```php
Route::resource('visits', VisitController::class)
    ->except(['destroy'])
    ->middleware('permission:view visits|create visits|edit visits|delete visits');
```

2. **Delete** the entire `visits.order-test` line (~299).

3. **Delete one** of the two identical `order-multiple-imaging-studies` lines (~590–591); keep a single registration.

- [ ] **Step 5: Run hygiene tests + OPD/Emergency regression**

```bash
php artisan test --compact tests/Feature/Module/VisitRouteHygieneTest.php
php artisan test --compact tests/Feature/Module/CheckModuleTest.php
php artisan test --compact tests/Feature/Navigation/SidebarVisitNavigationTest.php
php artisan test --compact tests/Feature/Visits/VisitInvestigationOrderTablesTest.php
```

Expected: all PASS. CheckModule OPD/Emergency list cases green; sidebar OPD/Emergency unchanged; investigation order tables still hit live routes.

- [ ] **Step 6: Commit + Phase 1 report**

```bash
git add routes/web.php tests/Feature/Module/VisitRouteHygieneTest.php
git add -f docs/superpowers/plans/2026-09-20-opd-ipd-emergency-http-identity.md
git commit -m "fix: remove dead visit routes and duplicate imaging registration"
```

**Phase 1 report (required):** L4 decision (remove), test commands + results, confirmation OPD/Emergency CheckModule + sidebar still pass. **Stop for user acknowledgment before Task 2.**

---

### Task 2: P1 — `test-orders.*` resolve owning visit type (Option A + Q1/Q4)

**Files:**

- Modify: `app/Models/ModuleRegistry.php` (`visitTypeFromRequest`, possibly a private helper)
- Modify: `tests/Unit/ModuleRegistryTest.php`
- Create: `tests/Feature/Module/TestOrderModuleResolutionTest.php`

**Interfaces:**

- Consumes: bound route param `testOrder` (`App\Models\TestOrder`), `$testOrder->visit`, existing Visit-param + query/input chain.
- Produces: `moduleForRequest` for `test-orders.*` returns `ipd` / `emergency` / `visits` from owning visit; orphan or invalid bound type **aborts 403** with `Unable to resolve visit type for this request.`; OPD-owned orders still map to `visits`.

- [ ] **Step 1: Write failing unit tests**

Append to `tests/Unit/ModuleRegistryTest.php`:

```php
use App\Models\TestOrder;
use App\Models\Visit;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('resolves test-orders module from the bound TestOrder visit type', function () {
    $visit = new Visit(['visit_type' => 'ipd']);
    $order = new TestOrder();
    $order->setRelation('visit', $visit);

    $request = Request::create('/test-orders/1', 'DELETE');
    $request->setRouteResolver(function () use ($order) {
        $route = new Route(['DELETE'], '/test-orders/{testOrder}', [
            'as' => 'test-orders.remove',
            'uses' => fn () => null,
        ]);
        $route->bind($request);
        $route->setParameter('testOrder', $order);

        return $route;
    });

    expect(ModuleRegistry::moduleForRequest($request))->toBe('ipd');
});

it('resolves emergency-owned test orders to the emergency module', function () {
    $visit = new Visit(['visit_type' => 'emergency']);
    $order = new TestOrder();
    $order->setRelation('visit', $visit);

    $request = Request::create('/test-orders/1/result', 'POST');
    $request->setRouteResolver(function () use ($order) {
        $route = new Route(['POST'], '/test-orders/{testOrder}/result', [
            'as' => 'test-orders.result',
            'uses' => fn () => null,
        ]);
        $route->bind($request);
        $route->setParameter('testOrder', $order);

        return $route;
    });

    expect(ModuleRegistry::moduleForRequest($request))->toBe('emergency');
});

it('aborts when a bound TestOrder has no visit', function () {
    $order = new TestOrder();
    $order->setRelation('visit', null);

    $request = Request::create('/test-orders/1', 'DELETE');
    $request->setRouteResolver(function () use ($order) {
        $route = new Route(['DELETE'], '/test-orders/{testOrder}', [
            'as' => 'test-orders.remove',
            'uses' => fn () => null,
        ]);
        $route->bind($request);
        $route->setParameter('testOrder', $order);

        return $route;
    });

    try {
        ModuleRegistry::moduleForRequest($request);
        expect(false)->toBeTrue(); // must not reach
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getMessage())->toBe('Unable to resolve visit type for this request.');
    }
});

it('aborts when a bound Visit has a non-whitelisted visit_type', function () {
    $visit = new Visit(['visit_type' => 'bogus']);

    $request = Request::create('/visits/1/workflow', 'GET');
    $request->setRouteResolver(function () use ($visit) {
        $route = new Route(['GET'], '/visits/{visit}/workflow', [
            'as' => 'visits.workflow',
            'uses' => fn () => null,
        ]);
        $route->bind($request);
        $route->setParameter('visit', $visit);

        return $route;
    });

    try {
        ModuleRegistry::moduleForRequest($request);
        expect(false)->toBeTrue();
    } catch (HttpException $e) {
        expect($e->getStatusCode())->toBe(403)
            ->and($e->getMessage())->toBe('Unable to resolve visit type for this request.');
    }
});

it('still maps query visit_type for visits.index without a bound model', function () {
    $named = fn (string $query) => tap(Request::create('/visits?'.$query, 'GET'), function (Request $request) {
        $request->setRouteResolver(fn () => new Route(['GET'], '/visits', [
            'as' => 'visits.index',
            'uses' => fn () => null,
        ]));
    });

    expect(ModuleRegistry::moduleForRequest($named('visit_type=opd')))->toBe('visits')
        ->and(ModuleRegistry::moduleForRequest($named('visit_type=emergency')))->toBe('emergency')
        ->and(ModuleRegistry::moduleForRequest($named('visit_type=ipd')))->toBe('ipd')
        ->and(ModuleRegistry::moduleForRequest($named('')))->toBe('visits');
});
```

- [ ] **Step 2: Write failing feature matrix**

`tests/Feature/Module/TestOrderModuleResolutionTest.php` — reuse `bindTenantWithModules` / `moduleGateUser` patterns from `CheckModuleTest.php` (copy the two helpers into this file or extract only if already shared; **prefer copy** to avoid drive-by Pest refactors).

```php
<?php

use App\Models\Patient;
use App\Models\Tenant;
use App\Models\TestOrder;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function bindTenantWithModulesForTestOrders(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function testOrderModuleUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Test Order Module User',
        'email' => 'tom-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

function makeVisitOwnedTestOrder(string $visitType): TestOrder
{
    $patient = Patient::create([
        'name' => 'TO Patient '.$visitType,
        'gender' => 'male',
        'age' => 40,
        'phone' => '0300'.random_int(1000000, 9999999),
    ]);

    $visit = Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => $visitType,
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);

    return TestOrder::create([
        'visit_id' => $visit->id,
        'test_name' => 'CBC',
        'quantity' => 1,
        'priority' => 'routine',
        'status' => 'ordered',
        'ordered_at' => now(),
    ]);
}

it('allows test-orders.result for an IPD-owned order when tenant has ipd only', function () {
    bindTenantWithModulesForTestOrders(['ipd']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('ipd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertRedirect();
});

it('allows test-orders.remove for an IPD-owned order when tenant has ipd only', function () {
    bindTenantWithModulesForTestOrders(['ipd']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('ipd');

    $this->delete(route('test-orders.remove', $order))
        ->assertRedirect();
});

it('forbids test-orders.result for an IPD-owned order when tenant has visits but not ipd', function () {
    bindTenantWithModulesForTestOrders(['visits']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('ipd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertForbidden();
});

it('allows test-orders.result for an OPD-owned order when tenant has visits only', function () {
    bindTenantWithModulesForTestOrders(['visits']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('opd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertRedirect();
});

it('forbids test-orders.result for an OPD-owned order when tenant lacks visits', function () {
    bindTenantWithModulesForTestOrders(['ipd']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('opd');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertForbidden();
});

it('allows test-orders.result for an emergency-owned order when tenant has emergency only', function () {
    bindTenantWithModulesForTestOrders(['emergency']);
    $this->actingAs(testOrderModuleUser(['edit visits']));
    $order = makeVisitOwnedTestOrder('emergency');

    $this->post(route('test-orders.result', $order), ['results' => 'Normal'])
        ->assertRedirect();
});

it('forbids orphaned test orders closed at CheckModule', function () {
    bindTenantWithModulesForTestOrders(['visits', 'ipd', 'emergency']);
    $this->actingAs(testOrderModuleUser(['edit visits']));

    $order = makeVisitOwnedTestOrder('opd');
    Schema::connection('tenant')->disableForeignKeyConstraints();
    $order->update(['visit_id' => 999999]);
    Schema::connection('tenant')->enableForeignKeyConstraints();

    $this->post(route('test-orders.result', $order->fresh()), ['results' => 'Normal'])
        ->assertForbidden();
});
```

- [ ] **Step 3: Run tests to verify they fail**

```bash
php artisan test --compact tests/Unit/ModuleRegistryTest.php --filter="test-orders|non-whitelisted|still maps query"
php artisan test --compact tests/Feature/Module/TestOrderModuleResolutionTest.php
```

Expected: FAIL — IPD-owned order with `ipd`-only still **403** (falls through to `visits`); orphan may incorrectly succeed or map to `visits`.

- [ ] **Step 4: Implement resolution chain**

In `app/Models/ModuleRegistry.php`, replace `visitTypeFromRequest` with logic matching the locked order:

```php
private static function visitTypeFromRequest(Request $request): ?string
{
    $route = $request->route();
    $whitelist = ['opd', 'ipd', 'emergency'];

    $visit = $route && $route->hasParameter('visit') ? $route->parameter('visit') : null;
    if (is_object($visit) && isset($visit->visit_type)) {
        $type = $visit->visit_type;
        if (is_string($type) && in_array($type, $whitelist, true)) {
            return $type;
        }
        abort(403, 'Unable to resolve visit type for this request.');
    }

    $testOrder = $route && $route->hasParameter('testOrder') ? $route->parameter('testOrder') : null;
    if ($testOrder !== null) {
        if (is_numeric($testOrder)) {
            $testOrder = \App\Models\TestOrder::query()->find($testOrder);
        }
        if (! is_object($testOrder)) {
            abort(403, 'Unable to resolve visit type for this request.');
        }
        $ownedVisit = $testOrder->visit ?? null;
        $type = is_object($ownedVisit) ? ($ownedVisit->visit_type ?? null) : null;
        if (is_string($type) && in_array($type, $whitelist, true)) {
            return $type;
        }
        abort(403, 'Unable to resolve visit type for this request.');
    }

    $type = $request->query('visit_type') ?? $request->input('visit_type');
    if (is_string($type) && in_array($type, $whitelist, true)) {
        return $type;
    }

    return null;
}
```

Notes:

- Prefer already-bound model; only `find` if the parameter is still a raw id.
- Load `visit` via relation (`$testOrder->visit`) — do not trust request `visit_type` when `testOrder` is bound.
- `moduleForRequest` `match` stays unchanged (`emergency` / `ipd` / default `visits`).

- [ ] **Step 5: Run new tests + OPD/Emergency regression**

```bash
php artisan test --compact tests/Unit/ModuleRegistryTest.php
php artisan test --compact tests/Feature/Module/TestOrderModuleResolutionTest.php
php artisan test --compact tests/Feature/Module/CheckModuleTest.php
```

Expected: all PASS — including existing emergency allow/deny and OPD allow cases in `CheckModuleTest`.

- [ ] **Step 6: Commit + Phase 2 report**

```bash
git add app/Models/ModuleRegistry.php tests/Unit/ModuleRegistryTest.php tests/Feature/Module/TestOrderModuleResolutionTest.php
git commit -m "fix: resolve test-orders module from owning visit type"
```

**Phase 2 report:** matrix results; explicit note that OPD-owned + Emergency-owned cases and `CheckModuleTest` OPD/Emergency list still PASS. **Stop.**

---

### Task 3: P2 — admit / triage `VisitTypeGuard` (403)

**Files:**

- Create: `app/Support/VisitTypeGuard.php`
- Create: `tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php`
- Modify: `app/Http/Controllers/VisitController.php` (`admitPatient`, `triagePatient` only)

**Interfaces:**

- Consumes: bound `Visit`, expected type `'ipd'` / `'emergency'`.
- Produces: `VisitTypeGuard::assert(Visit $visit, string $expectedType): void` — **403** with locked messages; matching-type admit/triage still succeed (existing characterization paths stay green).

- [ ] **Step 1: Write failing guard tests**

`tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php`:

```php
<?php

use App\Models\Bed;
use App\Models\Department;
use App\Models\Patient;
use App\Models\User;
use App\Models\Visit;
use App\Models\Ward;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
        \App\Http\Middleware\CheckModule::class,
    ]);

    Permission::findOrCreate('edit visits', 'web');
    Permission::findOrCreate('view visits', 'web');

    $this->user = User::create([
        'name' => 'Admit Triage Guard User',
        'email' => 'atg-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
    $this->user->givePermissionTo(['edit visits', 'view visits']);
    $this->actingAs($this->user);

    $this->patient = Patient::create([
        'name' => 'Admit Triage Patient',
        'gender' => 'female',
        'age' => 28,
        'phone' => '03001110000',
    ]);
});

function makeTypedVisit(Patient $patient, string $type): Visit
{
    return Visit::create([
        'patient_id' => $patient->id,
        'visit_type' => $type,
        'visit_datetime' => now(),
        'status' => 'registered',
        'doctor_id' => null,
    ]);
}

function makeAvailableBed(): Bed
{
    $department = Department::create([
        'name' => 'Guard Med',
        'code' => 'GMED-'.uniqid(),
        'status' => 'active',
    ]);
    $ward = Ward::create([
        'name' => 'Guard Ward',
        'department_id' => $department->id,
        'capacity' => 4,
        'ward_type' => 'general',
        'status' => 'active',
    ]);

    return Bed::create([
        'ward_id' => $ward->id,
        'bed_number' => 'G-01',
        'bed_type' => 'general',
        'daily_rate' => 1000,
        'status' => 'available',
    ]);
}

it('rejects admit on an OPD visit with 403 and creates no admission', function () {
    $visit = makeTypedVisit($this->patient, 'opd');
    $bed = makeAvailableBed();

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
        'admission_notes' => 'should not admit',
    ])->assertForbidden();

    expect($visit->fresh()->admission)->toBeNull()
        ->and($visit->fresh()->status)->toBe('registered');
});

it('rejects admit on an Emergency visit with 403 and creates no admission', function () {
    $visit = makeTypedVisit($this->patient, 'emergency');
    $bed = makeAvailableBed();

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
    ])->assertForbidden();

    expect($visit->fresh()->admission)->toBeNull();
});

it('admits an IPD visit successfully', function () {
    $visit = makeTypedVisit($this->patient, 'ipd');
    $bed = makeAvailableBed();

    $this->post(route('visits.admit', $visit), [
        'bed_id' => $bed->id,
        'admission_notes' => 'ok',
    ])->assertRedirect();

    expect($visit->fresh()->status)->toBe('admitted')
        ->and($visit->fresh()->admission)->not->toBeNull();
});

it('rejects triage on an OPD visit with 403 and creates no triage row', function () {
    $visit = makeTypedVisit($this->patient, 'opd');

    $this->post(route('visits.triage', $visit), [
        'priority_level' => 'urgent',
        'chief_complaint' => 'pain',
    ])->assertForbidden();

    expect($visit->fresh()->triage)->toBeNull();
});

it('rejects triage on an IPD visit with 403 and creates no triage row', function () {
    $visit = makeTypedVisit($this->patient, 'ipd');

    $this->post(route('visits.triage', $visit), [
        'priority_level' => 'urgent',
        'chief_complaint' => 'pain',
    ])->assertForbidden();

    expect($visit->fresh()->triage)->toBeNull();
});

it('triages an Emergency visit successfully', function () {
    $visit = makeTypedVisit($this->patient, 'emergency');

    $this->post(route('visits.triage', $visit), [
        'priority_level' => 'urgent',
        'chief_complaint' => 'chest pain',
        'pain_scale' => 5,
    ])->assertRedirect();

    expect($visit->fresh()->triage)->not->toBeNull()
        ->and($visit->fresh()->status)->toBe('triaged');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php`

Expected: FAIL — OPD/Emergency admit currently succeed (or redirect without 403); OPD/IPD triage currently succeed.

- [ ] **Step 3: Implement guard + wire controller**

`app/Support/VisitTypeGuard.php`:

```php
<?php

namespace App\Support;

use App\Models\Visit;

final class VisitTypeGuard
{
    public static function assert(Visit $visit, string $expectedType): void
    {
        if ($visit->visit_type === $expectedType) {
            return;
        }

        $label = match ($expectedType) {
            'ipd' => 'IPD',
            'emergency' => 'Emergency',
            'opd' => 'OPD',
            default => $expectedType,
        };

        abort(403, "This action requires an {$label} visit.");
    }
}
```

At the **top** of `admitPatient` (before the try/DB work):

```php
VisitTypeGuard::assert($visit, 'ipd');
```

At the **top** of `triagePatient`:

```php
VisitTypeGuard::assert($visit, 'emergency');
```

Add `use App\Support\VisitTypeGuard;`.

- [ ] **Step 4: Run guard tests + OPD/Emergency / IPD characterization regression**

```bash
php artisan test --compact tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php
php artisan test --compact tests/Feature/Visits/VisitSchemaCharacterizationTest.php
php artisan test --compact tests/Feature/Visits/EmergencyWorkflowUiSeparationTest.php
php artisan test --compact tests/Feature/Visits/OpdWorkflowUiSeparationTest.php
```

Expected: all PASS. Characterization admit-on-IPD still green; Emergency/OPD workflow UI tests unaffected.

- [ ] **Step 5: Commit + Phase 3 report**

```bash
git add app/Support/VisitTypeGuard.php app/Http/Controllers/VisitController.php tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php
git commit -m "fix: reject admit and triage when visit type does not match"
```

**Phase 3 report:** wrong-type 403 proof; matching-type success; OPD/Emergency workflow suites PASS. **Stop.**

---

### Task 4: P4 — sidebar Admitted Patients → `ipd`-only (Option B)

**Files:**

- Modify: `app/Services/SidebarService.php` (~lines 84–88)
- Modify: `tests/Feature/Navigation/SidebarVisitNavigationTest.php`

**Interfaces:**

- Consumes: `$this->hasModule($tenant, 'ipd')`, `$user->can('view visits')`.
- Produces: Admitted Patients visible with modules `['ipd']` + `view visits`; **not** requiring `visits` on the plan. OPD link still needs `visits`; Emergency link still needs `emergency`.

- [ ] **Step 1: Update / add failing sidebar tests**

Replace the intent of `it('gates ipd visit links behind both ipd and visits modules', …)` and strengthen coverage:

```php
it('shows Admitted Patients when tenant has ipd only', function () {
    $user = makeSidebarUser(['view visits', 'view wards', 'view beds']);
    $tenant = makeTenantWithModules(['ipd']);

    $menu = $this->service->build($user, $tenant);
    $ipd = collect($menu)->firstWhere('label', 'IPD Management');

    expect($ipd)->not->toBeNull();
    $admitted = collect($ipd['items'])->firstWhere('label', 'Admitted Patients');
    expect($admitted)->not->toBeNull()
        ->and($admitted['route_params']['visit_type'])->toBe('ipd');
});

it('hides IPD Management visit list when tenant lacks ipd even if visits is present', function () {
    $user = makeSidebarUser();
    $tenant = makeTenantWithModules(['visits']);

    $menu = $this->service->build($user, $tenant);

    expect(collect($menu)->pluck('label'))->toContain('OPD')
        ->and(collect($menu)->pluck('label'))->not->toContain('IPD Management');
});

it('keeps OPD and Emergency sidebar gates unchanged', function () {
    $user = makeSidebarUser(['view visits']);

    $opdMenu = $this->service->build($user, makeTenantWithModules(['visits']));
    expect(collect($opdMenu)->pluck('label'))->toContain('OPD')
        ->and(collect($opdMenu)->pluck('label'))->not->toContain('Emergency');

    $erMenu = $this->service->build($user, makeTenantWithModules(['emergency']));
    expect(collect($erMenu)->pluck('label'))->toContain('Emergency')
        ->and(collect($erMenu)->pluck('label'))->not->toContain('OPD');
});
```

Remove or rewrite the old test titled `gates ipd visit links behind both ipd and visits modules` so it no longer expects dual-module gating for Admitted Patients. Keep `ipd management has one visit list child…` but change its tenant modules to `['ipd']` (and permissions) so it still finds IPD Management without `visits`.

- [ ] **Step 2: Run sidebar tests to verify fail**

Run: `php artisan test --compact tests/Feature/Navigation/SidebarVisitNavigationTest.php`

Expected: FAIL — Admitted Patients still hidden for `ipd`-only tenant.

- [ ] **Step 3: Relax SidebarService gate**

Change:

```php
// ── IPD Management (ipd module + visit list requires visits module too) ─
$ipdItems = [];
if ($this->hasModule($tenant, 'ipd') && $this->hasModule($tenant, 'visits') && $user->can('view visits')) {
    $ipdItems[] = $this->item('Admitted Patients', 'fa-procedures', 'visits.index', ['visits.index'], ['visit_type' => 'ipd'], 'ipd');
}
```

To:

```php
// ── IPD Management (visit list gated like Emergency: clinical module + view visits) ─
$ipdItems = [];
if ($this->hasModule($tenant, 'ipd') && $user->can('view visits')) {
    $ipdItems[] = $this->item('Admitted Patients', 'fa-procedures', 'visits.index', ['visits.index'], ['visit_type' => 'ipd'], 'ipd');
}
```

Wards/Beds gates stay `ipd`-only (unchanged).

- [ ] **Step 4: Run sidebar + OPD/Emergency regression**

```bash
php artisan test --compact tests/Feature/Navigation/SidebarVisitNavigationTest.php
php artisan test --compact tests/Feature/Module/CheckModuleTest.php
```

Expected: PASS. OPD/Emergency sidebar cases in the same file pass; CheckModule OPD/Emergency unchanged.

- [ ] **Step 5: Commit + Phase 4 report**

```bash
git add app/Services/SidebarService.php tests/Feature/Navigation/SidebarVisitNavigationTest.php
git commit -m "fix: gate Admitted Patients sidebar on ipd module only"
```

**Phase 4 report:** before/after module sets; OPD/Emergency sidebar proof. **Stop.**

---

### Task 5: P5 — IPD-list CheckModule feature coverage (gap fill only)

**Files:**

- Modify: `tests/Feature/Module/CheckModuleTest.php`

**Interfaces:**

- Consumes: existing `bindTenantWithModules` / `moduleGateUser` in that file; `visits.index?visit_type=ipd`.
- Produces: feature proof that IPD list allows `ipd`-only and denies `visits`-without-`ipd`; OPD list still needs `visits`.

**Coverage audit (do this before writing):**

| Required case | Already covered? |
|---|---|
| IPD-owned `test-orders.*` ipd-only allow / visits-without-ipd deny | Task 2 — **yes** |
| Sidebar Admitted Patients ipd-only | Task 4 — **yes** |
| `visits.index?visit_type=ipd` CheckModule allow with `ipd` only | **No** — add here |
| `visits.index?visit_type=ipd` deny with `visits` only | **No** — add here |
| OPD list still allows with `visits` / blocks without | Existing `CheckModuleTest` OPD case — **keep**; add explicit “blocks OPD list without visits even if ipd present” if missing |

- [ ] **Step 1: Write only the missing failing tests**

Append to `tests/Feature/Module/CheckModuleTest.php`:

```php
it('allows ipd visit list when tenant has the ipd module only', function () {
    bindTenantWithModules(['ipd']);
    $this->actingAs(moduleGateUser(['view visits']));

    $this->get(route('visits.index', ['visit_type' => 'ipd']))
        ->assertOk();
});

it('blocks ipd visit list when tenant has visits but not ipd', function () {
    bindTenantWithModules(['visits']);
    $this->actingAs(moduleGateUser(['view visits']));

    $this->get(route('visits.index', ['visit_type' => 'ipd']))
        ->assertForbidden();
});

it('blocks opd visit list when tenant has ipd but not visits', function () {
    bindTenantWithModules(['ipd']);
    $this->actingAs(moduleGateUser(['view visits']));

    $this->get(route('visits.index', ['visit_type' => 'opd']))
        ->assertForbidden();
});
```

Do **not** duplicate Task 2 test-orders cases here.

- [ ] **Step 2: Run to verify fail (if production already maps ipd query → allow, the allow case may already pass; the deny/OPD cases must be written to catch regressions)**

Run: `php artisan test --compact tests/Feature/Module/CheckModuleTest.php --filter="ipd visit list|opd visit list when tenant has ipd"`

Expected: at least the new assertions execute; if all three already PASS without code changes, that is acceptable **only after** confirming Task 2 did not silently change list behavior — document in the phase report that P5 was **coverage-only** with zero production diff.

- [ ] **Step 3: Production change**

**None expected.** IPD list mapping already exists in `moduleForRequest`. If a test fails unexpectedly, fix only the bug found — do not reopen P4 middleware dual-require.

- [ ] **Step 4: Full CheckModule + OPD/Emergency proof**

```bash
php artisan test --compact tests/Feature/Module/CheckModuleTest.php
php artisan test --compact tests/Feature/Module/TestOrderModuleResolutionTest.php
php artisan test --compact tests/Feature/Navigation/SidebarVisitNavigationTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit + Phase 5 report**

```bash
git add tests/Feature/Module/CheckModuleTest.php
git commit -m "test: cover IPD visit list CheckModule entitlement"
```

If no production files changed: commit tests only. **Phase 5 report:** audit table (what Task 2/4 already covered vs what was added); OPD/Emergency CheckModule still PASS. **Stop.**

---

### Task 6: Full regression — no merge

**Files:** none (verification only)

- [ ] **Step 1: Run the identity-focused suites**

```bash
php artisan test --compact tests/Feature/Module/VisitRouteHygieneTest.php
php artisan test --compact tests/Feature/Module/TestOrderModuleResolutionTest.php
php artisan test --compact tests/Feature/Module/CheckModuleTest.php
php artisan test --compact tests/Unit/ModuleRegistryTest.php
php artisan test --compact tests/Feature/Visits/AdmitTriageVisitTypeGuardTest.php
php artisan test --compact tests/Feature/Navigation/SidebarVisitNavigationTest.php
php artisan test --compact tests/Feature/Visits/VisitSchemaCharacterizationTest.php
php artisan test --compact tests/Feature/Visits/OpdWorkflowUiSeparationTest.php
php artisan test --compact tests/Feature/Visits/EmergencyWorkflowUiSeparationTest.php
php artisan test --compact tests/Feature/Visits/IpdClinicalFeaturesTest.php
php artisan test --compact tests/Feature/Visits/VisitInvestigationOrderTablesTest.php
```

Expected: all PASS.

- [ ] **Step 2: Broader module / visit regression**

```bash
php artisan test --compact tests/Feature/Module
php artisan test --compact tests/Feature/Visits
php artisan test --compact tests/Feature/Navigation
```

Expected: all PASS. If pre-existing failures appear that are unrelated (Auth/Vite class of known debt), document them with evidence they exist on `main` @ merge-base — do **not** “fix” them in this branch unless introduced here.

- [ ] **Step 3: Closing report (Phase 6) — no merge**

Report must include:

1. Branch name + commit list (Task 1–5).
2. L4 outcome (removed destroy + order-test; deduped imaging).
3. P1 matrix (IPD/OPD/Emergency/orphan).
4. P2 wrong-type / matching-type.
5. P4 sidebar ipd-only + OPD/Emergency unchanged.
6. P5 coverage audit.
7. Full command output summary; **known bugs remaining: none** (or listed with severity).
8. Explicit: **not merged**.

Do **not** `git merge`, `git push` to main, or open a PR unless the user asks after accepting the closing report.

---

## Spec coverage self-review

| Spec item | Task |
|---|---|
| L4 destroy / order-test / duplicate imaging | Task 1 |
| P1 Option A + test matrix + unit TestOrder | Task 2 |
| Q1 orphan 403 | Task 2 |
| Q4 bound invalid type 403; query whitelist | Task 2 |
| P2 admit/triage 403 guards + success paths | Task 3 |
| Q2 = 403 | Task 3 |
| P4 Option B sidebar | Task 4 |
| P5 IPD-list CheckModule feature | Task 5 |
| Q3 quick-register unchanged | Global constraint (no task); regression via existing ungated/quick-register tests if present — do not add IPD |
| P3 preserve list/create/data | Global constraint; Q4 via Task 2 |
| L1 no rename | Global + Task 1 only removes dead names |
| L5 no CTI bridge | Global |
| OPD/Emergency unaffected proof | Every phase report + Task 6 |
| No merge until clean | Task 6 + header |

## Placeholder scan

No TBD/TODO steps. Concrete test code, abort strings, file paths, and commands included.

## Type consistency

- `VisitTypeGuard::assert(Visit, string)` used only in Task 3.
- Abort message for unresolved type shared by Task 2 unit + feature.
- Admit/triage messages use `IPD` / `Emergency` labels from the guard match.
