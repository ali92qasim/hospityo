# RBAC Wave 1 completion: Accounting + OT + Pharmacy Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish the original Wave 1 scope (Doctor Share ✓ → **Accounting → OT → Pharmacy**). Every route in the three modules gets exactly one action permission (plus confirmed supersets and view-only coarse grants on reads):
- **Accounting:** 17 routes.
- **OT:** 25 surgeries-group routes (corrected from 27 on 2026-10-07: the group has 25 route lines).
- **Pharmacy:** 82 routes, 77 live after the 5 dead routes are removed.

Alongside the routing:
- Hide every control a user can't use.
- Stop D2 ("create ≠ view") producing "saved, then 403" (AD-1).
- Introduce `manage theatres` (OT-1).
- Move prescriptions off `edit visits` onto pharmacy permissions (D1).
- Backfill `manage theatres` to Super Admin / HA and `create prescriptions` to Doctor / Nurse (PH-1). Each backfill ships **in the same deploy action** as its route change.

**Architecture:**
- Spatie `permission:` route middleware is the enforcement layer, with matching `@can`/`@canany` in the views. There are no role-name access checks.
- Redirect fallbacks live in **one shared trait**, `App\Http\Controllers\Concerns\RedirectsAfterWrite`. It's created in Phase 1 and **consumed** by OT and Pharmacy, never duplicated.
- Two idempotent tenant backfill commands, modelled on `BackfillManageSubscriptionPermission`:
  - `rbac:backfill-manage-theatres`
  - `rbac:backfill-create-prescriptions`, which includes a report-only safety list.

**Tech Stack:** Laravel 12, Spatie laravel-permission, Spatie multitenancy, Blade, Vite, Pest.

**Specs:**

| Module | Investigation | Design |
|---|---|---|
| Accounting | `docs/superpowers/specs/2026-10-06-rbac-wave1-accounting-investigation.md` | `docs/superpowers/specs/2026-10-06-rbac-wave1-accounting-design.md` |
| OT | `docs/superpowers/specs/2026-10-06-rbac-wave1-ot-investigation.md` | `docs/superpowers/specs/2026-10-06-rbac-wave1-ot-design.md` |
| Pharmacy | `docs/superpowers/specs/2026-10-06-rbac-wave1-pharmacy-investigation.md` | `docs/superpowers/specs/2026-10-06-rbac-wave1-pharmacy-design.md` |

Parent: `docs/superpowers/specs/2026-10-03-rbac-permission-escalation-investigation.md`.

## Confirmed decisions

| # | Decision |
|---|---|
| D1 | Prescriptions (all actions) leave `edit visits` and use pharmacy permissions. Lab/imaging ordering stays for the Laboratory wave. |
| D2 | Create does not imply view. |
| D5 | `view accounting`, `view pharmacy`, `view services` are view-only, accepted on read GETs only. |
| D6 | One permission per action, with before/after tests per route. |
| W1-1 | `manage …` is a superset (here `manage pharmacy`, `manage inventory`). |
| AC-1 | Admins are backfilled only where the seeder intends it. HA loses fiscal-year close (closed as the bug). |
| AC-2 | Form-only pages are gated on their action (deposit and transfer forms; the POS terminal). |
| AC-3 | `fiscal-years.pre-close` requires `close fiscal years`. |
| AC-4 | Fiscal Years sidebar entry: follow-up only, **not** in this plan. |
| AD-1 | After a successful write, redirect to the list/detail page only if the user can view it, otherwise to the originating form (or `back()` for destroy), keeping the flash message. Applied in all three modules. |
| OT-1 | New `manage theatres` permission for theatre create/edit. Backfilled to Super Admin and HA, in the same deploy action. |
| OT-2 | `ot.surgeries.cancel` requires `delete surgeries`. |
| PH-1 | Backfill `create prescriptions` to Doctor and Nurse, with the seeder updated, in the same deploy action, plus a README note. The instruction-template side effect is accepted. Dispense and the URL-only prescription list/show are closed. |
| PH-1a | The backfill also **reports** (and never grants) every other role that holds `edit visits` but not `create prescriptions`. |
| POS | Terminal and checkout both require `dispense pharmacy` (+mp). `view pos` opens nothing (AC-2). |

## Global Constraints

- **Worktree, not the main tree.**
  - The main tree (`C:/Users/Qasim/Herd/saasy`) is on `fix/bug-fixes` with the user's uncommitted work and 4 stashes. **Never** checkout, add, commit or stash there.
  - All work happens in `C:/Users/Qasim/Herd/saasy-rbac-wave1c` on branch `feat/rbac-wave1-completion`, created from `origin/main` @ `6d891b3`.
- **Never run `git stash` in the worktree, for any reason.** Worktrees share one stash list with the main repo. To compare against the pre-change state, use `git show HEAD:<path> > <scratch file>`. Every subagent prompt must repeat this rule.
- **TDD:** failing test first for every behaviour change. Run red and confirm each failure is for the **right reason**: a status mismatch such as `Expected 403 but received 200/302`, or a side-effect assertion seeing a write. Not a setup error. Then implement, run green, commit. At least one commit per task.
- **Before/after proof per route (D6), every phase:**
  - (a) A **deny test:** a permission holder who passes today but must not, gets **403**. Red before the fix.
  - (b) A **positive control:** exactly the new permission passes.
  - (c) For every state-changing route, a **side-effect assertion:** the blocked request wrote nothing (row count unchanged, status unchanged, stock quantity unchanged, no bill or settlement created). These are red before the fix *because the write currently happens*.
- **Spatie only:** `permission:` middleware, `@can`/`@canany`, `$user->can()`/`canany()`. No `hasRole`/`hasAnyRole`/role-name checks in access paths. Role names may appear **only** as backfill target selectors.
- **No real tenant data writes during execution.** The backfills run with `--dry-run` only, in the closing gate. Real runs happen only after merge and with explicit human approval (Task 18).
- **Full regression is chunked:** one Pest process per `tests/Feature/*` directory or file, plus `tests/Unit`, run sequentially (a single full run was memory-killed before).
  - **Pass criterion:** no failures except the known **34-test baseline**, compared **by file and count**:

    | File | Failures |
    |---|---|
    | Auth/AuthenticationTest | 4 |
    | Auth/EmailVerificationTest | 3 |
    | Auth/PasswordConfirmationTest | 3 |
    | Auth/PasswordResetTest | 4 |
    | Auth/PasswordUpdateTest | 2 |
    | Auth/RegistrationTest | 2 |
    | ProfileTest | 5 |
    | Commands/MigrateCoarsePermissionsTest | 3 |
    | Pharmacy/MedicineImportTest | 1 |
    | Pharmacy/PharmacyPosNavigationTest | 1 |
    | Pharmacy/UnitImportTest | 1 |
    | Billing/RevenueReportTest | 1 |
    | Branding/FaviconTest | 1 |
    | ExampleTest | 1 |
    | SuperAdmin/PlanModuleFormTest | 1 |
    | Ui/ConfirmDialogMarkupTest | 1 |

  - **If a baseline test starts passing,** report it; that's not a failure.
