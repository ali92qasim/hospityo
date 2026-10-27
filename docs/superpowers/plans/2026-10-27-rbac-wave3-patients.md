# RBAC Wave 3: Patients Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scope every Patients HTTP route to exactly one Spatie action permission; add soft delete for SA + HA (`delete patients`); trash list for deleters; restore for Super Admin only (`restore patients`); gate UI; AD-1 redirects; deploy backfill for HA `delete patients`.

**Architecture:**
- Spatie `permission:` middleware per route (Wave 1/2 pattern). No role-name access checks.
- SoftDeletes on `Patient`; `destroy` soft-deletes; no `forceDelete`.
- AD-1 via existing `App\Http\Controllers\Concerns\RedirectsAfterWrite`.
- DataTables row Edit/Delete gated with `data-can-edit` / `data-can-delete` (pharmacy catalog pattern).
- Idempotent `rbac:backfill-delete-patients` grants `delete patients` to Hospital Administrator only.

**Tech Stack:** Laravel 12, Spatie laravel-permission, Spatie multitenancy, Blade, Vite/JS (patients-index), Pest.

**Spec:** `docs/superpowers/specs/2026-10-27-rbac-wave3-patients-design.md` (PAT-1…PAT-9).

## Confirmed decisions

| # | Decision |
|---|---|
| PAT-1 | One permission per Patients route; no CRUD any-of. |
| PAT-2 | Soft delete only; no hard delete. |
| PAT-3 | Soft delete = `delete patients` for SA + HA (seeder + backfill add HA grant). |
| PAT-4 | Restore = `restore patients`, SA only. |
| PAT-5 | Trash list = `delete patients`; Restore button = `restore patients`. |
| PAT-6 | AD-1 on store/update/destroy/restore; keep save-and-add-another → create. |
| PAT-7 | `@can` / `data-can-*` on action controls. |
| PAT-8 | `api.patients.search` → `view patients`. |
| PAT-9 | D2 + Spatie-only. |
| D6 | Deny / allow / side-effect proof per route. |

## Global Constraints

- **Worktree, not the main tree.**
  - Main tree: `C:/Users/Qasim/Herd/saasy` on `fix/bug-fixes` with WIP and **4 stashes**. **Never** checkout, add, commit, or stash there.
  - Create/use: `C:/Users/Qasim/Herd/saasy-rbac-wave3-patients` on branch `feat/rbac-wave3-patients` from `origin/main`.
