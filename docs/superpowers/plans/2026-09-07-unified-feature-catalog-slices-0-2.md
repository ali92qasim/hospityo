# Unified Feature Catalog (Slices 0–2) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> Do **not** start Slice 3 (dashboard chrome), Visit CTI, Audit/rbac decoupling, Settings migration, or Accounting/Pharmacy/OT child SKUs.

**Goal:** Add catalog schema + `ModuleRegistry::allows()` with zero behavior change, route Backup through that gate identically to today, then make each of the 13 Reports items a separately entitled plan slug with per-route Spatie names.

**Architecture:** Grow `ModuleRegistry` (parent/children already exist for Settings). Keep `plans.modules` as a flat slug list. `planAllows()` is Layer 1 (exact slug in JSON, or `hasModule` true on trial/no-plan). `allows()` is Layer 1 plus Spatie any-of `PermissionRegistry::forModule`. CheckModule stays Layer 1 (`planAllows` only — do not call `allows()` there). Backup HTTP already runs through CheckModule via `backup.`; Slice 1 only swaps the Backup **sidebar** to `allows()`. `TenantModuleProvisioner::grant()` stays a flat slug list; granting `reports` never expands report children. C1 backfill appends all 13 child slugs onto plans that already list `reports`. Do not add a `permission` key on ModuleRegistry nodes — Spatie names stay in `PermissionRegistry`.

**Tech Stack:** Laravel 12, Pest, Spatie Permission, existing `CheckModule` tenant middleware, `SidebarService`, super-admin plan `_form.blade.php`.

**Spec:** `docs/superpowers/specs/2026-09-04-unified-feature-catalog-design.md`

## Global Constraints

- Slices 0, 1, and 2 only. No Slice 3 dashboard chrome. No OPD/IPD/Emergency visit_type routing changes.
- Do not migrate Settings off `SettingsSectionRegistry`. Do not change Settings tab composers.
- Do not implement Settings-style parent-implies-children in `planAllows()` this program (flag is stored for plan-form JS only).
- C1: backfill all 13 report child slugs onto every plan whose `modules` already contains `"reports"`.
- C2: plan JSON is what the hospital bought; Spatie is role access to purchased nodes. Layer 1 first.
- C3: no plan / trial still means `Tenant::hasModule` is true — do not tighten.
- C4: Audit/rbac dual sidebar check is out of this plan.
- C5: no Blade `@feature` and no JS catalog helper this pass.
- C6: keep Spatie `view reports` as Reports **group** visibility; children have their own `view reports.{child}` names.
- C7: one SKU `reports.investigations` covering both `reports.investigations` and `reports.lab-tests`.
- C8: Slice 1 (Backup) is required and must land before Slice 2.
- Additive provisioner: never `syncPermissions` from `TenantModuleProvisioner`. Do not revoke Spatie when a slug is removed from a plan.
- `ModuleRegistry::normalize()` must still only add missing **parents**, never expand a parent into children.
- Tests: Pest. `Feature/Module` already runs tenant migrations via `tests/Pest.php`.
- PHP 8.2: do not use `self::CONST` inside the `protected static array $modules` initializer.
- Force-add any new file under `docs/superpowers/` (`git add -f`); do not un-ignore `/docs`.

## Confirmed Part C (baked in)

| ID | Decision |
|---|---|
| C1 | Backfill 13 children onto every plan that has `reports` |
| C2 | As designed (plan = purchase, Spatie = role) |
| C3 | Trial/no-plan behavior unchanged |
| C4 | Deferred |
| C5 | No JS/@feature helper |
| C6 | Keep `view reports` for the group |
| C7 | One investigations SKU |
| C8 | Backup slice is mandatory before Reports |

## File map

**Create:**

- `tests/Feature/Module/ModuleRegistryAllowsTest.php`
- `tests/Feature/Module/BackupCatalogGateTest.php`
- `tests/Feature/Module/ReportCatalogEntitlementTest.php`
- `tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php` (HTTP/JS assertion on the plan form markup/script)
- `database/migrations/landlord/2026_09_07_000001_backfill_report_child_slugs_on_plans.php`

**Modify:**

- `app/Models/ModuleRegistry.php`
- `app/Http/Middleware/CheckModule.php`
- `app/Services/SidebarService.php`
- `app/Support/PermissionRegistry.php`
- `database/seeders/RolePermissionSeeder.php`
- `database/seeders/PlanSeeder.php`
- `app/Services/TenantModuleProvisioner.php` (explicit-grant child → Hospital Administrator, Task 7)
- `tests/Feature/Module/TenantModuleProvisionerTest.php` (add cases; do not weaken existing)
- `tests/Feature/Module/ParentRouteEntitlementTest.php` (Slice 2 only: investigation tests must include child slug + child permission)
- `tests/Unit/ModuleRegistryTest.php` (Slice 2: `all()` count)
- `routes/web.php` (reports group middleware)
- `resources/views/super-admin/plans/_form.blade.php`

**Do not modify:** visit_type mapping in `moduleForRequest`, Settings registry/controllers, dashboard Blade, `patients-index.js`.

**Thirteen report child slugs (C7: investigations is one):**

```
reports.daily-cash-register
reports.patient-visits
reports.revenue
reports.outstanding-bills
reports.investigations
reports.medicine-sales
reports.inventory-status
reports.expiry-report
reports.doctor-performance
reports.appointment-statistics
reports.ipd-report
reports.department-performance
reports.patient-demographics
```

Child Spatie name = `'view '.$slug` (example: `view reports.daily-cash-register`).

---

### Task 1: Slice 0 — schema fields on existing nodes

**Files:**

- Modify: `app/Models/ModuleRegistry.php`
- Test: `tests/Unit/ModuleRegistryTest.php` (extend; do not remove existing cases)

**Interfaces:**

- Consumes: current `$modules` array
- Produces: every node has `entitlement` (`plan` or `always`) and every **parent** has `child_access_requires_explicit_grant` (bool). Leaves omit the flag. Defaults: `entitlement` => `plan`; missing flag treated as `false`. `reports` parent must be set `true` even before children exist so Slice 2 JS can read it. No `always` node in this plan.

- [ ] **Step 1: Write the failing test**

Add to `tests/Unit/ModuleRegistryTest.php`:

