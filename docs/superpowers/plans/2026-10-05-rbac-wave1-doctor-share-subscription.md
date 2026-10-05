# RBAC Wave 1: Doctor Share + `manage subscription` Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply the following, per `docs/superpowers/specs/2026-10-04-rbac-wave1-doctor-share-subscription-design.md`:
- Scope all 10 Doctor Share routes to one permission per action (plus the `manage doctor shares` superset).
- Hide the action controls users can't use.
- Fix the settings-entry visibility rule.
- Introduce the `manage subscription` permission: registry, seeder and backfill, gating all 6 billing/subscription routes. This replaces the role-name sidebar check and gives non-admin staff on an expired trial a 402 page instead of a 403 dead end.

**Architecture:**
- Spatie `permission:` route middleware only. No controller-level logic and no role-name checks.
- One idempotent artisan backfill command, `rbac:backfill-manage-subscription`, modelled on `BackfillManagePacPermission`, grants the new permission to the Super Admin and Hospital Administrator roles in every tenant. Role names are used only to pick the backfill targets, never for access.
- `EnsureTenantActive` decides redirect-vs-402 with `$user->can('manage subscription')`.

**Tech Stack:** Laravel 12, Spatie laravel-permission, Spatie multitenancy, Blade, Pest.

**Spec:** `docs/superpowers/specs/2026-10-04-rbac-wave1-doctor-share-subscription-design.md`. Investigation: `docs/superpowers/specs/2026-10-04-rbac-wave1-doctor-share-subscription-investigation.md`.

## Confirmed decisions

| # | Decision |
|---|---|
| W1-1 | `manage doctor shares` is a superset, accepted alongside each route's own permission |
| W1-2 | `doctor-share.rates.sync` requires `edit share rules` |
| W1-3 | `doctor-share.settlements.store` requires `approve settlements` |
| W1-4 | `billing.*` and `subscription.*` pages require `manage subscription` (webhooks excluded) |
| D4 | `manage subscription` is backfilled to Super Admin and Hospital Administrator only; Spatie permission, no role-name checks |
| DS-1 | `doctor-share.settlements.preview` requires `approve settlements` |
| DS-2 | Expired trial: `manage subscription` holders are redirected to `subscription.index` as today. Everyone else gets `errors/trial-expired` with **HTTP 402**: "Subscribe Now" only for `manage subscription`, otherwise "Please ask your administrator to renew the subscription." Login/logout reachable, no redirect loop. |
| DS-3 | Wave 1 is built on `main` @ `e62fdec` (Track A merged) |

## Global Constraints