- **Never run `git stash` in the worktree.** Worktrees share the stash list with the main repo.
- **No merge/push to `main` until explicit human approval** after the closing gate.
- **TDD:** failing test first; confirm red for the right reason; implement; green; ≥1 commit per task.
- **Pest:** Feature tests already extend `Tests\TestCase` + `RefreshDatabase` via `tests/Pest.php`. **Do not** add `uses(Tests\TestCase::class, …)` (duplicate uses fails on current Pest).
- **Spatie only** in access paths. Role names only as backfill target selectors.
- **No real tenant data writes during execution.** Backfill `--dry-run` only in closing gate; real run after merge + approval.
- **CheckModule** / sidebar module entitlement unchanged.
- Register `GET patients/trashed` **before** `patients/{patient}` routes.

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/tenant/*_add_soft_deletes_to_patients_table.php` | **Create** `deleted_at` |
| `app/Models/Patient.php` | Add `SoftDeletes` |
| `app/Support/PermissionRegistry.php` | `restore patients` |
| `database/seeders/RolePermissionSeeder.php` | PERMISSIONS += restore; HA += `delete patients` |
| `app/Console/Commands/BackfillDeletePatientsPermission.php` | **Create** |
| `routes/web.php` | Per-route patients middleware + trashed + restore |
| `app/Http/Controllers/PatientController.php` | destroy, trashed, restore; RedirectsAfterWrite; withTrashed resolve |
| `resources/views/admin/patients/index.blade.php` | `@can` create/trash; data-can-edit/delete |
| `resources/views/admin/patients/show.blade.php` | `@can` edit/delete |
| `resources/views/admin/patients/trashed.blade.php` | **Create** trash UI |
| `resources/js/patients-index.js` | Conditional edit/delete row actions |
| `README.md` | Deploy step for backfill |
| `tests/Feature/Permissions/PatientsRouteScopingTest.php` | **Create** |
| `tests/Feature/Commands/BackfillDeletePatientsPermissionTest.php` | **Create** |
| `tests/Unit/PermissionRegistryTest.php` | Expect `restore patients` if asserted |

---

## Phase 0: Worktree + docs

### Task 0: Worktree, baselines, force-add docs

**Files:** design + this plan under `docs/superpowers/` (gitignored — force-add).

- [ ] **Step 1:** Record main-tree baseline (scratch only; do not modify main tree):

```bash
cd C:/Users/Qasim/Herd/saasy
git status --short
git stash list
# expect WIP on fix/bug-fixes; 4 stash entries
```

- [ ] **Step 2:** Create worktree from `origin/main`:

```bash
cd C:/Users/Qasim/Herd/saasy
git fetch origin main
git worktree add -b feat/rbac-wave3-patients ../saasy-rbac-wave3-patients origin/main
cd ../saasy-rbac-wave3-patients
git branch --unset-upstream
composer install
```

If design/plan only exist in `saasy-wave2-dead-show`, copy them into the new worktree `docs/superpowers/` before force-add.

- [ ] **Step 3:** Force-add docs; commit:

```bash
git add -f docs/superpowers/specs/2026-10-27-rbac-wave3-patients-design.md docs/superpowers/plans/2026-10-27-rbac-wave3-patients.md
git commit -m "docs: add RBAC wave 3 patients design and plan"
```

- [ ] **Step 4:** Confirm `vendor` resolves inside the worktree path; note HEAD for Phase reports.

---

## Phase 1: SoftDeletes + registry/seeder + backfill

### Task 1: SoftDeletes on patients

**Files:**
- Create: `database/migrations/tenant/YYYY_MM_DD_HHMMSS_add_soft_deletes_to_patients_table.php`
- Modify: `app/Models/Patient.php`
- Test: fold into `PatientsRouteScopingTest` in Task 4 (soft-delete behavior). For this task, a minimal model/migration proof is enough via artisan migrate in later tests.

**Interfaces:**
- Produces: `Patient` uses `SoftDeletes`; column `patients.deleted_at` nullable timestamp.

- [ ] **Step 1:** Create tenant migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
```

- [ ] **Step 2:** Update model:

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, Auditable, UsesTenantConnection, SoftDeletes;
    // ...
}
```

- [ ] **Step 3:** Commit:

```bash
git add database/migrations/tenant/*_add_soft_deletes_to_patients_table.php app/Models/Patient.php
git commit -m "feat: add soft deletes to patients"
```

### Task 2: Registry + seeder (`restore patients`, HA `delete patients`)

**Files:**
- Modify: `app/Support/PermissionRegistry.php` (patients group)
- Modify: `database/seeders/RolePermissionSeeder.php`
- Modify: `tests/Unit/PermissionRegistryTest.php` if it lists patients permissions explicitly

**Interfaces:**
- Produces: permission name `restore patients`; HA role includes `delete patients`.

- [ ] **Step 1:** If `PermissionRegistryTest` asserts patients permission list, write failing expectation for `restore patients` first; run red.

- [ ] **Step 2:** Registry:

```php
'patients' => [
    'view patients',
    'create patients',
    'edit patients',
    'delete patients',
    'restore patients',
],
```

- [ ] **Step 3:** Seeder `PERMISSIONS` array: add `'restore patients'` next to the other patient permissions.

- [ ] **Step 4:** HA `ROLE_PERMISSIONS` line for patients — change to:

```php
'view patients', 'create patients', 'edit patients', 'delete patients',
```

Do **not** add `restore patients` to HA or any non–Super Admin role.

- [ ] **Step 5:** Run unit registry test green; commit:

```bash
git commit -m "feat: register restore patients and grant HA delete patients"
```

### Task 3: Backfill command + tests

**Files:**
- Create: `app/Console/Commands/BackfillDeletePatientsPermission.php`
- Create: `tests/Feature/Commands/BackfillDeletePatientsPermissionTest.php`

**Interfaces:**
- Consumes: Spatie Permission/Role on tenant connection; `Tenant` loop pattern from `BackfillChangeBedStatusPermission`.
- Produces: `php artisan rbac:backfill-delete-patients {--dry-run}`
- Constants: `PERMISSION_NAME = 'delete patients'`; `TARGET_ROLES = ['Hospital Administrator']`.

Model on `BackfillChangeBedStatusPermission` / `BackfillManageTheatresPermission`:
- Dry-run: create nothing, grant nothing, leave permission cache untouched.
- Real run: `Permission::findOrCreate`, grant missing targets, `$registrar->forgetCachedPermissions()`.
- Safety report (both modes): roles that hold any of `view|create|edit|delete|restore patients` but are not Super Admin / Hospital Administrator and lack `delete patients` → print `[report only]` (never grant). Adjust exclusion list to `['Super Admin', 'Hospital Administrator']`.

- [ ] **Step 1:** Write failing Pest tests covering:
  - dry-run updates zero roles / does not create permission row if absent
  - real run grants HA only; idempotent second run
  - report-only line for a custom role with `view patients` only
  - does not grant `restore patients`

- [ ] **Step 2:** Run `php vendor/bin/pest tests/Feature/Commands/BackfillDeletePatientsPermissionTest.php` — expect FAIL (command missing).

- [ ] **Step 3:** Implement command (copy structure from `BackfillChangeBedStatusPermission.php`; replace permission/target/report sets as above). Ensure command is auto-discovered.

- [ ] **Step 4:** Green; commit:

```bash
git commit -m "feat: backfill delete patients for hospital administrators"
```

---

## Phase 2: Routes, controller, AD-1

### Task 4: Failing `PatientsRouteScopingTest` (D6 + soft delete + trash/restore)

**Files:**
- Create: `tests/Feature/Permissions/PatientsRouteScopingTest.php`

**Interfaces:**
- Helpers: `patScopeTenant()`, `patScopeUser(array $perms)`, `patScopePatient()`, `patScopePayload($route)`, `patScopeRequest(...)`.
- Grep `tests/` before naming helpers — must be unique.
- `beforeEach`: `withoutMiddleware` EnsureTenantActive + SetTenantTimezone; `withoutVite()`; migrate tenant path; mock tenant `hasModule('patients')`; create fixture patient.

**Patient fixture** (no factory):

```php
Patient::create([
    'name' => 'Pat Scope Fixture',
    'gender' => 'male',
    'age' => 30,
    'phone' => '03001234567',
]);
```

**Store/update payload:**

```php
[
    'name' => 'Pat Scope Name',
    'gender' => 'female',
    'age' => 25,
    'phone' => '03007654321',
    'marital_status' => 'single',
]
```

- [ ] **Step 1:** Write the suite **before** route changes so escalation denies are red for the right reason where any-of still allows writes.

Include at least:

1. **Escalation denies (dataset):** wrong-verb holder → 403 after fix; before fix several will be 200/302 — commit tests first, then fix routes in Task 5.
   - Examples: `create patients` only on `patients.index` / `patients.data` / `patients.show` / `patients.history` / `api.patients.search`
   - `view patients` only on `patients.create` / store / edit / update / destroy / trashed / restore
   - `edit patients` only on store / destroy
   - `delete patients` only on restore (HA-shaped)

2. **Allow exact permission (dataset):** each route with exactly its permission returns non-403 (200/302 as appropriate).

3. **Side effects:** view-only store creates no patient; view-only update leaves name unchanged; view-only destroy leaves `deleted_at` null.

4. **Soft delete:** `delete patients` destroy → `deleted_at` set; patient absent from `patients.data` / search; present on `patients.trashed`.

5. **HA-shaped user** (`view/create/edit/delete patients`, no restore): destroy + trashed OK; restore → 403; still trashed.

6. **Restore user** (`restore patients` + `view patients` or delete as needed to open trash): restore clears `deleted_at`; appears on index/data again.

7. **AD-1:** write-only create (`create patients` only) store → lands on create (or fallback), success flash; write+view → index.

8. **UI (HTML):** view-only index lacks Add New / data-can-edit / data-can-delete / trash link; create holder sees Add New; delete holder sees trash link + data-can-delete; restore holder sees Restore on trashed page.

9. **Route::has** `patients.trashed` and `patients.restore` true after Task 5 (write asserts now; they fail until routes exist — OK to add in same commit as Task 5 if preferred, but keep TDD: assert Route::has after implementing routes).

- [ ] **Step 2:** Run:

```bash
php vendor/bin/pest tests/Feature/Permissions/PatientsRouteScopingTest.php --no-coverage
```

Expected: failures for missing routes/methods and/or wrong status codes — not fixture/setup errors.

- [ ] **Step 3:** Commit failing tests:

```bash
git commit -m "test: add patients route scoping coverage (red)"
```

### Task 5: Scope routes + implement destroy/trashed/restore + AD-1

**Files:**
- Modify: `routes/web.php` (patients block ~L295–299 and `api.patients.search`)
- Modify: `app/Http/Controllers/PatientController.php`

**Interfaces:**
- Consumes: `RedirectsAfterWrite::redirectAfterWrite(...)`
- Produces: controller methods `destroy`, `trashed`, `restore`

- [ ] **Step 1:** Replace patients routes with (order matters):

```php
Route::get('/patients/data', [PatientController::class, 'data'])
    ->name('patients.data')
    ->middleware('permission:view patients');

Route::get('patients/trashed', [PatientController::class, 'trashed'])
    ->name('patients.trashed')
    ->middleware('permission:delete patients');

Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])
    ->name('patients.restore')
    ->middleware('permission:restore patients')
    ->withTrashed();

Route::resource('patients', PatientController::class)
    ->middlewareFor(['index', 'show'], 'permission:view patients')
    ->middlewareFor(['create', 'store'], 'permission:create patients')
    ->middlewareFor(['edit', 'update'], 'permission:edit patients')
    ->middlewareFor('destroy', 'permission:delete patients');

Route::get('patients/{patient}/history', [PatientController::class, 'history'])
    ->name('patients.history')
    ->middleware('permission:view patients');

// later in file:
Route::get('api/patients/search', [PatientController::class, 'search'])
    ->name('api.patients.search')
    ->middleware('permission:view patients');
```

If `middlewareFor` + `withTrashed` on a separate restore route is awkward, register restore as shown (explicit) and keep resource without custom restore. Prefer explicit restore route as in the design.

Note: `Route::resource(...)->withTrashed()` is not required on the whole resource; only restore needs trashed models. For `destroy`, default binding is fine (non-deleted). Soft-deleted show/edit should 404 via default binding.

- [ ] **Step 2:** Controller — add trait; implement methods; switch store/update success paths to AD-1 (preserve save-and-add-another):

```php
use App\Http\Controllers\Concerns\RedirectsAfterWrite;

class PatientController extends Controller
{
    use HandlesErrors, RedirectsAfterWrite;

    // store: after create, if save_and_add_another → patients.create with flash;
    // else:
    // return $this->redirectAfterWrite('patients.index', [], ['view patients'], $message, 'patients.create');

    // update:
    // return $this->redirectAfterWrite('patients.index', [], ['view patients'], $message, 'patients.edit', ['patient' => $patient]);

    public function destroy(Patient $patient): RedirectResponse
    {
        $name = $patient->name;
        $patient->delete();

        return $this->redirectAfterWrite(
            'patients.index',
            [],
            ['view patients'],
            "Patient {$name} moved to trash.",
            null,
        );
    }

    public function trashed(): View
    {
        $patients = Patient::onlyTrashed()->orderByDesc('deleted_at')->paginate(20);

        return view('admin.patients.trashed', compact('patients'));
    }

    public function restore(int $patient): RedirectResponse
    {
        $model = Patient::onlyTrashed()->findOrFail($patient);
        $model->restore();

        return $this->redirectAfterWrite(
            'patients.index',
            [],
            ['view patients'],
            "Patient {$model->name} restored.",
            'patients.trashed',
        );
    }
}
```

Use `findOrFail` on `onlyTrashed()` so restore does not depend on implicit binding quirks. Match route param name `{patient}`.

- [ ] **Step 3:** Create minimal `trashed.blade.php` (table of trashed patients; Restore form POST to `patients.restore` wrapped in `@can('restore patients')`; Back to patients link). Style like other admin index pages (existing Tailwind/medical classes).

- [ ] **Step 4:** Re-run `PatientsRouteScopingTest` — expect route/permission/soft-delete/AD-1 cases green; UI cases that need Blade/JS flags may still fail until Task 6.

- [ ] **Step 5:** Commit:

```bash
git commit -m "feat: scope patient routes and soft-delete with trash restore"
```

---

## Phase 3: UI gates

### Task 6: Blade + DataTables flags

**Files:**
- Modify: `resources/views/admin/patients/index.blade.php`
- Modify: `resources/views/admin/patients/show.blade.php`
- Modify: `resources/js/patients-index.js`
- Possibly: `resources/views/admin/patients/trashed.blade.php` (if Restore gate incomplete)

- [ ] **Step 1:** Index root div — add flags:

```blade
<div id="patients-index"
     @can('create visits') data-can-create-visits="1" @endcan
     @can('edit patients') data-can-edit="1" @endcan
     @can('delete patients') data-can-delete="1" @endcan
     ...
>
```

- [ ] **Step 2:** Wrap Add New Patient in `@can('create patients')`. Add Trash link `@can('delete patients')` → `route('patients.trashed')`.

- [ ] **Step 3:** In `patients-index.js` actions column, read `dataset.canEdit` / `dataset.canDelete` / csrf; only render Edit when canEdit; only render DELETE form (method spoof) when canDelete; confirm copy: soft-delete wording (“Move to trash”), not permanent delete.

- [ ] **Step 4:** Show page — wrap Edit and add Soft-delete form in `@can` blocks.

- [ ] **Step 5:** Re-run full `PatientsRouteScopingTest` — all green.

- [ ] **Step 6:** Commit:

```bash
git commit -m "fix: gate patient create edit delete and trash UI"
```

### Task 7: README deploy step

**Files:**
- Modify: `README.md` (after Wave 2 backfill sections)

- [ ] **Step 1:** Add:

```markdown
### Required Deploy Step: `delete patients` (RBAC Wave 3, Patients)

> **Hard requirement.** Same deploy action as the Wave 3 Patients code.
> Grants `delete patients` to Hospital Administrator so soft-delete/trash works.
> Super Admin receives `restore patients` via permission seeder sync (`tenants:sync-permissions` / RolePermissionSeeder) — confirm SA can restore after deploy.

```bash
php artisan rbac:backfill-delete-patients --dry-run
php artisan rbac:backfill-delete-patients
php artisan tenants:sync-permissions
```
```

- [ ] **Step 2:** Commit:

```bash
git commit -m "docs: require delete-patients backfill for Wave 3 Patients deploy"
```

---

## Phase 4: Closing gate

### Task 8: Regression + dry-run + report (no merge)

- [ ] **Step 1:** Run:

```bash
php vendor/bin/pest tests/Feature/Permissions/PatientsRouteScopingTest.php --no-coverage
php vendor/bin/pest tests/Feature/Commands/BackfillDeletePatientsPermissionTest.php --no-coverage
php vendor/bin/pest tests/Feature/Patients --no-coverage
php vendor/bin/pest tests/Unit/PermissionRegistryTest.php --no-coverage
```

Fix any failures caused by this wave (not the known auth baseline elsewhere).

- [ ] **Step 2:** Optional chunked Feature scan if time allows; report new failures vs known baseline.

- [ ] **Step 3:** Dry-run only:

```bash
php artisan rbac:backfill-delete-patients --dry-run
```

- [ ] **Step 4:** Final report to human: HEAD SHA; test results; dry-run output; **not merged, not pushed, awaiting instruction.**

- [ ] **Step 5 (human-gated later):** After approval — merge/rebase to main, push, run real backfill + `tenants:sync-permissions`, remove worktree. Do **not** do this in Task 8.

---

## Spec coverage checklist

| Spec item | Task |
|---|---|
| PAT-1 route scoping | 4–5 |
| PAT-2 soft delete / no force delete | 1, 5 |
| PAT-3 HA delete + backfill | 2–3, 7 |
| PAT-4 restore SA-only | 2, 4–5 |
| PAT-5 trash vs restore UI/middleware | 5–6 |
| PAT-6 AD-1 | 5 |
| PAT-7 UI gates | 6 |
| PAT-8 search gated | 5 |
| PAT-9 Spatie-only | all |
| README deploy | 7 |
| Closing gate | 8 |

## Execution handoff

Work only in `saasy-rbac-wave3-patients`. Never touch main-tree WIP/stashes. Never stash.