```php
it('declares entitlement and child-access flags without changing slug sets', function () {
    expect(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(22);

    $reports = ModuleRegistry::definitions()['reports'];
    expect($reports['entitlement'] ?? 'plan')->toBe('plan')
        ->and($reports['child_access_requires_explicit_grant'] ?? false)->toBeTrue()
        ->and($reports['parent'] ?? null)->toBeNull();

    $backup = ModuleRegistry::definitions()['backup'];
    expect($backup['entitlement'] ?? 'plan')->toBe('plan')
        ->and($backup['child_access_requires_explicit_grant'] ?? false)->toBeFalse();

    $settingsChild = ModuleRegistry::definitions()['settings.hospital-info'];
    expect($settingsChild['entitlement'] ?? 'plan')->toBe('plan')
        ->and(array_key_exists('child_access_requires_explicit_grant', $settingsChild))->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/ModuleRegistryTest.php --filter="declares entitlement"`

Expected: FAIL — `child_access_requires_explicit_grant` missing on `reports`.

- [ ] **Step 3: Write minimal implementation**

On each **top-level** definition in `app/Models/ModuleRegistry.php` add:

```php
'entitlement' => 'plan',
'child_access_requires_explicit_grant' => false,
```

except `'reports'` which gets `'child_access_requires_explicit_grant' => true`.

On existing **child** nodes (`settings.hospital-info`, `settings.prescription-print`) add only `'entitlement' => 'plan'` (no child-access flag).

Do not add report children yet (that would fail the count 22 assertion).

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Unit/ModuleRegistryTest.php`

Expected: PASS (including existing mapping/normalize tests).

- [ ] **Step 5: Commit**

```bash
git add app/Models/ModuleRegistry.php tests/Unit/ModuleRegistryTest.php
git commit -m "feat: declare catalog entitlement flags on ModuleRegistry"
```

---

### Task 2: Slice 0 — `planAllows()` and `allows()`

**Files:**

- Modify: `app/Models/ModuleRegistry.php`
- Create: `tests/Feature/Module/ModuleRegistryAllowsTest.php`

**Interfaces:**

- Consumes: `Tenant::hasModule()`, `PermissionRegistry::forModule(string $module): array`
- Produces:
  - `ModuleRegistry::planAllows(?\App\Models\Tenant $tenant, string $slug): bool`
  - `ModuleRegistry::allows(?\App\Models\Tenant $tenant, ?\Illuminate\Contracts\Auth\Access\Authorizable $user, string $slug): bool`

`planAllows` Layer 1 only:

1. Unknown slug → `false`.
2. Node `entitlement === 'always'` → `true` (no such node yet).
3. `$tenant === null` → `true` (same as CheckModule skipping when no current tenant).
4. Else `$tenant->hasModule($slug)` — **exact JSON membership**. Do not imply children from parent. C3: `hasModule` already returns true when the tenant has no plan.

`allows`:

1. If `! planAllows($tenant, $slug)` → `false`.
2. If `parentOf($slug)` is non-null and `! planAllows($tenant, $parent)` → `false`.
3. If `$user === null` → `true` (Layer 1 only; CheckModule has no user).
4. `$names = PermissionRegistry::forModule($slug)`. If empty, return `true` (no Spatie names registered). If non-empty, return true if `$user->can($name)` for **any** name (Backup’s five names).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Module/ModuleRegistryAllowsTest.php` (Feature/Module so Pest runs tenant migrations; landlord sqlite `beforeEach` copied from `TenantModuleProvisionerTest`):

```php
<?php

use App\Models\ModuleRegistry;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'permission.testing' => true,
        'multitenancy.switch_tenant_tasks' => [],
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
});

function catalogTenant(array $modules): Tenant
{
    $plan = Plan::create([
        'slug' => 'cat-'.uniqid(),
        'name' => 'Catalog Plan',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => $modules,
        'is_active' => true,
    ]);

    return Tenant::create([
        'name' => 'Catalog Clinic',
        'slug' => 'catalog-'.uniqid(),
        'domain' => uniqid().'.test',
        'database' => ':memory:',
        'status' => 'active',
        'plan_id' => $plan->id,
    ]);
}

it('planAllows matches hasModule for top-level slugs including backup', function () {
    $tenant = catalogTenant(['backup', 'patients']);

    expect(ModuleRegistry::planAllows($tenant, 'backup'))->toBe($tenant->hasModule('backup'))
        ->and(ModuleRegistry::planAllows($tenant, 'patients'))->toBeTrue()
        ->and(ModuleRegistry::planAllows($tenant, 'pharmacy'))->toBeFalse()
        ->and(ModuleRegistry::planAllows(null, 'backup'))->toBeTrue();
});

it('planAllows is true for a tenant with no plan (trial)', function () {
    $tenant = Tenant::create([
        'name' => 'Trial Clinic',
        'slug' => 'trial-'.uniqid(),
        'domain' => uniqid().'.test',
        'database' => ':memory:',
        'status' => 'active',
        'plan_id' => null,
    ]);

    expect($tenant->hasModule('backup'))->toBeTrue()
        ->and(ModuleRegistry::planAllows($tenant, 'backup'))->toBeTrue();
});

it('allows requires a backup spatia permission when a user is present', function () {
    $tenant = catalogTenant(['backup']);
    $user = User::create([
        'name' => 'No Backup',
        'email' => 'nobackup-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    expect(ModuleRegistry::allows($tenant, $user, 'backup'))->toBeFalse();

    Permission::findOrCreate('view backup', 'web');
    $user->givePermissionTo('view backup');

    expect(ModuleRegistry::allows($tenant, $user, 'backup'))->toBeTrue()
        ->and(ModuleRegistry::allows($tenant, null, 'backup'))->toBeTrue();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module/ModuleRegistryAllowsTest.php`

Expected: FAIL — `planAllows` undefined.

- [ ] **Step 3: Write minimal implementation**

In `ModuleRegistry.php`:

```php
public static function planAllows(?Tenant $tenant, string $slug): bool
{
    $definition = static::$modules[$slug] ?? null;
    if ($definition === null) {
        return false;
    }
    if (($definition['entitlement'] ?? 'plan') === 'always') {
        return true;
    }
    if ($tenant === null) {
        return true;
    }

    return $tenant->hasModule($slug);
}

public static function allows(?Tenant $tenant, ?\Illuminate\Contracts\Auth\Access\Authorizable $user, string $slug): bool
{
    if (! static::planAllows($tenant, $slug)) {
        return false;
    }

    $parent = static::parentOf($slug);
    if ($parent !== null && ! static::planAllows($tenant, $parent)) {
        return false;
    }

    if ($user === null) {
        return true;
    }

    $names = \App\Support\PermissionRegistry::forModule($slug);
    if ($names === []) {
        return true;
    }

    foreach ($names as $name) {
        if ($user->can($name)) {
            return true;
        }
    }

    return false;
}
```