- **Phase boundaries:** report to the human at the end of Phases 1, 2, 3 and 4 (format in each phase's last step). Execution runs continuously within a phase. Phase 0 is reported together with Phase 1.
- **Changing existing tests:** only their **permission fixtures** may change, never their behaviour assertions. Each change must first be shown failing on the old fixture after the route change, and listed in the phase report with the reason.
- **No merge, no push** until the final report is confirmed by the human. When done, remove the worktree (`git worktree remove`, then `git worktree prune`) and confirm the main tree's `git status --short` and `git stash list` are unchanged.
- **Pest helpers are global:** grep `tests/` for every new helper name before adding it.

## File Structure

| File | Responsibility |
|---|---|
| `app/Http/Controllers/Concerns/RedirectsAfterWrite.php` | **Create** (Phase 1). The AD-1 redirect helper. |
| `routes/web.php` Accounting block (~L357-412) | Per-route accounting middleware |
| `app/Http/Controllers/AccountingController.php` | Uses the trait for its 7 redirecting writes |
| `resources/views/admin/accounting/{chart-of-accounts,journal-entries,fiscal-years}.blade.php` | `@can` on action controls |
| `routes/web.php` OT surgeries group (~L945-976) | Per-route OT middleware |
| `app/Http/Controllers/OTController.php`, `OperativeMonitoringController.php` | Use the trait |
| `resources/views/admin/ot/{surgeries/show,calendar,theatres/index,checklist/show,consumables/usage,pac/show}.blade.php` | `@can` |
| `app/Support/PermissionRegistry.php` | `ot` module: `'theatres' => ['manage theatres']` |
| `database/seeders/RolePermissionSeeder.php` | `manage theatres` (master list + HA); `create prescriptions` (Doctor + Nurse) |
| `app/Console/Commands/BackfillManageTheatresPermission.php` | **Create.** `rbac:backfill-manage-theatres [--dry-run]` |
| `app/Console/Commands/BackfillCreatePrescriptionsPermission.php` | **Create.** `rbac:backfill-create-prescriptions [--dry-run]` + safety report |
| `routes/web.php` Pharmacy block (~L571-683) | Per-route pharmacy middleware; dead routes removed |
| Pharmacy controllers: `Medicine`, `MedicineCategory`, `MedicineBrand`, `Unit`, `Supplier`, `Purchase`, `Inventory`, `Prescription` | Use the trait |
| `app/Http/Controllers/VisitController.php` (~L350) | `can_prescribe` adds `can('create prescriptions')` |
| Pharmacy views (see Task 15) | `@can` on action controls |
| `resources/js/medicines-index.js`, `resources/js/units-index.js` + their index Blades | Row actions honour `data-can-edit`/`data-can-delete` |
| `app/Services/SidebarService.php` (~L126) | POS condition becomes `dispense pharmacy \|\| manage pharmacy` |
| `README.md` → Deployment | Two new "Required Deploy Step" sections |
| `tests/Feature/Permissions/AccountingRouteScopingTest.php` | **Create** |
| `tests/Feature/Permissions/OtSurgeryRouteScopingTest.php` | **Create** |
| `tests/Feature/Permissions/PharmacyRouteScopingTest.php` (may be split, e.g. `PharmacyCatalogRouteScopingTest`, `PharmacyStockRouteScopingTest`, `PrescriptionRouteScopingTest`) | **Create** |
| `tests/Feature/Commands/BackfillManageTheatresPermissionTest.php`, `BackfillCreatePrescriptionsPermissionTest.php` | **Create** |
| `tests/Unit/RedirectsAfterWriteTest.php` (or Feature) | **Create** |

---

## Phase 0: Worktree, branch, docs

### Task 0: Create the worktree and commit the docs

**Files:** none (git only)

- [ ] **Step 1: Record the main tree's baseline** (for the final before/after check).

```bash
cd C:/Users/Qasim/Herd/saasy
git status --short > <scratchpad>/main-tree-status-before.txt
git stash list    > <scratchpad>/main-tree-stash-before.txt
```

- [ ] **Step 2: Create the worktree off `origin/main`.**

```bash
git fetch origin
git rev-parse origin/main            # expect 6d891b3410ac3814ee88691cf692a0b83c0b7b4d; STOP and report if different
git worktree add -b feat/rbac-wave1-completion ../saasy-rbac-wave1c origin/main
cd ../saasy-rbac-wave1c && git branch --unset-upstream   # a stray push can't target main
cp ../saasy/.env .env
cp -r ../saasy/vendor ../saasy/node_modules .
mkdir -p public && cp -r ../saasy/public/build public/build
composer dump-autoload -q
php -r "require 'vendor/autoload.php'; echo (new ReflectionClass(App\Http\Controllers\AccountingController::class))->getFileName();"
# must print a path inside saasy-rbac-wave1c
```

- [ ] **Step 3: Bring the 7 docs onto the branch.** They're gitignored under `/docs`, so force-add them.

```bash
cp ../saasy/docs/superpowers/specs/2026-10-06-rbac-wave1-*.md docs/superpowers/specs/
cp ../saasy/docs/superpowers/plans/2026-10-06-rbac-wave1-completion-accounting-ot-pharmacy.md docs/superpowers/plans/
git add -f docs/superpowers/specs/2026-10-06-rbac-wave1-* docs/superpowers/plans/2026-10-06-rbac-wave1-completion-*
git commit -m "docs: add RBAC wave 1 completion investigations, designs and plan"
```

- [ ] **Step 4: Note for the Phase 1 report:** the worktree path, branch, HEAD, and the main tree status/stash diff (must be empty).

---

## Phase 1: Accounting

### Task 1: Per-route accounting middleware (17 routes)

**Files:**
- Modify `routes/web.php` (Accounting block).
- Create `tests/Feature/Permissions/AccountingRouteScopingTest.php`.
- Re-run `tests/Feature/Permissions/AccountingPermissionsTest.php` and `tests/Feature/Accounting`.

**Target mapping** (unchanged: general-ledger, the 3 ledgers, profit-loss, balance-sheet):

| Route (`accounting.` prefix) | Middleware after |
|---|---|
| `chart-of-accounts` | `permission:view chart of accounts\|view accounting` |
| `create-account`, `store-account` | `permission:create chart of accounts` |
| `edit-account`, `update-account` | `permission:edit chart of accounts` |
| `deposit`, `process-deposit` | `permission:create deposits` |
| `transfer`, `process-transfer` | `permission:create transfers` |
| `journal-entries` | `permission:view journal entries\|view accounting` |
| `create-journal-entry`, `store-journal-entry` | `permission:create journal entries` |
| `edit-journal-entry`, `update-journal-entry` | `permission:edit journal entries` |
| `fiscal-years` | `permission:view fiscal years\|view accounting` |
| `fiscal-years.pre-close`, `fiscal-years.close` | `permission:close fiscal years` |

- [ ] **Step 1: Write the failing tests.**
  - Tenant binding and user factory: copy the approach of `AccountingPermissionsTest.php` (a mocked tenant with module `accounting`) under **new names**, e.g. `acScopeTenant()` / `acScopeUser(array $perms)`.
  - Fixtures: an `Account` (asset), an open `FiscalYear`, and a manual `JournalEntry` with balanced lines. Reuse the `tests/Feature/Accounting/FiscalYearLockTest.php` / `JournalEntryTest.php` setup.

```php
// (a) Deny: passes TODAY, must be 403 after.
dataset('accounting escalations', [
    'create-account via view CoA'        => ['get',  'accounting.create-account',        [], ['view chart of accounts']],
    'store-account via view accounting'  => ['post', 'accounting.store-account',         [], ['view accounting']],
    'edit-account via create CoA'        => ['get',  'accounting.edit-account',          ['account'], ['create chart of accounts']],
    'update-account via view CoA'        => ['put',  'accounting.update-account',        ['account'], ['view chart of accounts']],
    'chart list via create CoA'          => ['get',  'accounting.chart-of-accounts',     [], ['create chart of accounts']],
    'deposit form via view deposits'     => ['get',  'accounting.deposit',               [], ['view deposits']],
    'process-deposit via view accounting'=> ['post', 'accounting.process-deposit',       [], ['view accounting']],
    'transfer form via view transfers'   => ['get',  'accounting.transfer',              [], ['view transfers']],
    'process-transfer via view accounting'=>['post', 'accounting.process-transfer',      [], ['view accounting']],
    'JE list via create JE'              => ['get',  'accounting.journal-entries',       [], ['create journal entries']],
    'create JE via view JE'              => ['get',  'accounting.create-journal-entry',  [], ['view journal entries']],
    'store JE via view accounting'       => ['post', 'accounting.store-journal-entry',   [], ['view accounting']],
    'edit JE via create JE'              => ['get',  'accounting.edit-journal-entry',    ['entry'], ['create journal entries']],
    'update JE via view JE'              => ['put',  'accounting.update-journal-entry',  ['entry'], ['view journal entries']],
    'FY list via close FY'               => ['get',  'accounting.fiscal-years',          [], ['close fiscal years']],
    'pre-close via view FY'              => ['get',  'accounting.fiscal-years.pre-close',['fy'], ['view fiscal years']],
    'close via view FY'                  => ['post', 'accounting.fiscal-years.close',    ['fy'], ['view fiscal years']],
]);
// The 3rd column names the fixtures to bind as route params; the test resolves them.
// It asserts ->assertForbidden() for each case.
```

  - **(b) Positive controls,** a dataset with the same 17 routes, each with exactly its new permission:
    - GET routes expect 200;
    - writes with a **valid payload** expect a 302 that is not to a 403;
    - `close` uses the fiscal year's exact confirmation string (see `closeFiscalYear` validation, `:613`).
  - **Coarse read:** a `view accounting`-only user gets 200 on `chart-of-accounts`, `journal-entries`, `fiscal-years` and the 6 report routes, and 403 on all 14 form/write/close routes (loop).
  - **(c) Side effects** (blocked writes write nothing):
    - `view chart of accounts`-only valid POST `store-account`: 403, and `Account::count()` unchanged.
    - `view accounting`-only valid POST `process-deposit`, `process-transfer`, `store-journal-entry`: 403 each, and `JournalEntry::count()` and `JournalEntryLine::count()` unchanged.
    - `view chart of accounts`-only PUT `update-account` with a new name: 403, and the name is unchanged.
    - `view journal entries`-only PUT `update-journal-entry` with changed lines: 403, and the lines are unchanged.
    - `view fiscal years`-only valid POST `close`: 403, and the fiscal year is still `is_closed = 0` with `closed_by` null.
    - `close fiscal years` user, valid close: `is_closed = 1`. The positive control proves the close happens with the right permission.
  - **AC-1 (seeded HA):**
    - run `RolePermissionSeeder`, then create a user with the seeded `Hospital Administrator` role;
    - 403 on `pre-close` and `close`;
    - 200 on `chart-of-accounts`, `journal-entries`, `fiscal-years`, `create-account`, `create-journal-entry`, `deposit`, `transfer` and the 6 reports.

- [ ] **Step 2: Run red.** `vendor/bin/pest tests/Feature/Permissions/AccountingRouteScopingTest.php`. Expected:
  - every escalation case fails (`Expected 403 … received 200/302`);
  - the side-effect tests fail because the write happened;
  - the coarse-read writes loop and the AC-1 close tests fail;
  - the positive controls pass.

  Record the counts and two sample failure messages.

- [ ] **Step 3: Implement.** Replace the four any-of `Route::middleware(...)->group()` blocks with per-route `->middleware('permission:…')` per the table. Keep paths, names, order and HTTP verbs.

- [ ] **Step 4: Green.** The new file, `AccountingPermissionsTest`, and `tests/Feature/Accounting`.

- [ ] **Step 5: Commit:** `fix: scope accounting routes to one permission per action`

### Task 2: Extract `RedirectsAfterWrite` and apply it in Accounting (AD-1)

**Files:**
- Create `app/Http/Controllers/Concerns/RedirectsAfterWrite.php` and `tests/Unit/RedirectsAfterWriteTest.php` (or a Feature test if it needs the router).
- Modify `AccountingController`.
- Append to `AccountingRouteScopingTest.php`.

**Trait contract.** This supersedes the shorter signature sketched in the Accounting design §3; the behaviour is identical.

```php
namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsAfterWrite
{
    /**
     * AD-1: send the user to $route only if they may view it; otherwise to $fallbackRoute
     * (or back() when null). The flash message is kept either way.
     *
     * @param  list<string>  $viewPermissions  any-of permissions that open $route
     */
    protected function redirectAfterWrite(
        string $route,
        array $routeParams,
        array $viewPermissions,
        string $message,
        ?string $fallbackRoute = null,
        array $fallbackParams = [],
        string $flashKey = 'success',
    ): RedirectResponse {
        if (auth()->user()?->canany($viewPermissions)) {
            return redirect()->route($route, $routeParams)->with($flashKey, $message);
        }

        return $fallbackRoute === null
            ? back()->with($flashKey, $message)
            : redirect()->route($fallbackRoute, $fallbackParams)->with($flashKey, $message);
    }
}
```

- [ ] **Step 1: Failing tests.**
  - **Trait unit/feature test** (a tiny test controller or anonymous class using the trait):
    - (i) with a view permission → redirect to `$route` with the flash;
    - (ii) without it, with a fallback → redirect to the fallback with the same flash;
    - (iii) without it, with a null fallback → `back()` with the flash.
  - **Accounting:** for each of the 7 redirecting writes, (a) the write permission only, then (b) the write permission + view:

    | Write | Without view | With view |
    |---|---|---|
    | `store-account` | `create-account` | `chart-of-accounts` |
    | `update-account` | `edit-account` | `chart-of-accounts` |
    | `process-deposit` | `deposit` | `chart-of-accounts` |
    | `process-transfer` | `transfer` | `chart-of-accounts` |
    | `store-journal-entry` | `create-journal-entry` | `journal-entries` |
    | `update-journal-entry` | `edit-journal-entry` | `journal-entries` |
    | `fiscal-years.close` | `fiscal-years.pre-close` | `fiscal-years` |

    Assert the session `success` flash in both cases. `view accounting` counts as view for all three lists.

- [ ] **Step 2: Red** (the trait class doesn't exist; the accounting fallbacks currently go to the 403'd list). **Step 3: Implement.**
  - Create the trait.
  - In `AccountingController`, `use RedirectsAfterWrite;` and replace the 7 `redirect()->route(<list>)->with('success', …)` calls (`:49, :94, :206, :287, :339, :396, :632`) with `redirectAfterWrite(...)`, view permissions `['view <x>', 'view accounting']`.
  - **Error paths (`back()->withErrors…`) are unchanged.**

- [ ] **Step 4: Green** (the new tests + `tests/Feature/Accounting`). **Step 5: Commit:** `feat: redirect after accounting writes only to pages the user can view`

### Task 3: Gate the accounting action controls

**Files:** `chart-of-accounts.blade.php` (L8, L11, L14, L80), `journal-entries.blade.php` (L12, L72), `fiscal-years.blade.php` (L49); tests appended.

- [ ] **Step 1: Failing tests.**
  - **`view chart of accounts` + `view accounting` user on chart-of-accounts:** absent are `href="{{ route('accounting.deposit') }}"`, `route('accounting.transfer')`, `route('accounting.create-account')` and any `route('accounting.edit-account', …)`.
  - **Users with `create deposits` / `create transfers` / `create chart of accounts` / `edit chart of accounts` (each, plus view):** each control is present.
  - **Journal entries:** New and per-row Edit appear only with `create` / `edit journal entries`. The "Auto, locked" icon still renders for auto entries.
  - **Fiscal years:** with `view fiscal years` only, there's no `route('accounting.fiscal-years.pre-close', …)` link and open years show "—". With `close fiscal years`, the link is present.

- [ ] **Step 2: Red. Step 3: Implement** the `@can` wraps per the design §4. For fiscal years: `@if(!$fy->is_closed) @can('close fiscal years') <link> @else — @endcan @else Locked @endif`.

- [ ] **Step 4: Green.** `tests/Feature/Permissions`, `tests/Feature/Accounting`, `tests/Feature/Navigation`. **Step 5: Commit:** `fix: hide accounting actions users are not permitted to perform`

- [ ] **Step 6: Phase 1 report** (includes Phase 0):
  - worktree, branch and SHAs;
  - per task: red counts with failure reasons, green counts;
  - the **route → permission table as implemented** (17 rows);
  - the **side-effect proof list** (each blocked write and the unchanged count or state);
  - the AC-1 seeded-HA result;
  - pre-existing tests changed (expected: none);
  - the main tree status/stash unchanged.

---

## Phase 2: Operation Theatre

### Task 4: `manage theatres`: registry, seeder, backfill command (OT-1)

**Files:**
- `app/Support/PermissionRegistry.php` (`ot` module groups)
- `database/seeders/RolePermissionSeeder.php` (OT section of `PERMISSIONS`; `ROLE_PERMISSIONS['Hospital Administrator']`)
- Create `app/Console/Commands/BackfillManageTheatresPermission.php` and `tests/Feature/Commands/BackfillManageTheatresPermissionTest.php`
- Modify `tests/Unit/PermissionRegistryTest.php:122` (the exact `forModule('ot')` list; add `'manage theatres'`, the parity precedent from Wave 1)

- [ ] **Step 1: Failing tests.**
  - `PermissionRegistry::forModule('ot')` contains `manage theatres`. First run the existing `PermissionRegistryTest`: it must **fail** after only the registry change, before its expected list is updated. Record that.
  - `RolePermissionSeeder` gives Super Admin and HA `manage theatres`, and Doctor, Nurse, Receptionist, Pharmacist and Lab Technician don't get it.
  - **Command,** with roles Super Admin, HA, Nurse and a custom role:
    - creates the permission if missing;
    - grants exactly Super Admin and HA;
    - **a second run** updates 0 roles (idempotent);
    - Nurse and the custom role stay without it;
    - **`--dry-run`:** reports "would update 2 role(s)", the permission row still doesn't exist, no grants are made, and the permission cache key is untouched.
  - Copy the harness from `tests/Feature/Commands/BackfillManageSubscriptionPermissionTest.php`.
  - If `tests/Feature/OT/OtPermissionSeederTest.php` asserts exact HA lists, it fails here. Update its expected list and record it.

- [ ] **Step 2: Red. Step 3: Implement.**
  - **Registry:** `'theatres' => ['manage theatres'],` in the `ot` groups.
  - **Seeder:** add to the master list and to HA.
  - **Command:** copy `BackfillManageSubscriptionPermission` with:
    - `PERMISSION_NAME = 'manage theatres'`
    - `TARGET_ROLES = ['Super Admin', 'Hospital Administrator']`
    - signature `rbac:backfill-manage-theatres {--dry-run}`
    - the same docblock: "Role names select backfill targets only; access is always checked by permission."

- [ ] **Step 4: Green.**
  - Run the new command test, `tests/Unit/PermissionRegistryTest.php`, `tests/Feature/OT`, `tests/Feature/Commands` and `tests/Feature/Module`.
  - The only Commands failures allowed are the 3 baseline `MigrateCoarsePermissionsTest` ones.
  - **Step 5: Commit:** `feat: add manage theatres permission with seeder default and backfill command`

### Task 5: Per-route OT middleware (25 routes)

**Files:**
- Modify `routes/web.php` (the OT surgeries group).
- Create `tests/Feature/Permissions/OtSurgeryRouteScopingTest.php`.
- Re-run `tests/Feature/OT`.

**Target mapping:**

| Route (`ot.` prefix) | Middleware after |
|---|---|
| `calendar`, `calendar.events`, `theatres`, `surgeries.index`, `surgeries.show`, `monitoring.vitals-data` | `permission:view surgeries` |
| `check-conflicts` | `permission:create surgeries\|edit surgeries` |
| `theatres.create`, `theatres.store`, `theatres.edit`, `theatres.update` | `permission:manage theatres` |
| `surgeries.create`, `surgeries.store` | `permission:create surgeries` |
| `surgeries.edit`, `surgeries.update`, `surgeries.start`, `surgeries.complete`, `surgeries.postpone` | `permission:edit surgeries` |
| `surgeries.cancel` | `permission:delete surgeries` |
| `monitoring.anaesthesia`, `.store-anaesthesia`, `.vitals`, `.store-vitals`, `.post-op`, `.store-post-op` | `permission:edit surgeries` |

- [ ] **Step 1: Failing tests.**
  - **Fixtures:** a theatre and surgeries in each status: `scheduled`, `in_progress`, plus PAC cleared and checklist sign-in complete, so `start` reaches its write. Reuse the `makeScheduledSurgery` approach from `tests/Feature/OT/SurgicalChecklistIndexTest.php` under new helper names (`otScope*`).
  - **(a) Deny dataset, 25 rows:**
    - every write/form route with a `view surgeries`-only user;
    - every read route with a `create surgeries`-only user;
    - `check-conflicts` with `view surgeries`-only;
    - theatre create/store/edit/update with an `edit surgeries`-only user (OT-1);
    - `cancel` with an `edit surgeries`-only user (OT-2).
  - **(b) Positive dataset:** each route with exactly its permission (GET 200; writes with valid payloads give a non-403 302 or 200 JSON).
  - **(c) Side effects:**
    - `view surgeries`-only POST `start` / `complete` / `postpone` / `cancel`: 403, and the surgery `status` is unchanged.
    - `edit surgeries`-only POST `cancel` with a reason: 403, status ≠ `cancelled`. A `delete surgeries` user: status = `cancelled`.
    - `view surgeries`-only POST `store-anaesthesia` / `store-vitals` / `store-post-op`: 403, and the row count of each monitoring table is unchanged.
    - `view surgeries`-only POST `surgeries.store`: 403, `Surgery::count()` unchanged.
    - `edit surgeries`-only POST `theatres.store`: 403, `OperationTheatre::count()` unchanged. PUT `theatres.update`: name and status unchanged.
  - **Superset sanity:** a user with all 4 surgery permissions **but not** `manage theatres` is still denied theatre writes. That shows `manage theatres` is really required, not just any surgery permission.

- [ ] **Step 2: Red** (the deny and side-effect cases fail with 200/302 and real writes). **Step 3: Implement:** replace the group with per-route middleware. **Step 4: Green:** the new file + `tests/Feature/OT` + `tests/Unit/ModuleRegistryTest.php` (route→module mapping must be unaffected). **Step 5: Commit:** `fix: scope operation theatre surgery routes to one permission per action`

### Task 6: AD-1 redirects in OT (consume the trait)

**Files:** `OTController`, `OperativeMonitoringController`; tests appended to `OtSurgeryRouteScopingTest.php`.

- [ ] **Step 1: Failing tests,** both cases each:

  | Write | Without view | With `view surgeries` |
  |---|---|---|
  | `theatres.store` | `ot.theatres.create` | `ot.theatres` |
  | `theatres.update` | `ot.theatres.edit` | `ot.theatres` |
  | `surgeries.store` | `ot.surgeries.create` | `ot.surgeries.index` |
  | `surgeries.update` | `ot.surgeries.edit` | `ot.surgeries.show` |
  | `monitoring.store-anaesthesia` | `ot.monitoring.anaesthesia` | `ot.surgeries.show` |

  The flash is kept in both cases.

- [ ] **Step 2: Red. Step 3: Implement.** `use RedirectsAfterWrite;` in both controllers; replace `OTController:181, :208, :327, :432` and `OperativeMonitoringController:71`. **Do not** change the `back()` responses or the `OTController:339` guard redirect in `edit()`. **Step 4: Green. Step 5: Commit:** `feat: redirect after operation theatre writes only to pages the user can view`

### Task 7: Gate OT action controls + README deploy note for `manage theatres`

**Files:**
- `resources/views/admin/ot/surgeries/show.blade.php` (L33, L40, L43, L49, L56, L346, L353, L360, plus forms L165/L187/L204)
- `calendar.blade.php` (L40)
- `theatres/index.blade.php` (L10, L51)
- `checklist/show.blade.php` (L35), `consumables/usage.blade.php` (L86), `pac/show.blade.php` (L219)
- `README.md`
- tests appended

- [ ] **Step 1: Failing tests.**
  - **Show page:**
    - a `view surgeries`-only user on a `scheduled` surgery sees none of: the Start form action, `id="postpone-btn"`, the Edit link, the monitoring links;
    - on `in_progress`: no `id="complete-btn"`;
    - Cancel (`id="cancel-btn"`) is absent;
    - an `edit surgeries` user sees Start / Postpone / Edit / Complete / monitoring links, but **not** Cancel;
    - a `delete surgeries` (+view) user sees Cancel.
  - **Calendar:** "Schedule" (`route('ot.surgeries.create')`) only with `create surgeries`.
  - **Theatres index:** Add/Edit only with `manage theatres`.
  - **Back-links:** the PAC, checklist and usage pages show the back-link to `ot.surgeries.show` only with `view surgeries`. A `manage pac` user without `view surgeries` doesn't see it.

- [ ] **Step 2: Red. Step 3: Implement** the `@can` wraps per the OT design §5. The `@canany` of all 4 surgery permissions on the 3 back-links becomes `@can('view surgeries')`. No JS change: `ot-surgery-show.js` `setupToggle` already returns when an element is missing. Verify this with a grep in the report.

- [ ] **Step 4: README.** Add **"Required Deploy Step: `manage theatres` (RBAC Wave 1 completion)"** under Deployment, after the `manage subscription` section, in the same structure:
  - **Hard requirement:** run it in the same deploy action as the code. From the moment the routes are live, Super Admin and HA get 403 on theatre create/edit until it has run.
  - The `--dry-run` command and its expected output ("would update 2 role(s)" per tenant).
  - The real command.
  - The post-check (exactly Super Admin and HA hold `manage theatres`).
  - "Not yet applied locally" (updated in Task 18).

- [ ] **Step 5: Green** (`tests/Feature/OT`, the OT scoping file, `tests/Feature/Navigation`). **Step 6: Commit:** `fix: hide operation theatre actions users are not permitted to perform`

- [ ] **Step 7: Phase 2 report.** SHAs; red/green per task; the **25-row route → permission table as implemented**; the side-effect proof list; the OT-1 superset-sanity result; changed pre-existing tests with reasons (expected: `PermissionRegistryTest:122`, and possibly `OtPermissionSeederTest`); the README section text; main tree unchanged.

---

## Phase 3: Pharmacy (incl. D1 prescriptions)

### Task 8: `create prescriptions` backfill with safety report (PH-1)

**Files:**
- `database/seeders/RolePermissionSeeder.php` (`ROLE_PERMISSIONS['Doctor']`, `['Nurse']`)
- Create `app/Console/Commands/BackfillCreatePrescriptionsPermission.php` and `tests/Feature/Commands/BackfillCreatePrescriptionsPermissionTest.php`

- [ ] **Step 1: Failing tests.**
  - **Seeder:** after `RolePermissionSeeder`, Doctor and Nurse hold `create prescriptions`. Receptionist, Lab Technician and Medical Records Clerk (if seeded) don't. Pharmacist and HA keep what they had.
  - **Command,** with roles Super Admin (all permissions), HA, Doctor (`edit visits` only), Nurse (`edit visits` only), Receptionist, and **a custom role "Ward Clerk" holding `edit visits` but not `create prescriptions`**:
    - grants `create prescriptions` to exactly Doctor and Nurse;
    - a second run updates 0 (idempotent);
    - Receptionist untouched.
  - **Safety report test (explicit, required):**
    - the output (dry run **and** real run) contains a line naming `Ward Clerk` as holding `edit visits` without `create prescriptions`;
    - **after the real run, Ward Clerk still does NOT hold `create prescriptions`**;
    - its total permission count is unchanged;
    - Super Admin and HA are **not** listed, because they already hold it.
  - **`--dry-run`:** reports "would update 2 role(s)" and the Ward Clerk safety line, and writes nothing: Doctor and Nurse still lack the permission, and the cache is untouched.
  - **Missing roles:** a tenant with no Nurse role updates Doctor only and doesn't error.

- [ ] **Step 2: Red. Step 3: Implement.**
  - **Seeder:** add `'create prescriptions'` to Doctor and Nurse.
  - **Command:** copy `BackfillManageSubscriptionPermission` with:
    - `PERMISSION_NAME = 'create prescriptions'`
    - `TARGET_ROLES = ['Doctor', 'Nurse']`
    - signature `rbac:backfill-create-prescriptions {--dry-run}`
  - **Safety report:** per tenant, after computing targets, query roles that hold `edit visits` (guard `web`), lack `create prescriptions`, and aren't in `TARGET_ROLES`. For each, print `  [report only] Tenant {slug}: role "{name}" holds edit visits but not create prescriptions; not granted.`. This is **read-only in both modes.**
  - Docblock: "Role names select backfill targets only; access is always checked by permission. The safety report never grants."

- [ ] **Step 4: Green** (the new file, `tests/Feature/Commands` with only the 3 baseline failures, `tests/Feature/Module`). **Step 5: Commit:** `feat: backfill create prescriptions for doctors and nurses with a safety report`

### Task 9: Catalog + instruction templates: per-route middleware, dead routes removed

**Files:**
- `routes/web.php` (medicines, medicine-categories, medicine-brands, units, prescription-instructions)
- Create `tests/Feature/Permissions/PharmacyCatalogRouteScopingTest.php`

**Notation:** R = `view pharmacy|view services|manage pharmacy`; +mp = `|manage pharmacy`.

| Resource (`<X>` permission noun) | index/show/data | create, store | edit, update | destroy | import, import-status |
|---|---|---|---|---|---|
| `medicines` (`medicines`) | `view medicines\|`R | `create medicines`+mp | `edit medicines`+mp | `delete medicines`+mp | `manage pharmacy` (unchanged) |
| `medicine-categories` (`medicine categories`) | `view medicine categories\|`R | `create …`+mp | `edit …`+mp | `delete …`+mp | unchanged |
| `medicine-brands` (`brands`) | `view brands\|`R | `create brands`+mp | `edit brands`+mp | `delete brands`+mp | unchanged |
| `units` (`units`): **no show** | `view units\|`R | `create units`+mp | `edit units`+mp | `delete units`+mp | unchanged |
| `prescription-instructions` (`prescriptions`): **no show** | `view prescriptions\|`R | `create prescriptions`+mp | `edit prescriptions`+mp | `delete prescriptions`+mp | n/a |

**Shape:** `Route::resource(...)->only([...])->middleware(...)`, one registration per permission, as the existing `medicines` block does. `units` and `prescription-instructions` use `->except(['show'])` (dead). Keep the import routes **before** their resource (existing ordering comment).

- [ ] **Step 1: Failing tests.**
  - **(a) Deny:**
    - for each resource: `view services`-only and `view pharmacy`-only on create/store/edit/update/destroy;
    - `create <X>`-only on index and on edit/destroy;
    - `edit <X>`-only on destroy.
  - **(b) Positive:** each route with its own permission. `manage pharmacy` passes every catalog route.
  - **Coarse read:** `view services` and `view pharmacy` each get 200 on every index/show/data.
  - **(c) Side effects:**
    - `view services`-only `DELETE medicines/{id}`: 403, the medicine still exists;
    - the same for a category, a brand, a unit and an instruction;
    - `view pharmacy`-only `POST medicines.store` with a valid payload: 403, `Medicine::count()` unchanged;
    - `create brands`-only `PUT medicine-brands.update`: 403, the name is unchanged.
  - **Dead routes:** `Route::has('units.show')` and `Route::has('prescription-instructions.show')` are false.

- [ ] **Step 2: Red. Step 3: Implement. Step 4: Green:** the new file, plus `tests/Feature/Pharmacy` (only the 3 baseline failures allowed) and `tests/Feature/Module`. **Step 5: Commit:** `fix: scope pharmacy catalog routes to one permission per action`

### Task 10: Suppliers, purchases, inventory

**Files:**
- `routes/web.php` (suppliers, purchases, inventory)
- Create `tests/Feature/Permissions/PharmacyStockRouteScopingTest.php`

+mi = `|manage inventory`.

| Route | After |
|---|---|
| suppliers: index, show | `view suppliers\|`R |
| suppliers: create, store / edit, update / destroy | `create suppliers` / `edit suppliers` / `delete suppliers`, each +mp |
| purchases: index, show | `view purchases\|`R |
| purchases: create, store | `create purchases`+mp |
| `purchases.approve`, `purchases.receive` | `edit purchases`+mp |
| `purchases.cancel` | `delete purchases`+mp |
| `inventory.index`, `.low-stock`, `.expiring`, `.opening-stock` | `view inventory\|`R+mi |
| `inventory.opening-stock.import`, `.import-status` | `create inventory`+mp+mi |
| `inventory.stock-in`, `.process-stock-in`, `.medicines.batches` | `create inventory`+mp+mi (batches corrected 2026-10-07: batches is used only by the stock-in form) |
| `inventory.stock-out`, `.process-stock-out` | `edit inventory`+mp+mi |

- [ ] **Step 1: Failing tests.**
  - **(a) Deny:**
    - `view services`-only on every supplier/purchase write, `approve`/`receive`/`cancel`, stock-in/out (GET and POST), `batches` and the opening-stock import;
    - `create purchases`-only on `approve`;
    - `edit purchases`-only on `cancel`;
    - `create inventory`-only on stock-out;
    - `edit inventory`-only on stock-in.
  - **(b) Positive** for each route. `manage pharmacy` passes all except inventory reads, where R also includes it; `manage inventory` passes all inventory routes.
  - **(c) Side effects:**
    - `view services`-only `POST purchases.approve` on a pending order: 403, status still `pending`.
    - `view services`-only `POST purchases.receive` on an approved order: 403, status still `approved`, and **`InventoryTransaction::count()` and batch quantities unchanged**.
    - `view services`-only `POST purchases.cancel`: 403, status unchanged.
    - `view services`-only `POST inventory.process-stock-in`: 403, no new batch or transaction.
    - `view services`-only `POST inventory.process-stock-out`: 403, **the batch `quantity` is unchanged**.
    - `view pharmacy`-only `DELETE suppliers/{id}`: 403, the supplier exists.
    - Reuse the purchase/stock fixtures from the existing `tests/Feature/Pharmacy` tests (grep for `PurchaseOrder::create` / `MedicineBatch` factories).

- [ ] **Step 2: Red. Step 3: Implement. Step 4: Green** (+ `tests/Feature/Pharmacy`). **Step 5: Commit:** `fix: scope supplier, purchase and inventory routes to one permission per action`

### Task 11: POS + prescriptions (D1) + the visit prescription panel

**Files:**
- `routes/web.php` (POS group, prescriptions resource, dispense, `visits.prescription`)
- `app/Http/Controllers/VisitController.php` (~L350)
- Create `tests/Feature/Permissions/PrescriptionRouteScopingTest.php`
- **Existing tests whose permission fixtures change** (listed in Step 5)

| Route | After |
|---|---|
| `pharmacy.pos.index`, `.medicines.search`, `.prescription`, `.checkout` | `dispense pharmacy`+mp |
| `visits.prescription`, `prescriptions.create`, `prescriptions.store` | `create prescriptions` |
| `prescriptions.index`, `prescriptions.show` | `view prescriptions`+mp |
| `prescriptions.dispense` | `dispense pharmacy`+mp |
| `prescriptions.edit`, `.update`, `.destroy` | **removed** (`Route::resource(...)->only(['index','create','store','show'])`) |

- [ ] **Step 1: Failing tests.**
  - **(a) Deny:**
    - `view pos`-only on all 4 POS routes;
    - `edit visits`-only on `visits.prescription`, `prescriptions.create`/`store`/`index`/`show`/`dispense`;
    - `manage pharmacy`-only on `visits.prescription` and `prescriptions.store` (prescribing is clinical; no superset);
    - `create prescriptions`-only on `prescriptions.index` and `dispense`.
  - **(b) Positive:**
    - `dispense pharmacy` on the POS routes and `dispense`;
    - `create prescriptions` on `visits.prescription` (valid payload; OPD visit with an assigned doctor) and the standalone store;
    - `view prescriptions` on index/show.
  - **(c) Side effects:**
    - `view pos`-only POS `checkout` (prescription mode, valid payload): 403, **no new `Bill`**, the prescription stays `pending`, **batch quantities unchanged**.
    - `edit visits`-only `prescriptions.dispense`: 403, status `pending`, `dispensed_date` null, **batch quantities and `InventoryTransaction::count()` unchanged**.
    - `edit visits`-only `visits.prescription`: 403, `Prescription::count()` unchanged.
    - `dispense pharmacy` user dispense: status `dispensed` and stock reduced (positive).
  - **Panel:** the visit workflow page (OPD) shows the prescription form (`route('visits.prescription', …)`) for a `view visits` + `edit visits` + `create prescriptions` user, and **doesn't** for `view visits` + `edit visits` without `create prescriptions`. The IPD doctor-picker path still works for a non-doctor with `create prescriptions` (reuse the `IpdClinicalFeaturesTest` fixture).
  - **Seeded roles:** after `RolePermissionSeeder`, a user with the seeded **Doctor** role, and separately one with **Nurse**, can POST `visits.prescription` (302, prescription created). Neither can `dispense` (403).
  - **Dead routes:** `Route::has()` is false for `prescriptions.edit/update/destroy`. A grep test confirms no `route('prescriptions.(edit|update|destroy)'` remains in `resources/` or `app/`.

- [ ] **Step 2: Red. Step 3: Implement.**
  - **Routes:** per the table.
  - **`VisitController:350`:** `'can_prescribe' => $handler->canPrescribe($visit) && Tenant::currentHasModule('pharmacy') && $request->user()?->can('create prescriptions'),`. Use the request/auth user already available in that method.

- [ ] **Step 4: Run the existing suites and record which now fail** (expected: these, because their fixtures grant only `edit visits`):
  - `Pharmacy/PrescriptionDispenseRouteTest`
  - `Pharmacy/PrescriptionRoutesRegistrationTest`
  - `Pharmacy/PrescriptionRequiresSellingPriceTest`
  - `Pharmacy/VisitPrescriptionFulfillmentTypeTest`
  - `Pharmacy/VisitPrescriptionPricingTest`
  - the 2 visit-prescription cases in `Module/ParentRouteEntitlementTest`
  - the `visits.prescription` cases in `Visits/IpdClinicalFeaturesTest`
  - `DoctorShare/DoctorSharePharmacyPosTest` and `Pharmacy/PharmacyPosCheckoutTest`, only if their users lack `dispense pharmacy` (they're expected to hold it already)

  Record each failure message (it must be a 403).

- [ ] **Step 5: Update only the permission fixtures:**
  - `edit visits` → `dispense pharmacy` for dispense;
  - add `create prescriptions` for prescribing;
  - in `PrescriptionRoutesRegistrationTest`, assert the 3 dead names are gone and the remaining routes carry the new middleware.

  Behaviour assertions stay unchanged. List every edited test, with before/after fixture and reason, for the phase report.

- [ ] **Step 6: Green:** the new file, `tests/Feature/Pharmacy` (3 baseline failures only), `tests/Feature/Visits`, `tests/Feature/Module`, `tests/Feature/DoctorShare`. **Step 7: Commit:** `fix: gate prescriptions and pharmacy POS on pharmacy permissions`

### Task 12: AD-1 redirects in Pharmacy (consume the trait)

**Files:** `MedicineController`, `MedicineCategoryController`, `MedicineBrandController`, `UnitController`, `SupplierController`, `PurchaseController`, `InventoryController`, `PrescriptionController`; tests appended to the pharmacy scoping files.

| Controller | Writes (current line) | View permissions for target | Fallback |
|---|---|---|---|
| Medicine | store :81, update :93, destroy :99 | `view medicines`, R | `medicines.create` / `medicines.edit` / `back()` |
| MedicineCategory | store :45, update :65, destroy :77 | `view medicine categories`, R | create / edit / `back()` |
| MedicineBrand | store :44, update :64, destroy :76 | `view brands`, R | create / edit / `back()` |
| Unit | store :48, update :62, destroy :74 | `view units`, R | create / edit / `back()` |
| Supplier | store :44, update :73, destroy :87 | `view suppliers`, R | create / edit / `back()` |
| Purchase | store :96 | `view purchases`, R | `purchases.create` |
| Inventory | process-stock-in :132, process-stock-out :209 | `view inventory`, R, `manage inventory` | `inventory.stock-in` / `inventory.stock-out` |
| Prescription | store :89 | `view prescriptions`, `manage pharmacy` | `prescriptions.create` |

**Not changed:** the import redirects (`MedicineController:140`, etc.: `manage pharmacy` is in R), `back()` responses, POS checkout, dispense, and `visits.prescription`.

Before editing, verify each line number against the file in the worktree and report any drift.

- [ ] **Step 1: Failing tests.** Both cases (view held / not held) for one store and one destroy per controller pattern, plus every distinct fallback: create form, edit form, `back()`, the stock-in form, the stock-out form, `purchases.create`, `prescriptions.create`. Flash kept in each.
- [ ] **Step 2: Red. Step 3: Implement. Step 4: Green** (+ `tests/Feature/Pharmacy`). **Step 5: Commit:** `feat: redirect after pharmacy writes only to pages the user can view`

### Task 13: Gate Pharmacy Blade controls + POS sidebar

**Files:** the views listed in the Pharmacy design §6 (exact lines there), `app/Services/SidebarService.php:126`; tests appended.

- [ ] **Step 1: Failing tests.**
  - **For each index, as a view-only user:** absent are New (`route('<res>.create')`), row Edit/Delete and show-page Edit. Present for users holding each permission (or `manage pharmacy`).
  - **Purchases** (index and show):
    - Approve and Receive appear only with `edit purchases` (+mp);
    - Cancel only with `delete purchases` (+mp).
  - **Inventory:**
    - Stock In (index, low-stock, opening-stock) only with `create inventory` (+mp/mi);
    - Stock Out (index, expiring) only with `edit inventory` (+mp/mi).
  - **Prescriptions:** New only with `create prescriptions`; Dispense (index, show) only with `dispense pharmacy` (+mp).
  - **Instructions:** New → `create prescriptions`, Edit → `edit prescriptions`, Delete → `delete prescriptions` (+mp).
  - **Sidebar:** POS shows for `dispense pharmacy`, and for `manage pharmacy`. It does **not** show for `view pos`-only. Update `PharmacyPosNavigationTest` only if a case there conflicts; it uses `dispense pharmacy`, so it's expected not to.
  - The existing import gates are unchanged (regression assertion).

- [ ] **Step 2: Red. Step 3: Implement.** Wrap each control with the matching `@can`/`@canany`. **Sidebar** `:126` becomes `$user->can('dispense pharmacy') || $user->can('manage pharmacy')`.
- [ ] **Step 4: Green** (+ `tests/Feature/Pharmacy`, `tests/Feature/Navigation`). **Step 5: Commit:** `fix: hide pharmacy actions users are not permitted to perform`

### Task 14: JS-rendered row actions (medicines, units)

**Files:**
- `resources/views/admin/medicines/index.blade.php` (root `#medicines-index`)
- `resources/views/admin/units/index.blade.php` (its root container)
- `resources/js/medicines-index.js` (~L256-261), `resources/js/units-index.js` (~L326-331)
- `public/build` (rebuilt)
- tests appended

- [ ] **Step 1: Failing tests (server side).**
  - The medicines index root renders `data-can-edit="0" data-can-delete="0"` for a `view medicines`-only user, and `"1"` for a user with `edit medicines` / `delete medicines` (or `manage pharmacy`).
  - The same for units.
- [ ] **Step 2: Red. Step 3: Implement.**
  - Blade emits the flags from `canany(['edit <X>','manage pharmacy'])` / `canany(['delete <X>','manage pharmacy'])`.
  - The JS reads them once from the root's `dataset` and renders the Edit `<a>` / Delete `<form>` only when the flag is `'1'`. If neither renders, the actions cell renders empty (no stray separators).
- [ ] **Step 4: Build.** `npm run build`, then run `tests/Feature/ViteBuildFreshnessGateTest.php` and `tests/Feature/ViteConfigInputsTest.php`. Commit the rebuilt assets only if the repo tracks `public/build` (check with `git ls-files public/build | head`; follow the existing practice).
- [ ] **Step 5: Green. Step 6: Commit:** `fix: render medicine and unit row actions only for permitted users`

### Task 15: README deploy note for `create prescriptions`

**Files:** `README.md`

- [ ] **Step 1:** Add **"Required Deploy Step: `create prescriptions` (RBAC Wave 1 completion, D1)"** after the `manage theatres` section, in the same structure.
  - **Hard requirement:** run it in the same deploy action. From the moment the routes are live, **every Doctor and Nurse loses prescribing** until it has run.
  - The `--dry-run` command and its expected output ("would update 2 role(s)" per tenant), plus: **read the `[report only]` lines.** Each names a custom role that prescribes today through `edit visits` and will lose it. Decide on those roles **before** go-live, because the command never grants them.
  - The real command.
  - The post-check (Doctor and Nurse hold `create prescriptions` in every tenant).
  - A one-line pointer that both Wave 1-completion backfills (`manage theatres`, `create prescriptions`) belong to the same release and are run in the same deploy action.
  - "Not yet applied locally" (updated in Task 18).
- [ ] **Step 2: Commit:** `docs: require the create prescriptions backfill in the same deploy action`

- [ ] **Step 3: Phase 3 report.**
  - SHAs; red/green per task.
  - **The full pharmacy route → permission table as implemented** (77 live routes + the 5 removed).
  - **The side-effect proof list** (purchases, stock, POS, dispense, prescribing, catalog deletes).
  - The safety-report test output.
  - **Every pre-existing test edited,** with before/after fixture and reason.
  - The JS build and freshness gate result.
  - Both README section texts.
  - Main tree unchanged.

---

## Phase 4: Closing gate

### Task 16: Regression, review, dry runs, real-render check

- [ ] **Step 1: Focused suites.**

```bash
vendor/bin/pest tests/Feature/Permissions tests/Feature/Accounting tests/Feature/OT tests/Feature/Pharmacy tests/Feature/Visits tests/Feature/Commands tests/Feature/Module tests/Feature/Navigation tests/Feature/DoctorShare tests/Unit --no-coverage
```

(If memory is a problem, run each directory separately.)

- [ ] **Step 2: Full chunked regression.** One Pest process per `tests/Feature/*` entry plus `tests/Unit`, run sequentially. The per-chunk `Tests:` lines go to `<scratchpad>/wave1c-chunks/_summary.txt`.
  - **Pass criterion:** failures exactly match the 34-test baseline **by file and count**.
  - Anything else is a bug: write a failing test, fix, commit, re-run Steps 1–2.

- [ ] **Step 3: Diff review** (`git diff origin/main...HEAD -- app resources routes database README.md`):
  - only Spatie checks; `grep -n "hasRole\|hasAnyRole\|role_or_permission"` in touched files finds nothing new;
  - **no any-of across CRUD verbs remains** in the three modules. The only multi-permission strings allowed are the action + confirmed superset/coarse-read combinations, and `ot.check-conflicts`. Check with a route dump (Step 4);
  - the PAC/checklist/consumables/sterilization groups are untouched;
  - the webhook and visit lab/imaging routes are untouched;
  - all redirects go through the trait (no duplicated helper);
  - no dead code; no unescaped output.

- [ ] **Step 4: Route dump proof.** `php artisan route:list --json` filtered to the accounting, OT and pharmacy route names. Write it to the scratchpad and diff it against the design tables. Every row must match; attach the table to the report.

- [ ] **Step 5: Backfill dry runs** (read-only, real local tenants, from the worktree):
  - `php artisan rbac:backfill-manage-theatres --dry-run`: expect "would update 2 role(s)" × 7.
  - `php artisan rbac:backfill-create-prescriptions --dry-run`: expect "would update 2 role(s)" × 7 and **no** `[report only]` lines locally.
  - Before and after, run a read-only SQL probe of `permissions` / `role_has_permissions` row counts per tenant. They must be identical.

- [ ] **Step 6: Re-run the real-role scan** (read-only SQL, all 7 tenants) and confirm the effect tables in the three designs still hold:
  - Super Admin and HA unchanged;
  - HA lacks `close fiscal years`;
  - Doctor and Nurse hold only `edit visits` in the prescription area (pre-backfill);
  - no unexpected roles.

  Report any drift before proceeding.

- [ ] **Step 7: Real-render check** (the in-app browser, against the worktree served locally or via `php artisan serve` on a spare port, logged in as test users created **in the test environment only**; never change real tenant users):
  - the medicines and units index rows as a view-only user (no Edit/Delete) and as an editor (both present);
  - the surgery show page as `view surgeries`-only (no action buttons).

  Take screenshots. If the browser path isn't available, say so explicitly rather than skipping silently.

- [ ] **Step 8: Final report:**
  - HEAD SHA and per-phase SHAs;
  - the full chunked-suite table against the baseline (by file);
  - the route dump table;
  - the dry-run outputs and the before/after probe;
  - the role-scan result;
  - real-render screenshots;
  - all edited pre-existing tests;
  - any non-Spatie checks found/converted;
  - the explicit statement: **not merged, not pushed, awaiting instruction.**

### Task 17: Worktree hygiene check (end of execution)

- [ ] Confirm the main tree's `git status --short` and `git stash list` match the Phase 0 files exactly. `git worktree list` shows the worktree (kept until merge).

### Task 18: After human approval only (not part of execution)

1. Fast-forward `main` to `feat/rbac-wave1-completion`, push, and confirm `origin/main` matches.
2. **With explicit approval,** run `php artisan rbac:backfill-manage-theatres` and `php artisan rbac:backfill-create-prescriptions` for real. Without them, admins lose theatre create/edit, and doctors and nurses lose prescribing.
3. Read-only verification: exactly Super Admin and HA hold `manage theatres`; Doctor and Nurse (plus the admins and Pharmacist) hold `create prescriptions`. In all 7 tenants.
4. Update both README sections' "applied locally" lines, and add the local execution record to this plan. Commit and push.
5. Remove the worktree (`git worktree remove ../saasy-rbac-wave1c`, `git worktree prune`). Delete the merged branch with `git branch -d`. Confirm the main tree is unchanged.

**Deployment hard requirement (every environment beyond this machine):** run both backfills (after confirming each `--dry-run`, and reading the prescriptions `[report only]` lines) **in the same deploy action** as this code. Not before (the commands don't exist yet), and not after (from the moment the routes are live, admins get 403 on theatre create/edit and every Doctor/Nurse loses prescribing). This is tracked in `README.md` → Deployment.

---

## Spec coverage self-check

| Spec item | Task |
|---|---|
| Accounting §1 mapping (AC-2, AC-3), no registry/seeder/backfill | 1 |
| Accounting §2 AC-1 (HA loses close) | 1 (seeded-HA test), 16.6 |
| Accounting §3 AD-1 + shared trait | 2 |
| Accounting §4 UI | 3 |
| OT §1 `manage theatres` registry/seeder/backfill (OT-1) | 4 |
| OT §2 mapping incl. OT-2 and the check-conflicts exception | 5 |
| OT §4 AD-1 | 6 |
| OT §5 UI, back-links, JS safety | 7 |
| OT README deploy note | 7 |
| Pharmacy §2.1–2.2 catalog + instructions, dead routes | 9 |
| Pharmacy §2.3–2.4 suppliers, purchases, inventory | 10 |
| Pharmacy §2.5 POS per AC-2; §2.6 prescriptions (D1) + `can_prescribe` | 11 |
| Pharmacy §4 PH-1 backfill + seeder + **safety report (explicit test)** | 8 |
| Pharmacy §5 AD-1 | 12 |
| Pharmacy §6 Blade + sidebar | 13 |
| Pharmacy §6 JS row actions + build | 14 |
| Pharmacy §7 README deploy note | 15 |
| Pharmacy §8 existing-test fixture updates | 11 |
| Before/after + side-effect proof per route (D6) | 1, 5, 9, 10, 11 (+ 2, 6, 12 for redirects) |
| No real-data writes; backfills gated; same-deploy-action requirement | Global, 16, 18, README |
| Chunked regression against the named baseline | 16 |
| No merge until confirmed | 16, 18 |
| AC-4 (Fiscal Years sidebar) | **excluded by decision** (follow-up memory) |

## Placeholder scan

- Line numbers are from `main` @ `6d891b3`. Tasks 2, 6 and 12 require verifying them in the worktree before editing, and reporting any drift.
- Fixture sources are named per task (existing test files to copy from).
- No step defers a decision. The only conditional work is fixing regressions found in Task 16, with a defined procedure, and the README "applied locally" lines filled in Task 18.

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-10-06-rbac-wave1-completion-accounting-ot-pharmacy.md` (force-added to the branch in Task 0).

**Do not begin execution until the human confirms this plan.**

When confirmed, two execution options:
1. **Subagent-Driven (recommended):** a fresh subagent per task with review between tasks (`superpowers:subagent-driven-development`). Every subagent prompt repeats: work only in `saasy-rbac-wave1c`, **never `git stash`**, never touch the main tree, no push. Human reports at the end of Phases 1, 2, 3 and 4.
2. **Inline Execution:** in this session via `superpowers:executing-plans`, with the same checkpoints.