- **Worktree, not the main tree.** The main tree (`C:/Users/Qasim/Herd/saasy`) is on `fix/bug-fixes` with the user's uncommitted work and stashes. **Never** checkout, stash, add or commit there. All work happens in `C:/Users/Qasim/Herd/saasy-rbac-wave1` on branch `feat/rbac-wave1-doctor-share-subscription`, created from `e62fdec`.
- **TDD:** failing test first for every behaviour change. Run red and confirm it fails for the *right* reason (403-vs-200 style status mismatch, not a setup error), then implement, then green, then commit. At least one commit per task.
- **Before/after evidence per route (D6):** every tightened route needs a test that a wrong-verb permission holder is **denied** (fails before the fix) and a test that the correct permission holder still **succeeds** (positive control).
- **Spatie only:** `permission:` middleware, `@can` / `@canany`, `$user->can()`. No `hasRole` / `hasAnyRole` / role-name string checks in access paths. Any found in touched files are converted and listed in the final report.
- **No real tenant data writes during execution.** The backfill command gets a `--dry-run` flag. Running it for real against tenant databases happens **only after merge and with explicit human approval** (Task 8).
- **Full regression is chunked** (one Pest process per `tests/Feature/*` directory or file, plus `tests/Unit`), run sequentially, because a single full run was memory-killed before. The pass criterion: **no failures except the known 34-test baseline** (16 files: Auth ×18, ProfileTest ×5, MigrateCoarsePermissions ×3, Pharmacy MedicineImport/PharmacyPosNavigation/UnitImport, RevenueReport, Favicon, ExampleTest, PlanModuleForm, ConfirmDialogMarkup), compared **by test file and count**, not just by total.
- **Phase boundaries:** report to the human after each phase (format in each phase's last step). Execution runs continuously within a phase.
- **No merge, no push** until the final report is confirmed by the human.
- **Standing practice:** remove worktrees when done (`git worktree remove`, then `git worktree prune`).

## File Structure

| File | Responsibility |
|---|---|
| `routes/web.php` (Doctor Share block ~L834; billing block ~L277-287) | Per-action Doctor Share middleware; `manage subscription` on billing/subscription routes |
| `resources/views/admin/doctor-share/rates/index.blade.php` | Matrix inputs disabled and Save/Add/Remove hidden without `edit share rules\|manage doctor shares` |
| `resources/views/admin/doctor-share/settlements/index.blade.php` | "New Settlement" gated on `approve settlements\|manage doctor shares` |
| `resources/views/admin/doctor-share/settlements/preview.blade.php` | Confirm Settlement form gated on the same |
| `app/Support/SettingsAccess.php` | Non-bundled section `GET` counts only `view `/`manage `/`access ` permissions |
| `app/Support/PermissionRegistry.php` | `manage subscription` under the `settings` module, new `subscription` group |
| `database/seeders/RolePermissionSeeder.php` | `manage subscription` in the master list and in Hospital Administrator's defaults |
| `app/Console/Commands/BackfillManageSubscriptionPermission.php` | **Create.** `rbac:backfill-manage-subscription [--dry-run]` |
| `resources/views/partials/sidebar.blade.php` (~L66) | `hasAnyRole([...])` → `@can('manage subscription')` |
| `resources/views/admin/layout.blade.php` (~L47) | Trial banner "Upgrade Now" link gated |
| `app/Http/Middleware/EnsureTenantActive.php` (~L44-48) | DS-2: redirect only `manage subscription` holders; others get the 402 view |
| `resources/views/errors/trial-expired.blade.php` (~L27) | "Subscribe Now" gated; administrator message otherwise |
| `tests/Feature/Permissions/DoctorShareRouteScopingTest.php` | **Create.** Phase 1 tests |
| `tests/Feature/Billing/ManageSubscriptionPermissionTest.php` | **Create.** Phase 2 tests |
| `tests/Feature/Commands/BackfillManageSubscriptionPermissionTest.php` | **Create.** Backfill command tests |

---

## Phase 0: Worktree, branch, docs

### Task 0: Create the Wave 1 worktree

**Files:** none (git only)

- [ ] **Step 1: Create the worktree off updated main**

```bash
cd C:/Users/Qasim/Herd/saasy
git fetch origin
git rev-parse origin/main            # expect e62fdec0e147ece4a108e7cb6b15c61ba6ac295d
git worktree add -b feat/rbac-wave1-doctor-share-subscription ../saasy-rbac-wave1 origin/main
cp .env ../saasy-rbac-wave1/.env
cp -r vendor node_modules ../saasy-rbac-wave1/
cp -r public/build ../saasy-rbac-wave1/public/build
cd ../saasy-rbac-wave1 && composer dump-autoload -q
php -r "require 'vendor/autoload.php'; echo (new ReflectionClass(App\Support\SettingsAccess::class))->getFileName();"
# must print a path inside saasy-rbac-wave1
```

- [ ] **Step 2: Bring the three docs onto the branch** (they're gitignored under `/docs`, so force-add)

```bash
cp ../saasy/docs/superpowers/specs/2026-10-04-rbac-wave1-doctor-share-subscription-investigation.md docs/superpowers/specs/
cp ../saasy/docs/superpowers/specs/2026-10-04-rbac-wave1-doctor-share-subscription-design.md docs/superpowers/specs/
cp ../saasy/docs/superpowers/plans/2026-10-05-rbac-wave1-doctor-share-subscription.md docs/superpowers/plans/
git add -f docs/superpowers/specs/2026-10-04-rbac-wave1-* docs/superpowers/plans/2026-10-05-rbac-wave1-*
git commit -m "docs: add RBAC wave 1 investigation, design and plan"
```

- [ ] **Step 3: Phase 0 report.** Worktree path, branch, `git rev-parse HEAD`, and confirmation that the main tree's `git status --short` and `git stash list` are unchanged.

---

## Phase 1: Doctor Share

### Task 1: Per-action route middleware (10 routes)

**Files:**
- Modify: `routes/web.php` (Doctor Share block)
- Create: `tests/Feature/Permissions/DoctorShareRouteScopingTest.php`
- Re-run: `tests/Feature/Permissions/DoctorSharePermissionsTest.php`, `tests/Feature/DoctorShare/*`

**Target mapping:**

| Route | Middleware after |
|---|---|
| `rates.index`, `rules.index` | `permission:view share rules\|manage doctor shares` |
| `rates.sync` | `permission:edit share rules\|manage doctor shares` |
| `items.index` | `permission:view share items\|manage doctor shares` |
| `settlements.index`, `settlements.show` | `permission:view settlements\|manage doctor shares` |
| `settlements.preview`, `settlements.store` | `permission:approve settlements\|manage doctor shares` |
| `reports.index`, `reports.print` | `permission:view share reports\|manage doctor shares` (unchanged) |

- [ ] **Step 1: Write the failing tests.** Reuse the `bindDoctorShareTenant()` / user-factory pattern from `DoctorSharePermissionsTest.php`, with **new local helper names** (Pest helpers are global; grep `tests/` for collisions).

```php
// Denied: a wrong-verb permission that passes TODAY → must be 403 after the fix.
dataset('doctor share escalations', [
    'rates page via create share rules'      => ['get',  'doctor-share.rates.index',        ['create share rules']],
    'rates page via edit share rules'        => ['get',  'doctor-share.rates.index',        ['edit share rules']],
    'rules redirect via create share rules'  => ['get',  'doctor-share.rules.index',        ['create share rules']],
    'rates sync via create share rules'      => ['put',  'doctor-share.rates.sync',         ['create share rules']],
    'items via create share items'           => ['get',  'doctor-share.items.index',        ['create share items']],
    'items via delete share items'           => ['get',  'doctor-share.items.index',        ['delete share items']],
    'settlements via create settlements'     => ['get',  'doctor-share.settlements.index',  ['create settlements']],
    'settlements via approve settlements'    => ['get',  'doctor-share.settlements.index',  ['approve settlements']],
    'preview via create settlements'         => ['get',  'doctor-share.settlements.preview',['create settlements']],
    'store via create settlements'           => ['post', 'doctor-share.settlements.store',  ['create settlements']],
]);

it('denies a wrong-verb permission on each doctor share route', function (string $method, string $route, array $perms) {
    dsScopeTenant();
    $this->actingAs(dsScopeUser($perms));
    $this->{$method}(route($route), [])->assertForbidden();
})->with('doctor share escalations');

// settlements.show needs a real settlement row: one extra test asserting 403 for
// ['create settlements'] and 200 for ['view settlements'].

// Allowed: the single correct permission (positive control).
dataset('doctor share proper access', [
    ['get', 'doctor-share.rates.index',        ['view share rules']],
    ['get', 'doctor-share.items.index',        ['view share items']],
    ['get', 'doctor-share.settlements.index',  ['view settlements']],
    ['get', 'doctor-share.settlements.preview',['approve settlements']],
    ['get', 'doctor-share.reports.index',      ['view share reports']],
]);

it('allows the single correct permission on each doctor share route', function (string $method, string $route, array $perms) {
    dsScopeTenant();
    $this->actingAs(dsScopeUser($perms));
    expect($this->{$method}(route($route))->status())->toBeIn([200, 302]);
})->with('doctor share proper access');

it('lets manage doctor shares reach every doctor share route (superset)', function () { /* loop the 9 GET routes; assert not 403 */ });
```

Plus behavioural tests:
- **`rates.sync`, create-only user:** PUT a valid matrix payload as a `create share rules`-only user. Expect 403, and `DoctorShareRate::count()` and the rows are unchanged.
- **`rates.sync`, editor:** PUT as an `edit share rules` user. Expect a redirect and the rows replaced.
- **`settlements.store`, create-only user:** seed one `pending` share item in range. A `create settlements`-only user gets 403, there's no `DoctorShareSettlement` row, and the item stays `pending`.
- **`settlements.store`, approver:** an `approve settlements` user gets a redirect, one settlement row, and the item `settled`. The payload follows `DoctorShareController::settlementsStore` validation; copy the fixture from the existing settlement tests in `tests/Feature/DoctorShare/` if present, otherwise build the minimal rows.

- [ ] **Step 2: Run red.** `vendor/bin/pest tests/Feature/Permissions/DoctorShareRouteScopingTest.php`. The escalation dataset and the create-only behavioural tests fail with `Expected 403 but received 200/302`. Positive controls pass. Record counts.

- [ ] **Step 3: Implement.** Replace each middleware string in the Doctor Share block per the mapping table. Keep the route names, paths and order.

- [ ] **Step 4: Green.** Run the new file, `DoctorSharePermissionsTest`, and the `tests/Feature/DoctorShare` directory.

- [ ] **Step 5: Commit:** `fix: scope doctor share routes to one permission per action`

### Task 2: Gate the Doctor Share action controls

**Files:** the three Doctor Share views listed in File Structure; tests appended to `DoctorShareRouteScopingTest.php`.

- [ ] **Step 1: Failing tests.**
  - The rates page as a `view share rules`-only user: no `action="{{ route('doctor-share.rates.sync') }}"` submit button (assert the Save button text is absent), and the rate inputs render `disabled`.
  - As an `edit share rules` user, Save is present.
  - The settlements index without `approve settlements`/`manage`: no `route('doctor-share.settlements.preview')` link. With `approve settlements`, the link is present.
  - The preview page is no longer reachable without `approve settlements` (route test covers it). Add an assertion that the Confirm Settlement form renders for an approver.

- [ ] **Step 2: Red. Step 3: Implement.**
  - **`rates/index.blade.php`:** compute `$canEditRates = auth()->user()->canany(['edit share rules','manage doctor shares'])` once.
    - When false, add the `disabled` attribute to every rate `<input>` (L88, L133 template) and omit the Add-doctor control (L57), the Remove buttons (L103, L146) and the Save button (L118).
    - The `<form>` stays (it wraps the read-only matrix). Nothing can submit it.
  - **`settlements/index.blade.php:8`:** wrap the link in `@canany(['approve settlements','manage doctor shares'])`.
  - **`settlements/preview.blade.php:108`:** wrap the form in the same `@canany`.

- [ ] **Step 4: Green** (whole new file + `tests/Feature/DoctorShare`). **Step 5: Commit:** `fix: hide doctor share actions users are not permitted to perform`

### Task 3: Settings entry needs a view-level permission

**Files:** `app/Support/SettingsAccess.php` (non-bundled branch, the `return self::userCanAny($user, $names);` line); tests appended to `DoctorShareRouteScopingTest.php`.

- [ ] **Step 1: Failing tests.**
  - A user with only `create share rules`: `SettingsAccess::canAccessSection($user, 'settings.doctor-share', 'GET')` is `false`, and the Settings index (`settings.index`) doesn't list "Doctor Share".
  - With `view share rules`: `true`, and it's listed.
  - With `manage doctor shares`: `true`.
  - Regression: a user with `access settings.lab-report-print` still gets `true` for `settings.lab-report-print` `GET` and `PUT`.

- [ ] **Step 2: Red. Step 3: Implement** (non-bundled branch):

```php
if (in_array($method, ['GET', 'HEAD'], true)) {
    $names = array_values(array_filter(
        $names,
        fn (string $name) => str_starts_with($name, 'view ')
            || str_starts_with($name, 'manage ')
            || str_starts_with($name, 'access ')
    ));
}

return self::userCanAny($user, $names);
```

- [ ] **Step 4: Green.** The new file, plus `tests/Feature/Settings`, `tests/Feature/Navigation`, `tests/Feature/Permissions`.

- [ ] **Step 5: Commit:** `fix: require a view-level permission to list a settings section`

- [ ] **Step 6: Phase 1 report.** SHAs; the red counts and failure reasons per task; green counts; every test changed outside the new files (expected: none); the Doctor Share route→permission table as implemented.

---

## Phase 2: `manage subscription`

### Task 4: Permission, seeder, backfill command

**Files:**
- `app/Support/PermissionRegistry.php`
- `database/seeders/RolePermissionSeeder.php`
- Create `app/Console/Commands/BackfillManageSubscriptionPermission.php`
- Create `tests/Feature/Commands/BackfillManageSubscriptionPermissionTest.php`

- [ ] **Step 1: Failing tests.**
  - `PermissionRegistry::forModule('settings')` contains `manage subscription`.
  - Running `RolePermissionSeeder` gives Hospital Administrator and Super Admin `manage subscription`, and Doctor/Nurse/Receptionist/Pharmacist/Lab Technician don't have it.
  - **Command:** with roles Super Admin, Hospital Administrator, Receptionist and a custom role, `artisan rbac:backfill-manage-subscription`:
    - creates the permission if missing;
    - grants it to exactly Super Admin and Hospital Administrator;
    - a **second run** grants nothing new (idempotent, reports 0 roles updated);
    - Receptionist and the custom role stay without it;
    - `--dry-run` reports the 2 roles it *would* update and writes nothing (`Permission::where('name','manage subscription')->exists()` stays false when it didn't exist).
  - Follow the existing backfill command tests for running against the current tenant connection (see the `BackfillManagePacPermission` tests under `tests/Feature/Commands` / `tests/Feature/OT`).

- [ ] **Step 2: Red. Step 3: Implement.**
  - **Registry:** in `'settings'` module groups, add `'subscription' => ['manage subscription'],`.
  - **Seeder:** add `'manage subscription'` to the master permission list (the `ALL`/domain list near the top) and to `ROLE_PERMISSIONS['Hospital Administrator']`. Super Admin already syncs `Permission::all()`.
  - **Command:** copy the tenant-iteration and cache-isolation skeleton of `BackfillManagePacPermission`, with:
    - signature `rbac:backfill-manage-subscription {--dry-run}`;
    - `TARGET_ROLES = ['Super Admin', 'Hospital Administrator']`;
    - per tenant: `Permission::findOrCreate('manage subscription','web')` (skipped in dry run), then for each existing role in `TARGET_ROLES` lacking it, `givePermissionTo` (or just count in dry run), then `forgetCachedPermissions()`;
    - output `Backfilled tenant {slug} ({n} role(s) updated)` / `[dry-run] would update …`.
  - Docblock: "Role names select backfill targets only; access is always checked by permission."

- [ ] **Step 4: Green. Step 5: Commit:** `feat: add manage subscription permission with seeder default and backfill command`

### Task 5: Gate billing/subscription routes and links

**Files:**
- `routes/web.php` (billing block ~L277-287)
- `resources/views/partials/sidebar.blade.php` (~L66)
- `resources/views/admin/layout.blade.php` (~L47)
- Create `tests/Feature/Billing/ManageSubscriptionPermissionTest.php`
- Re-run `tests/Feature/Billing/PaymentIntegrityTest.php` and `SubscriptionBillingPayfastLazyTest.php` (they authenticate users; their user helpers must be **given `manage subscription`** in this task. List each change.)

- [ ] **Step 1: Failing tests** (landlord-sqlite setup copied from `PaymentIntegrityTest`'s `beforeEach`):
  - **Denied** (fails before, because today they're 200/302 for any logged-in user): a logged-in user **without** `manage subscription` gets 403 on all 6 routes: `billing.index` GET, `billing.subscribe` POST, `billing.payfast.success` GET, `billing.payfast.cancel` GET, `subscription.index` GET, `subscription.activate` POST.
  - **Allowed:** with `manage subscription`, `billing.index` and `subscription.index` return 200, and `billing.subscribe` with a free plan redirects (behaviour unchanged).
  - **Sidebar:** a user with `manage subscription` sees `route('subscription.index')` in the rendered sidebar. A user whose role is **named** `Hospital Administrator` but lacks the permission does **not** (proves the role-name check is gone).
  - **Trial banner:** on an `onTrial()` tenant, "Upgrade Now" shows only with `manage subscription`.
  - **Webhooks unaffected:** the signed PayFast webhook from `PaymentIntegrityTest` still returns 200 (it's re-run, not duplicated).

- [ ] **Step 2: Red. Step 3: Implement.**
  - Add `->middleware('permission:manage subscription')` to the `billing` prefix group and to the two `subscription` routes. Don't touch the webhook routes at ~L126-137.
  - **Sidebar:** replace `@if($currentTenant && auth()->user()->hasAnyRole(['Super Admin', 'Hospital Administrator']))` with `@if($currentTenant) @can('manage subscription')` … `@endcan @endif`.
  - **Layout banner:** wrap the "Upgrade Now" `<a>` in `@can('manage subscription')`.

- [ ] **Step 4: Green.** The new file, `PaymentIntegrityTest`, `SubscriptionBillingPayfastLazyTest`, `tests/Feature/Navigation`. **Step 5: Commit:** `fix: require manage subscription for billing and subscription pages`

### Task 6: DS-2: expired trial for non-admins

**Files:**
- `app/Http/Middleware/EnsureTenantActive.php` (~L44-48)
- `resources/views/errors/trial-expired.blade.php`
- tests appended to `ManageSubscriptionPermissionTest.php`

- [ ] **Step 1: Failing tests** (tenant with `trial_ends_at` in the past and no active subscription; `EnsureTenantActive` **not** bypassed in these tests):
  - **Non-admin** (no `manage subscription`): GET `dashboard` → **402**, view `errors.trial-expired`, sees "ask your administrator", no `route('subscription.index')` link. GET `subscription.index` → 402 (not a redirect, not 403). `POST logout` still works.
  - **Admin** (`manage subscription`): GET `dashboard` → redirect to `subscription.index`; GET `subscription.index` → 200; the trial-expired page shows "Subscribe Now".
  - **No loop:** following redirects from `dashboard` for each user type ends at a 200/402 within 2 hops.

- [ ] **Step 2: Red. Step 3: Implement.**

```php
if ($tenant->trialExpired() && ! $tenant->activeSubscription) {
    if (! $request->routeIs('login') && ! $request->routeIs('logout')) {
        $user = $request->user();

        if ($user && $user->can('manage subscription')) {
            if (! $request->routeIs('subscription.*')) {
                return redirect()->route('subscription.index');
            }
        } else {
            return response()->view('errors.trial-expired', ['tenant' => $tenant], 402);
        }
    }
}
```

  - **Check the order:** the permission cache key is set just above (L41-42), so `can()` resolves against the right tenant.
  - **Guests:** a guest hitting a protected route currently gets the trial redirect *before* auth. With this change a guest (no user) gets the 402 page. Confirm `login` stays reachable (already excluded).
  - **View:** wrap "Subscribe Now" (L27) in `@can('manage subscription')`, with `@else` rendering `<p>Please ask your administrator to renew the subscription.</p>`. `@can` is false for guests, which is correct.

- [ ] **Step 4: Green.** The new file, plus `tests/Feature/Module`, `tests/Feature/Navigation`, `tests/Feature/Billing`. **Step 5: Commit:** `fix: show the trial-expired page to staff who cannot manage the subscription`

- [ ] **Step 6: Phase 2 report.** SHAs, red and green evidence, every changed pre-existing test with the reason, and the list of non-Spatie checks converted (expected: the `sidebar.blade.php` `hasAnyRole`, plus anything else found in touched files).

---

## Phase 3: Closing gate

### Task 7: Regression, review, no merge

- [ ] **Step 1: Focused suites.**

```bash
vendor/bin/pest tests/Feature/Permissions tests/Feature/DoctorShare tests/Feature/Billing tests/Feature/Settings tests/Feature/Navigation tests/Feature/Commands tests/Feature/Module --no-coverage
```

- [ ] **Step 2: Full regression, chunked** (memory-safe). One Pest process per directory or file under `tests/Feature` plus `tests/Unit`, run sequentially, logging `Tests:` per chunk to a summary file in the scratchpad. **Pass criterion:** the failing tests are exactly the 34-test baseline, by file and count. Anything else is a bug: write a failing test, fix, commit, re-run Steps 1–2.

- [ ] **Step 3: Diff review** (`git diff origin/main...HEAD -- app resources routes database`):
  - only Spatie checks;
  - no role-name access checks remain in touched files (`grep -n "hasRole\|hasAnyRole\|role_or_permission" <touched files>`);
  - the webhook routes are untouched;
  - every new string is escaped;
  - no dead code.

- [ ] **Step 4: Backfill dry run** (read-only, real tenants): `php artisan rbac:backfill-manage-subscription --dry-run` from the worktree. Expect "would update 2 role(s)" for each of the 7 tenants (14 total), and nothing written. Record the output.

- [ ] **Step 5: Final report.** HEAD SHA; per-phase SHAs; full chunked-suite table against the baseline; the dry-run output; converted non-Spatie checks; and the explicit statement: **not merged, not pushed, awaiting instruction.**

### Task 8: After human approval only (not part of execution)

1. Fast-forward `main` to the branch, push, and confirm `origin/main` matches.
2. **With explicit approval,** run `php artisan rbac:backfill-manage-subscription` for real. Without it, admins lose the subscription pages after deploy.
3. Remove the worktree.

**Ordering note for deployment:** the routes and the backfill must go live together. Run the backfill **before** or immediately after deploying the route change.

---

## Spec coverage self-check

| Spec item | Task |
|---|---|
| §1 Doctor Share mapping (incl. W1-2, W1-3, DS-1) | 1 |
| §2 Doctor Share UI (rates, New Settlement, preview form) | 2 |
| §2 settings entry needs a view-level permission | 3 |
| §3.1 registry, seeder, backfill (D4) | 4 |
| §3.2 routes (W1-4), webhooks excluded | 5 |
| §3.3 sidebar `hasAnyRole` → `@can` | 5 |
| §3.4 trial banner, trial-expired button, `EnsureTenantActive` (DS-2) | 5, 6 |
| Before/after per route (D6) | 1, 5 (+ behavioural tests) |
| No real-data writes during execution; backfill gated | Global, 7, 8 |
| Chunked full regression against the named 34-test baseline | 7 |
| No merge until confirmed | 7, 8 |

## Placeholder scan

- `/* loop the 9 GET routes */` in Task 1 names its exact assertion.
- The settlement fixture in Task 1 names its source (`settlementsStore` validation and existing DoctorShare tests).

No step defers a decision. The only conditional work is fixing regressions found in Task 7, with a defined procedure.

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-10-05-rbac-wave1-doctor-share-subscription.md` (force-added to the branch in Task 0).

**Do not begin execution until the human confirms this plan.**

When confirmed, two execution options:

1. **Subagent-Driven (recommended):** a fresh subagent per task with review between tasks (`superpowers:subagent-driven-development`), and human reports at the end of Phases 0, 1, 2 and 3.
2. **Inline Execution:** in this session via `superpowers:executing-plans`, with the same checkpoints.

Which approach?