Add `use App\Models\Tenant;` is already the same class file namespace `App\Models`.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/Module/ModuleRegistryAllowsTest.php tests/Unit/ModuleRegistryTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/ModuleRegistry.php tests/Feature/Module/ModuleRegistryAllowsTest.php
git commit -m "feat: add ModuleRegistry planAllows and allows helpers"
```

---

### Task 3: Slice 0 — existing suite files still pass unmodified

**Files:** none (run only).

**Interfaces:** none.

- [ ] **Step 1: Run unmodified regression files**

Run:

```
php artisan test --compact tests/Feature/Module/ParentRouteEntitlementTest.php tests/Feature/Module/TenantModuleProvisionerTest.php
```

Expected: PASS. Do not edit those files in this task. If they fail, fix `planAllows`/`allows` only — they must not be wired into production yet except as unused methods.

- [ ] **Step 2: Commit** only if a production fix was required; otherwise no commit.

---

### Task 4: Slice 1 — CheckModule uses `planAllows`

**Files:**

- Modify: `app/Http/Middleware/CheckModule.php`
- Test: existing `ParentRouteEntitlementTest.php` (run, do not change)

**Interfaces:**

- Consumes: `ModuleRegistry::planAllows(Tenant $tenant, string $slug): bool`
- Produces: same 403 copy as today

Replace `$tenant->hasModule($module)` with `ModuleRegistry::planAllows($tenant, $module)` and the parent check similarly.

```php
if (! ModuleRegistry::planAllows($tenant, $module)) {
    abort(403, 'Your plan does not include the ' . ModuleRegistry::nameFor($module) . ' module. Please upgrade your plan.');
}

$parent = ModuleRegistry::parentOf($module);
if ($parent && ! ModuleRegistry::planAllows($tenant, $parent)) {
    abort(403, 'Your plan does not include the ' . ModuleRegistry::nameFor($parent) . ' module. Please upgrade your plan.');
}
```

- [ ] **Step 1: Write no new test yet.** This must be behavior-identical. Proof is the existing entitlement file.

- [ ] **Step 2: Implement the two-line substitution above.**

- [ ] **Step 3: Run**

```
php artisan test --compact tests/Feature/Module/ParentRouteEntitlementTest.php
```

Expected: PASS (file unmodified).

- [ ] **Step 4: Commit**

```bash
git add app/Http/Middleware/CheckModule.php
git commit -m "refactor: CheckModule uses ModuleRegistry::planAllows"
```

---

### Task 5: Slice 1 — Backup sidebar uses `allows()`

**Files:**

- Modify: `app/Services/SidebarService.php` (backup block only, ~lines 349–351)
- Create: `tests/Feature/Module/BackupCatalogGateTest.php`
- Test (run unmodified): `tests/Feature/Backup/BackupRestoreTest.php`

**Interfaces:**

- Consumes: `ModuleRegistry::allows(?Tenant, ?Authorizable, string): bool`
- Produces: Backup sidebar link iff `allows($tenant, $user, 'backup')`

Today:

```php
if ($this->hasModule($tenant, 'backup') && $user->canAny(['view backup', 'create backup', 'restore backup', 'delete backup', 'manage backup'])) {
```

Replace with:

```php
if (ModuleRegistry::allows($tenant, $user, 'backup')) {
```

Keep the same `link(...)` call.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Module/BackupCatalogGateTest.php`. Copy the landlord sqlite `beforeEach` from `tests/Feature/Backup/BackupRestoreTest.php` (plan `modules => ['backup']`, bind current tenant, create `$this->user`). Use **unique** tenant `slug`/`domain` (`backup-gate-`.uniqid()) so this file cannot collide with `BackupRestoreTest` on the shared tenant database. Public API is `SidebarService::build(Authenticatable $user, ?Tenant $tenant): array`. Backup is a top-level `link` with `'id' => 'backup'`.

```php
it('shows backup in the sidebar only when plan and spatia backup permission both pass', function () {
    $service = app(\App\Services\SidebarService::class);

    $withoutPerm = $service->build($this->user, $this->tenant);
    expect(collect($withoutPerm)->pluck('id'))->not->toContain('backup');

    \Spatie\Permission\Models\Permission::findOrCreate('view backup', 'web');
    $this->user->givePermissionTo('view backup');
    $this->user->unsetRelation('permissions');
    $this->user->unsetRelation('roles');

    $withPerm = $service->build($this->user, $this->tenant);
    expect(collect($withPerm)->pluck('id'))->toContain('backup');
});
```

This characterization already passes against today's `hasModule && canAny`. Keep it. After the swap it must still pass.

- [ ] **Step 2: Run the new test**

Run: `php artisan test --compact tests/Feature/Module/BackupCatalogGateTest.php`

Expected: PASS (characterization). Then swap the production `if` and re-run. If the swap breaks it, fix `allows()` Backup `canAny` semantics.

- [ ] **Step 3: Swap the backup `if` to `ModuleRegistry::allows($tenant, $user, 'backup')`.** Add `use App\Models\ModuleRegistry;` at the top of `SidebarService.php` if it is not already there.

- [ ] **Step 4: Run**

```
php artisan test --compact tests/Feature/Module/BackupCatalogGateTest.php tests/Feature/Backup/BackupRestoreTest.php
```

Expected: both PASS. `BackupRestoreTest` must be unmodified and still disable `CheckModule` on the HTTP create test as today.

- [ ] **Step 5: Commit**

```bash
git add app/Services/SidebarService.php tests/Feature/Module/BackupCatalogGateTest.php
git commit -m "feat: gate Backup sidebar through ModuleRegistry::allows"
```

---

### Task 6: Slice 2 — register 13 report child nodes

**Files:**

- Modify: `app/Models/ModuleRegistry.php` (`reports` children + 13 node arrays)
- Modify: `tests/Unit/ModuleRegistryTest.php` (`all()` count 22 → 35)

**Interfaces:**

- Consumes: Task 1 flags
- Produces: `ModuleRegistry::reportChildSlugs(): array` returning the 13 slugs in the File map order. `definitions()['reports']['children']` equals that list. `moduleForRoute('reports.daily-cash-register')` => `reports.daily-cash-register`. `moduleForRoute('reports.lab-tests')` and `moduleForRoute('reports.investigations')` => `reports.investigations`. `moduleForRoute('reports.revenue')` still not matching bare `reports.` longer than child — longest prefix: give each child `routes` of its full route name(s); keep parent `routes => ['reports.']` as fallback for any ungated `reports.*` leftover.

- [ ] **Step 1: Write failing tests** in `ModuleRegistryTest.php`:

```php
it('maps each operational report route to its child slug', function () {
    expect(ModuleRegistry::moduleForRoute('reports.daily-cash-register'))->toBe('reports.daily-cash-register')
        ->and(ModuleRegistry::moduleForRoute('reports.lab-tests'))->toBe('reports.investigations')
        ->and(ModuleRegistry::moduleForRoute('reports.investigations'))->toBe('reports.investigations')
        ->and(ModuleRegistry::parentOf('reports.revenue'))->toBe('reports')
        ->and(ModuleRegistry::topLevel())->toHaveCount(20)
        ->and(ModuleRegistry::all())->toHaveCount(35)
        ->and(ModuleRegistry::normalize(['reports.revenue']))->toEqualCanonicalizing(['reports.revenue', 'reports'])
        ->and(ModuleRegistry::normalize(['reports']))->toBe(['reports']);
});
```

Change the existing `registers 20 top-level modules` test from `all()->toHaveCount(22)` to `35` in the same edit as the implementation so you do not leave a contradictory pair. The new test above is the failing-first coverage for mapping.

- [ ] **Step 2: Run filter — FAIL** on mapping.

- [ ] **Step 3: Implementation**

Add this constant and helper (outside `$modules`):

```php
public const REPORT_CHILD_SLUGS = [
    'reports.daily-cash-register',
    'reports.patient-visits',
    'reports.revenue',
    'reports.outstanding-bills',
    'reports.investigations',
    'reports.medicine-sales',
    'reports.inventory-status',
    'reports.expiry-report',
    'reports.doctor-performance',
    'reports.appointment-statistics',
    'reports.ipd-report',
    'reports.department-performance',
    'reports.patient-demographics',
];

public static function reportChildSlugs(): array
{
    return self::REPORT_CHILD_SLUGS;
}
```

On the existing `reports` node, set `'children'` to that **same literal 13-element list** (do not write `'children' => self::REPORT_CHILD_SLUGS` inside `$modules` — PHP 8.2 rejects it). Keep parent `'routes' => ['reports.']`.

Add this assertion to the new test (or a sibling test in the same file):

```php
expect(ModuleRegistry::definitions()['reports']['children'])->toBe(ModuleRegistry::REPORT_CHILD_SLUGS);
```

Add these child nodes (copy `group`/`parent`/`entitlement` onto each; `routes` are exact names so they beat the parent prefix):

```php
'reports.daily-cash-register' => [
    'name' => 'Daily Cash Register',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Daily cash register report.',
    'routes' => ['reports.daily-cash-register'],
],
'reports.patient-visits' => [
    'name' => 'Patient Visits',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Patient visit report.',
    'routes' => ['reports.patient-visits'],
],
'reports.revenue' => [
    'name' => 'Revenue Report',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Revenue report.',
    'routes' => ['reports.revenue'],
],
'reports.outstanding-bills' => [
    'name' => 'Outstanding Bills',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Outstanding bills report.',
    'routes' => ['reports.outstanding-bills'],
],
'reports.investigations' => [
    'name' => 'Investigation Report',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Lab and imaging investigation report.',
    'routes' => ['reports.investigations', 'reports.lab-tests'],
],
'reports.medicine-sales' => [
    'name' => 'Medicine Sales',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Medicine sales report.',
    'routes' => ['reports.medicine-sales'],
],
'reports.inventory-status' => [
    'name' => 'Inventory Status',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Inventory status report.',
    'routes' => ['reports.inventory-status'],
],
'reports.expiry-report' => [
    'name' => 'Expiry Report',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Medicine expiry report.',
    'routes' => ['reports.expiry-report'],
],
'reports.doctor-performance' => [
    'name' => 'Doctor Performance',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Doctor performance report.',
    'routes' => ['reports.doctor-performance'],
],
'reports.appointment-statistics' => [
    'name' => 'Appointment Statistics',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Appointment statistics report.',
    'routes' => ['reports.appointment-statistics'],
],
'reports.ipd-report' => [
    'name' => 'IPD Report',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'IPD report.',
    'routes' => ['reports.ipd-report'],
],
'reports.department-performance' => [
    'name' => 'Department Performance',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Department performance report.',
    'routes' => ['reports.department-performance'],
],
'reports.patient-demographics' => [
    'name' => 'Patient Demographics',
    'group' => 'Admin',
    'parent' => 'reports',
    'entitlement' => 'plan',
    'description' => 'Patient demographics report.',
    'routes' => ['reports.patient-demographics'],
],
```

Sidebar labels must stay identical to today’s `SidebarService` item labels (listed in `name` above).

- [ ] **Step 4: Run** `php artisan test --compact tests/Unit/ModuleRegistryTest.php`

Expected: PASS. `normalize(['reports'])` must **not** grow into 13 children.

- [ ] **Step 5: Commit**

```bash
git add app/Models/ModuleRegistry.php tests/Unit/ModuleRegistryTest.php
git commit -m "feat: register thirteen report child catalog nodes"
```

---

### Task 7: Slice 2 — PermissionRegistry + seeder catalog (no free child perms)

**Files:**

- Modify: `app/Support/PermissionRegistry.php`
- Modify: `database/seeders/RolePermissionSeeder.php`
- Test: `tests/Feature/Module/TenantModuleProvisionerTest.php` (add cases)

**Interfaces:**

- Consumes: `ModuleRegistry::REPORT_CHILD_SLUGS`
- Produces: `PermissionRegistry::forModule('reports')` remains `['view reports']` only. `forModule('reports.revenue')` returns `['view reports.revenue']`. `RolePermissionSeeder::PERMISSIONS` includes each `view reports.{child}` so empty-catalog seed **creates** the rows. **Hospital Administrator `ROLE_PERMISSIONS` still contains `view reports` and must not contain any `view reports.*` child** (empty-seed trap: a full seeder must not unlock all 13 reports). Super Admin still receives `Permission::all()` after seed (C2: Layer 1 hides unpurchased reports). When `grant()` iterates an explicit-grant **child** slug, it `givePermissionTo` that child name on Super Admin **and** Hospital Administrator, bypassing the seeder allow-list for HA only.

- [ ] **Step 1: Write failing tests** at the bottom of `TenantModuleProvisionerTest.php`:

```php
it('does not grant report child permissions when only the reports module is granted', function () {
    $provisioner = app(\App\Services\TenantModuleProvisioner::class);
    $provisioner->grant($this->tenant, ['reports']);

    $this->tenant->makeCurrent();
    $ha = \App\Models\Role::where('name', 'Hospital Administrator')->first();

    expect(\App\Support\PermissionRegistry::forModule('reports'))->toBe(['view reports'])
        ->and($ha->hasPermissionTo('view reports'))->toBeTrue()
        ->and($ha->hasPermissionTo('view reports.daily-cash-register'))->toBeFalse()
        ->and(\App\Models\Permission::where('name', 'view reports.daily-cash-register')->exists())->toBeTrue();
});

it('grants a report child only when that slug is in the grant list', function () {
    app(\App\Services\TenantModuleProvisioner::class)->grant(
        $this->tenant,
        ['reports', 'reports.daily-cash-register'],
    );

    $this->tenant->makeCurrent();
    $ha = \App\Models\Role::where('name', 'Hospital Administrator')->first();
    $super = \App\Models\Role::where('name', 'Super Admin')->first();

    expect($ha->hasPermissionTo('view reports.daily-cash-register'))->toBeTrue()
        ->and($super->hasPermissionTo('view reports.daily-cash-register'))->toBeTrue()
        ->and($ha->hasPermissionTo('view reports.revenue'))->toBeFalse();
});
```

Leave the second test as written (HA **true** for the granted child). Do **not** put child names on HA’s seeder list.

- [ ] **Step 2: Run those two tests — FAIL**

Run: `php artisan test --compact tests/Feature/Module/TenantModuleProvisionerTest.php --filter="report child"`

Expected: FAIL — `view reports.daily-cash-register` is not a catalog permission / HA does not receive it.

- [ ] **Step 3: Implementation**

`PermissionRegistry`: keep `'reports' => ['groups' => ['reports' => ['view reports']]]`. Add one top-level key per child slug (all 13, labels matching ModuleRegistry `name`):

```php
'reports.daily-cash-register' => [
    'label' => 'Daily Cash Register',
    'groups' => [
        'reports' => ['view reports.daily-cash-register'],
    ],
],
```

`RolePermissionSeeder::PERMISSIONS`: append these 13 strings next to `'view reports'`:

```
view reports.daily-cash-register
view reports.patient-visits
view reports.revenue
view reports.outstanding-bills
view reports.investigations
view reports.medicine-sales
view reports.inventory-status
view reports.expiry-report
view reports.doctor-performance
view reports.appointment-statistics
view reports.ipd-report
view reports.department-performance
view reports.patient-demographics
```

Do **not** add those 13 names to `ROLE_PERMISSIONS['Hospital Administrator']` or any other default role except Super Admin (who already gets `Permission::all()`).

In `TenantModuleProvisioner::grant`, immediately after the Super Admin `givePermissionTo` block inside `foreach ($modules as $module) { foreach (PermissionRegistry::forModule($module) as $name) {`, add:

```php
$parentSlug = ModuleRegistry::parentOf($module);
$explicitGrantChild = $parentSlug !== null
    && ! empty(ModuleRegistry::definitions()[$parentSlug]['child_access_requires_explicit_grant']);

if ($explicitGrantChild) {
    $hospitalAdmin = Role::where('name', 'Hospital Administrator')->first();
    if ($hospitalAdmin && ! $this->roleHas($hospitalAdmin, $name)) {
        $granted++;
        if (! $dryRun) {
            $hospitalAdmin->givePermissionTo($name);
            $hospitalAdmin->unsetRelation('permissions');
        }
    }
}
```

Leave the existing `$roleLists` intersection loop unchanged. Do not grant report children to Doctor, Nurse, or other default roles.

- [ ] **Step 4: Run**

```
php artisan test --compact tests/Feature/Module/TenantModuleProvisionerTest.php
```

Expected: all previous six cases PASS plus the two new ones.

- [ ] **Step 5: Commit**

```bash
git add app/Support/PermissionRegistry.php database/seeders/RolePermissionSeeder.php app/Services/TenantModuleProvisioner.php tests/Feature/Module/TenantModuleProvisionerTest.php
git commit -m "feat: grant report children only when those slugs are requested"
```

---

### Task 8: Slice 2 — split reports route middleware

**Files:**

- Modify: `routes/web.php` (the `reports.` group ~855–870)
- Modify: `tests/Feature/Module/ParentRouteEntitlementTest.php` (investigation cases only)
- Create: `tests/Feature/Module/ReportCatalogEntitlementTest.php`

**Interfaces:**

- Consumes: child permission names from Task 7; `moduleForRoute` from Task 6
- Produces: each report route requires `permission:view {childSlug}` instead of a group-level `permission:view reports`. Keep `view reports` off the HTTP group (C6 is sidebar group, not route). CheckModule still Layer 1 via longest-match child slug.

Replace the single group middleware with per-route middleware. Example:

```php
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('daily-cash-register', [ReportController::class, 'dailyCashRegister'])
        ->middleware('permission:view reports.daily-cash-register')
        ->name('daily-cash-register');
    // ...
    Route::get('lab-tests', [ReportController::class, 'labTests'])
        ->middleware('permission:view reports.investigations')
        ->name('lab-tests');
    Route::get('investigations', [ReportController::class, 'labTests'])
        ->middleware('permission:view reports.investigations')
        ->name('investigations');
});
```

ParentRouteEntitlement investigation tests currently bind `['reports']` and `['view reports']`. After this task they 403 unless updated. Change **only** those four tests:

```php
bindEntitlementTenant(['reports', 'reports.investigations']);
$this->actingAs(entitlementUser(['view reports', 'view reports.investigations']));
```

The laboratory/imaging `test_type` assertions stay the same (`laboratory` / `imaging` still required for those query params).

- [ ] **Step 1: Write `ReportCatalogEntitlementTest.php`**

Copy `bindEntitlementTenant` and `entitlementUser` from `ParentRouteEntitlementTest.php` into this new file (do not move them to Pest.php). Copy the same `withoutMiddleware` `beforeEach` for `EnsureTenantActive` and `SetTenantTimezone`. Do **not** disable `CheckModule`.

```php
<?php

use App\Models\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->withoutMiddleware([
        \App\Http\Middleware\EnsureTenantActive::class,
        \App\Http\Middleware\SetTenantTimezone::class,
    ]);
});

function reportCatalogTenant(array $modules): Tenant
{
    $tenant = Mockery::mock(Tenant::class)->makePartial();
    $tenant->id = 1;
    $tenant->status = 'active';
    $tenant->shouldReceive('hasModule')
        ->andReturnUsing(fn (string $module) => in_array($module, $modules, true));

    app()->instance(config('multitenancy.current_tenant_container_key'), $tenant);

    return $tenant;
}

function reportCatalogUser(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::create([
        'name' => 'Report Catalog User',
        'email' => 'report-catalog-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo($permissions);

    return $user;
}

it('forbids a report route when the child slug is missing from the plan', function () {
    reportCatalogTenant(['reports']);
    $this->actingAs(reportCatalogUser(['view reports', 'view reports.daily-cash-register']));

    $this->get(route('reports.daily-cash-register'))->assertForbidden();
});

it('forbids a report route when the child spatia permission is missing', function () {
    reportCatalogTenant(['reports', 'reports.daily-cash-register']);
    $this->actingAs(reportCatalogUser(['view reports']));

    $this->get(route('reports.daily-cash-register'))->assertForbidden();
});

it('allows a report route when plan child slug and child permission are present', function () {
    reportCatalogTenant(['reports', 'reports.daily-cash-register']);
    $this->actingAs(reportCatalogUser(['view reports', 'view reports.daily-cash-register']));

    $this->get(route('reports.daily-cash-register'))->assertOk();
});
```

`dailyCashRegister` queries `Payment`/`Account`/`Bill` and renders empty collections — `assertOk()` is the expected success status.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module/ReportCatalogEntitlementTest.php`

Expected: FAIL — group middleware is still `permission:view reports`; CheckModule still resolves `reports.daily-cash-register` to slug `reports`.

- [ ] **Step 3: Split middleware + update ParentRoute investigation bind lists.**

Replace the reports group in `routes/web.php` with:

```php
Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('daily-cash-register', [ReportController::class, 'dailyCashRegister'])
        ->middleware('permission:view reports.daily-cash-register')
        ->name('daily-cash-register');
    Route::get('patient-visits', [ReportController::class, 'patientVisits'])
        ->middleware('permission:view reports.patient-visits')
        ->name('patient-visits');
    Route::get('revenue', [ReportController::class, 'revenue'])
        ->middleware('permission:view reports.revenue')
        ->name('revenue');
    Route::get('outstanding-bills', [ReportController::class, 'outstandingBills'])
        ->middleware('permission:view reports.outstanding-bills')
        ->name('outstanding-bills');
    Route::get('lab-tests', [ReportController::class, 'labTests'])
        ->middleware('permission:view reports.investigations')
        ->name('lab-tests');
    Route::get('investigations', [ReportController::class, 'labTests'])
        ->middleware('permission:view reports.investigations')
        ->name('investigations');
    Route::get('medicine-sales', [ReportController::class, 'medicineSales'])
        ->middleware('permission:view reports.medicine-sales')
        ->name('medicine-sales');
    Route::get('inventory-status', [ReportController::class, 'inventoryStatus'])
        ->middleware('permission:view reports.inventory-status')
        ->name('inventory-status');
    Route::get('expiry-report', [ReportController::class, 'expiryReport'])
        ->middleware('permission:view reports.expiry-report')
        ->name('expiry-report');
    Route::get('doctor-performance', [ReportController::class, 'doctorPerformance'])
        ->middleware('permission:view reports.doctor-performance')
        ->name('doctor-performance');
    Route::get('appointment-statistics', [ReportController::class, 'appointmentStatistics'])
        ->middleware('permission:view reports.appointment-statistics')
        ->name('appointment-statistics');
    Route::get('ipd-report', [ReportController::class, 'ipdReport'])
        ->middleware('permission:view reports.ipd-report')
        ->name('ipd-report');
    Route::get('department-performance', [ReportController::class, 'departmentPerformance'])
        ->middleware('permission:view reports.department-performance')
        ->name('department-performance');
    Route::get('patient-demographics', [ReportController::class, 'patientDemographics'])
        ->middleware('permission:view reports.patient-demographics')
        ->name('patient-demographics');
});
```

Keep `view reports` off this HTTP group.

Update the four investigation tests in `ParentRouteEntitlementTest.php` as specified in Interfaces (child slug + `view reports.investigations`). `allows investigation report test_type lab` already adds `laboratory` to the module list — keep that:

```php
bindEntitlementTenant(['reports', 'reports.investigations', 'laboratory']);
```

- [ ] **Step 4: Run**

```
php artisan test --compact tests/Feature/Module/ReportCatalogEntitlementTest.php tests/Feature/Module/ParentRouteEntitlementTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php tests/Feature/Module/ReportCatalogEntitlementTest.php tests/Feature/Module/ParentRouteEntitlementTest.php
git commit -m "feat: gate each report route with its child permission and plan slug"
```

---

### Task 9: Slice 2 — sidebar per-child Reports

**Files:**

- Modify: `app/Services/SidebarService.php` (Reports block ~267–285)
- Test: `tests/Feature/Module/ReportCatalogEntitlementTest.php` (add sidebar cases)

**Interfaces:**

- Consumes: `ModuleRegistry::allows($tenant, $user, $slug)`
- Produces: Reports group rendered only if `allows($tenant, $user, 'reports')` (C6: `view reports` + plan `reports`). Each child item only if `allows($tenant, $user, $childSlug)`. If the items array is empty, do not push the group.

Replace the hardcoded `$items = [ 13 item() ... ]` with this (add `use App\Models\ModuleRegistry;` at the top of `SidebarService.php`):

```php
if (ModuleRegistry::allows($tenant, $user, 'reports')) {
    $reportItemMap = [
        'reports.daily-cash-register' => ['Daily Cash Register', 'fa-cash-register', 'reports.daily-cash-register', ['reports.daily-cash-register']],
        'reports.patient-visits' => ['Patient Visits', 'fa-user-clock', 'reports.patient-visits', ['reports.patient-visits']],
        'reports.revenue' => ['Revenue Report', 'fa-chart-line', 'reports.revenue', ['reports.revenue']],
        'reports.outstanding-bills' => ['Outstanding Bills', 'fa-file-invoice-dollar', 'reports.outstanding-bills', ['reports.outstanding-bills']],
        'reports.investigations' => ['Investigation Report', 'fa-flask', 'reports.investigations', ['reports.investigations', 'reports.lab-tests']],
        'reports.medicine-sales' => ['Medicine Sales', 'fa-pills', 'reports.medicine-sales', ['reports.medicine-sales']],
        'reports.inventory-status' => ['Inventory Status', 'fa-boxes', 'reports.inventory-status', ['reports.inventory-status']],
        'reports.expiry-report' => ['Expiry Report', 'fa-calendar-times', 'reports.expiry-report', ['reports.expiry-report']],
        'reports.doctor-performance' => ['Doctor Performance', 'fa-user-md', 'reports.doctor-performance', ['reports.doctor-performance']],
        'reports.appointment-statistics' => ['Appointment Statistics', 'fa-calendar-alt', 'reports.appointment-statistics', ['reports.appointment-statistics']],
        'reports.ipd-report' => ['IPD Report', 'fa-procedures', 'reports.ipd-report', ['reports.ipd-report']],
        'reports.department-performance' => ['Department Performance', 'fa-building', 'reports.department-performance', ['reports.department-performance']],
        'reports.patient-demographics' => ['Patient Demographics', 'fa-chart-pie', 'reports.patient-demographics', ['reports.patient-demographics']],
    ];

    $items = [];
    foreach (ModuleRegistry::reportChildSlugs() as $slug) {
        if (! ModuleRegistry::allows($tenant, $user, $slug)) {
            continue;
        }
        [$label, $icon, $route, $patterns] = $reportItemMap[$slug];
        $items[] = $this->item($label, $icon, $route, $patterns);
    }

    if ($items !== []) {
        $menu[] = $this->group('reports', 'Reports', $items, ['reports.*']);
    }
}
```

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Module/ReportCatalogEntitlementTest.php` (reuse `reportCatalogTenant` / `reportCatalogUser` from Task 8):

```php
it('hides the reports group when the plan has reports but no child slugs', function () {
    $tenant = reportCatalogTenant(['reports']);
    $user = reportCatalogUser(['view reports']);

    $menu = app(\App\Services\SidebarService::class)->build($user, $tenant);

    expect(collect($menu)->firstWhere('id', 'reports'))->toBeNull();
});

it('shows only entitled report children in the sidebar', function () {
    $tenant = reportCatalogTenant(['reports', 'reports.revenue']);
    $user = reportCatalogUser(['view reports', 'view reports.revenue']);

    $group = collect(app(\App\Services\SidebarService::class)->build($user, $tenant))
        ->firstWhere('id', 'reports');

    expect($group)->not->toBeNull()
        ->and(collect($group['items'])->pluck('label')->all())->toBe(['Revenue Report']);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Module/ReportCatalogEntitlementTest.php --filter="report"`

Expected: FAIL — all 13 children still listed; group still shown for `view reports` alone.

- [ ] **Step 3: Replace the Reports `if` in `SidebarService.php` (~line 268) with the loop in this task’s Interfaces block. Add `use App\Models\ModuleRegistry;` if Task 5 did not already.**

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/Module/ReportCatalogEntitlementTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/SidebarService.php tests/Feature/Module/ReportCatalogEntitlementTest.php
git commit -m "feat: show report sidebar children only when allows() passes"
```

---

### Task 10: Slice 2 — plan form JS explicit-grant branch

**Files:**

- Modify: `resources/views/super-admin/plans/_form.blade.php`
- Create: `tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php`

**Interfaces:**

- Consumes: `child_access_requires_explicit_grant` on parent definitions
- Produces: parent checkbox `data-child-access-requires-explicit-grant="1"` when the flag is true. JS: if that data attr is true, parent tick sets `child.disabled = false` and does **not** set `child.checked = true`. If false, keep `child.checked = parentBox.checked` (Settings). Uncheck parent: disable and uncheck children in both modes.

Parent input (keep the existing `class` and `checked` attributes; only add the data attribute):

```blade
<input type="checkbox" name="modules[]" value="{{ $slug }}"
       data-module-parent="{{ $slug }}"
       data-child-access-requires-explicit-grant="{{ !empty($def['child_access_requires_explicit_grant']) ? '1' : '0' }}"
       class="rounded border-gray-300 text-medical-blue focus:ring-medical-blue mr-3 mt-0.5"
       {{ in_array($slug, $selectedModules) ? 'checked' : '' }}>
```

Script replacement for the parent `change` handler:

```javascript
parentBox.addEventListener('change', function () {
    const explicit = parentBox.getAttribute('data-child-access-requires-explicit-grant') === '1';
    children.forEach(function (child) {
        child.disabled = !parentBox.checked;
        if (!parentBox.checked) {
            child.checked = false;
        } else if (!explicit) {
            child.checked = true;
        }
    });
});
```

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php` using the same landlord sqlite `beforeEach` and SuperAdmin actingAs as `tests/Feature/SuperAdmin/PlanModuleFormTest.php` (including `PlanSeeder`).

```php
<?php

use App\Models\Plan;
use App\Models\SuperAdmin;
use Database\Seeders\PlanSeeder;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);

    $this->app['db']->purge('landlord');

    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->seed(PlanSeeder::class);

    $this->superAdmin = SuperAdmin::create([
        'name' => 'Super Admin',
        'email' => 'super-admin-explicit@example.com',
        'password' => bcrypt('password'),
    ]);
});

it('marks reports as explicit-grant and does not auto-check children in plan form js', function () {
    $plan = Plan::where('slug', 'enterprise')->first();

    $this->actingAs($this->superAdmin, 'super_admin')
        ->get(route('super-admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('data-child-access-requires-explicit-grant="1"', false)
        ->assertSee('data-child-access-requires-explicit-grant="0"', false)
        ->assertSee("const explicit = parentBox.getAttribute('data-child-access-requires-explicit-grant') === '1';", false)
        ->assertSee('data-module-child-of="reports"', false);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php`

Expected: FAIL — data attribute and `const explicit` are absent.

- [ ] **Step 3: Implement markup + JS.**

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/super-admin/plans/_form.blade.php tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php
git commit -m "feat: plan form does not auto-check explicit-grant children"
```

---

### Task 11: Slice 2 — C1 backfill + PlanSeeder

**Files:**

- Modify: `app/Models/ModuleRegistry.php` (`backfillReportChildren`)
- Create: `database/migrations/landlord/2026_09_07_000001_backfill_report_child_slugs_on_plans.php`
- Modify: `database/seeders/PlanSeeder.php` (professional + enterprise `modules` arrays)
- Test: `tests/Unit/ModuleRegistryTest.php` (backfill helper) and `tests/Feature/Module/ModuleRegistryAllowsTest.php` (persist round-trip)

**Interfaces:**

- Consumes: `REPORT_CHILD_SLUGS`
- Produces: `ModuleRegistry::backfillReportChildren(array $modules): array` — if `'reports'` not in list, return list unchanged; else `array_values(array_unique([...$modules, ...self::REPORT_CHILD_SLUGS]))`.

Migration (landlord connection):

```php
public function up(): void
{
    \App\Models\Plan::query()->each(function (\App\Models\Plan $plan) {
        $plan->modules = \App\Models\ModuleRegistry::backfillReportChildren($plan->modules ?? []);
        $plan->save();
    });
}

public function down(): void
{
    // no-op: do not strip purchased report slugs
}
```

Use `Plan` model (landlord connection) not a raw DB json decode unless tests require it.

PlanSeeder: after each `'reports'` entry in professional and enterprise `modules`, append `...ModuleRegistry::REPORT_CHILD_SLUGS` via `array_merge` (add `use App\Models\ModuleRegistry;`). Starter has no `reports` — leave it.

Example professional `modules`:

```php
'modules' => array_merge([
    'patients', 'doctors', 'departments', 'appointments', 'visits', 'emergency', 'billing',
    'pharmacy', 'laboratory', 'imaging', 'ipd', 'reports', 'rbac',
    'settings', 'settings.hospital-info', 'settings.prescription-print',
], ModuleRegistry::REPORT_CHILD_SLUGS),
```

Enterprise: same merge around its existing list that already contains `'reports'`.

- [ ] **Step 1: Write the failing test** in `tests/Unit/ModuleRegistryTest.php`:

```php
it('backfills all report children only when reports is already on the plan', function () {
    expect(ModuleRegistry::backfillReportChildren(['patients']))->toBe(['patients'])
        ->and(ModuleRegistry::backfillReportChildren(['reports']))
            ->toEqualCanonicalizing(array_merge(['reports'], ModuleRegistry::reportChildSlugs()))
        ->and(ModuleRegistry::backfillReportChildren(['reports', 'reports.revenue']))
            ->toContain('reports.revenue', 'reports.daily-cash-register')
            ->and(count(array_unique(ModuleRegistry::backfillReportChildren(['reports', 'reports.revenue']))))
            ->toBe(1 + count(ModuleRegistry::reportChildSlugs()));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Unit/ModuleRegistryTest.php --filter="backfills all report"`

Expected: FAIL — `backfillReportChildren` undefined.

- [ ] **Step 3: Implement helper, migration, PlanSeeder.**

```php
public static function backfillReportChildren(array $modules): array
{
    if (! in_array('reports', $modules, true)) {
        return array_values($modules);
    }

    return array_values(array_unique([...$modules, ...self::REPORT_CHILD_SLUGS]));
}
```

- [ ] **Step 4: Persist round-trip** in `tests/Feature/Module/ModuleRegistryAllowsTest.php` (landlord migrate already in that file’s `beforeEach`):

```php
it('persists backfilled report children on a plan row', function () {
    $plan = Plan::create([
        'slug' => 'backfill-'.uniqid(),
        'name' => 'Backfill Plan',
        'price' => 0,
        'billing_cycle' => 'monthly',
        'modules' => ['reports'],
        'is_active' => true,
    ]);

    $plan->modules = ModuleRegistry::backfillReportChildren($plan->modules);
    $plan->save();

    expect($plan->fresh()->modules)->toContain('reports.ipd-report', 'reports.daily-cash-register');
});
```

The landlord migration is the production caller of that helper. Do not assert that `Plan::create` after migrate auto-backfills — only existing rows are rewritten.

- [ ] **Step 5: Run** `php artisan test --compact tests/Unit/ModuleRegistryTest.php tests/Feature/Module/ModuleRegistryAllowsTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Models/ModuleRegistry.php database/migrations/landlord/2026_09_07_000001_backfill_report_child_slugs_on_plans.php database/seeders/PlanSeeder.php tests/Unit/ModuleRegistryTest.php tests/Feature/Module/ModuleRegistryAllowsTest.php
git commit -m "feat: backfill report child slugs onto plans that list reports"
```

---

### Task 12: Slice 2 — freeze provisioner + entitlement regressions

**Files:** none new.

- [ ] **Step 1: Run**

```
php artisan test --compact tests/Feature/Module/TenantModuleProvisionerTest.php tests/Feature/Module/ParentRouteEntitlementTest.php tests/Feature/Module/ReportCatalogEntitlementTest.php tests/Feature/Module/BackupCatalogGateTest.php tests/Feature/Backup/BackupRestoreTest.php tests/Unit/ModuleRegistryTest.php tests/Feature/Module/ModuleRegistryAllowsTest.php tests/Feature/SuperAdmin/PlanFormExplicitGrantTest.php
```

Expected: PASS.

- [ ] **Step 2: Commit** only if a fix was required.

---

## Out of this plan

Slice 3 dashboard chrome. Visit CTI. Audit/rbac. Settings `entitlement: always` / parent-implies-children in `planAllows`. Accounting/Pharmacy/OT child SKUs. `@feature` / JS catalog. Revoke-on-uncheck.

## Self-review (spec coverage)

| Spec item | Task |
|---|---|
| Schema flags | 1 |
| `allows()` / trial unchanged | 2 |
| ParentRoute + provisioner unmodified in Slice 0 | 3 |
| CheckModule Layer 1 | 4 |
| Backup sidebar + BackupRestoreTest | 5 |
| 13 nodes + investigations alias | 6 |
| forModule(reports) not expanding children; empty seed trap | 7 |
| Per-route permission + CheckModule child slug | 8 |
| Sidebar per child | 9 |
| Plan form JS | 10 |
| C1 backfill + PlanSeeder | 11 |
| Regression freeze | 12 |
| Slice 3 / C4 / C5 JS helper | excluded |

Do not begin execution until this plan is confirmed. After confirmation, choose:

1. **Subagent-Driven (recommended)** — fresh subagent per task, review between tasks (`superpowers:subagent-driven-development`).
2. **Inline Execution** — same session, `superpowers:executing-plans`, batch with checkpoints.

