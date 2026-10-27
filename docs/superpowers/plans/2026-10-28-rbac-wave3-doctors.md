# RBAC Wave 3: Doctors Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Scope every Doctors admin HTTP route to exactly one Spatie action permission; soft-delete doctors for SA + HA (`delete doctors`) with login deactivate (no user hard-delete); SA-only trash + restore (`restore doctors`) with login reactivate; replace `hasRole('Doctor')` on assignments with `view doctor assignments`; AD-1 + UI gates; two deploy backfills.

**Architecture:**
- Spatie `permission:` middleware per route (Wave 1/2/Patients pattern). No role-name access checks.
- SoftDeletes on `Doctor`; destroy soft-deletes + `status=inactive` + `TenantUser::unregister`; restore reverses that. No `forceDelete` / no `$user->delete()`.
- AD-1 via existing `RedirectsAfterWrite`.
- DataTables row Edit/Delete gated with `data-can-edit` / `data-can-delete`.
- Related `belongsTo(Doctor::class)->withTrashed()`; `doctor_id` exists rules `whereNull('deleted_at')`.
- Backfills: `rbac:backfill-delete-doctors` (HA), `rbac:backfill-view-doctor-assignments` (Doctor role).

**Tech Stack:** Laravel 12, Spatie laravel-permission, Spatie multitenancy, Blade, Vite/JS (`doctors-index.js`), Pest.

**Spec:** `docs/superpowers/specs/2026-10-28-rbac-wave3-doctors-design.md` (DOC-1…DOC-11).

## Confirmed decisions

| # | Decision |
|---|---|
| DOC-1 | One permission per admin doctors route; email-available keeps create\|edit. |
| DOC-2 | Soft delete only; do not hard-delete linked User. |
| DOC-3 | Soft delete = `delete doctors` for SA + HA (seeder + backfill). |
| DOC-4 | Restore = `restore doctors`, SA only. |
| DOC-5 | Trash list = `restore doctors` (SA only); HA cannot open trash. |
| DOC-6 | Destroy: inactive + unregister TenantUser; restore: active + register. |
| DOC-7 | Assignments: `view doctor assignments` for Doctor role (+ backfill); no hasRole. |
| DOC-8 | AD-1 on store/update/destroy/restore. |
| DOC-9 | `@can` / `data-can-*` UI gates. |
| DOC-10 | Related doctor() withTrashed + exists exclude trashed. |
| DOC-11 | D2 + Spatie-only. |
| D6 | Deny / allow / side-effect proof per route. |

## Global Constraints

- **Worktree, not the main tree.**
  - Main tree: `C:/Users/Qasim/Herd/saasy` on `fix/bug-fixes` with WIP and **4 stashes**. **Never** checkout, add, commit, or stash there.
  - Create: `C:/Users/Qasim/Herd/saasy-rbac-wave3-doctors` on branch `feat/rbac-wave3-doctors` from `origin/main` (Patients already on `main`).
- **Never run `git stash` in the worktree.**
- **No merge/push to `main` until explicit human approval** after the closing gate.
- **TDD:** failing test first; red for the right reason; implement; green; ≥1 commit per task.
- **Pest:** Do **not** add `uses(Tests\TestCase::class, …)` (Pest.php already extends Feature).
- **Spatie only** in access paths. Role names only as backfill target selectors.
- **No real tenant data writes during execution.** Backfill `--dry-run` only in closing gate.
- Register `GET doctors/trashed` **before** `doctors/{doctor}`.
- Status vocabulary: `active` \| `inactive` only.

## File Structure

| File | Responsibility |
|---|---|
| `database/migrations/tenant/*_add_soft_deletes_to_doctors_table.php` | **Create** `deleted_at` |
| `app/Models/Doctor.php` | SoftDeletes; withTrashed max id; (optional helpers) |
| `app/Support/PermissionRegistry.php` | `restore doctors`, `view doctor assignments` |
| `database/seeders/RolePermissionSeeder.php` | PERMISSIONS; HA += delete; Doctor += assignments |
| `app/Console/Commands/BackfillDeleteDoctorsPermission.php` | **Create** |
| `app/Console/Commands/BackfillViewDoctorAssignmentsPermission.php` | **Create** |
| `routes/web.php` | Per-route doctors middleware + trashed/restore + assignments gate |
| `app/Http/Controllers/DoctorController.php` | Soft destroy/trashed/restore; AD-1; assignments without hasRole |
| Models with `belongsTo(Doctor::class)` | `->withTrashed()` |
| FormRequests / OT with `doctor_id` exists | `whereNull('deleted_at')` |
| `resources/views/admin/doctors/{index,show,trashed}.blade.php` | `@can` / trash UI |
| `resources/js/doctors-index.js` | Conditional edit/delete; Move to trash |
| `README.md` | Deploy steps |
| `tests/Feature/Permissions/DoctorsRouteScopingTest.php` | **Create** |
| `tests/Feature/Commands/BackfillDeleteDoctorsPermissionTest.php` | **Create** |
| `tests/Feature/Commands/BackfillViewDoctorAssignmentsPermissionTest.php` | **Create** |
| `tests/Feature/Doctors/SoftDeletedDoctorRelationsTest.php` | **Create** (relation + exists) |
| `tests/Unit/PermissionRegistryTest.php` | Update doctors group list |

---

## Phase 0: Worktree + docs

### Task 0: Worktree, baselines, force-add docs

**Files:** design + this plan under `docs/superpowers/` (force-add).

- [ ] **Step 1:** Record main-tree baseline (scratch only under the new worktree `.superpowers/sdd/`):

```bash
cd C:/Users/Qasim/Herd/saasy
git status --short
git stash list
```

- [ ] **Step 2:** Create worktree from `origin/main`:

```bash
cd C:/Users/Qasim/Herd/saasy
git fetch origin main
git worktree add -b feat/rbac-wave3-doctors ../saasy-rbac-wave3-doctors origin/main
cd ../saasy-rbac-wave3-doctors
git branch --unset-upstream
composer install
```

Copy design + this plan from wherever they currently live (e.g. `saasy-rbac-wave3-patients/docs/superpowers/…`) into the new worktree if needed.

- [ ] **Step 3:** Force-add docs; commit:

```bash
git add -f docs/superpowers/specs/2026-10-28-rbac-wave3-doctors-design.md docs/superpowers/plans/2026-10-28-rbac-wave3-doctors.md
git commit -m "docs: add RBAC wave 3 doctors design and plan"
```

- [ ] **Step 4:** Confirm vendor path is inside `saasy-rbac-wave3-doctors`; note HEAD.

---

## Phase 1: SoftDeletes + registry/seeder + backfills

### Task 1: SoftDeletes on doctors

**Files:**
- Create: `database/migrations/tenant/YYYY_MM_DD_HHMMSS_add_soft_deletes_to_doctors_table.php`
- Modify: `app/Models/Doctor.php`

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, Auditable, UsesTenantConnection, SoftDeletes;

    // creating callback:
    $doctor->doctor_no = 'DR' . str_pad(
        (Doctor::withTrashed()->max('id') ?? 0) + 1,
        4,
        '0',
        STR_PAD_LEFT
    );
}
```

Migration: `$table->softDeletes();` / `dropSoftDeletes()`.

- [ ] **Step 1:** Add migration + SoftDeletes + withTrashed max id.
- [ ] **Step 2:** Commit: `feat: add soft deletes to doctors`

### Task 2: Registry + seeder

**Files:**
- Modify: `app/Support/PermissionRegistry.php` doctors group
- Modify: `database/seeders/RolePermissionSeeder.php`
- Modify: `tests/Unit/PermissionRegistryTest.php` (assert doctors list includes new names)

```php
'doctors' => [
    'view doctors',
    'create doctors',
    'edit doctors',
    'delete doctors',
    'restore doctors',
    'view doctor assignments',
],
```

HA: `'view doctors', 'create doctors', 'edit doctors', 'delete doctors'`  
Doctor role: add `'view doctor assignments'`  
Do **not** grant `restore doctors` to HA or Doctor.

- [ ] **Step 1:** TDD registry expectation if asserting list; implement; green.
- [ ] **Step 2:** Commit: `feat: register restore doctors and view doctor assignments`

### Task 3: Backfill commands + tests

**Files:**
- Create: `app/Console/Commands/BackfillDeleteDoctorsPermission.php`
- Create: `app/Console/Commands/BackfillViewDoctorAssignmentsPermission.php`
- Create: matching Feature tests under `tests/Feature/Commands/`

Model on `BackfillDeletePatientsPermission` / `BackfillChangeBedStatusPermission`:
- `rbac:backfill-delete-doctors {--dry-run}` — `PERMISSION_NAME = 'delete doctors'`; `TARGET_ROLES = ['Hospital Administrator']`
- `rbac:backfill-view-doctor-assignments {--dry-run}` — `PERMISSION_NAME = 'view doctor assignments'`; `TARGET_ROLES = ['Doctor']`
- Dry-run writes nothing; real run findOrCreate + grant + forget cache
- Safety report for unexpected holders (exclude SA / target role by name)

- [ ] **Step 1:** Write failing tests (dry-run, real, idempotent, never grants restore, report-only).
- [ ] **Step 2:** Implement both commands; green.
- [ ] **Step 3:** Commit: `feat: backfill delete doctors and view doctor assignments`

---

## Phase 2: Routes, controller, assignments, AD-1

### Task 4: Failing `DoctorsRouteScopingTest` (RED)

**Files:**
- Create: `tests/Feature/Permissions/DoctorsRouteScopingTest.php`

Helpers: `docScopeTenant`, `docScopeUser`, `docScopeDoctor`, `docScopePayload`, `docScopeRequest` (grep first).  
`beforeEach`: withoutMiddleware EnsureTenantActive + SetTenantTimezone; withoutVite; migrate tenant; mock `hasModule('doctors')`.

Cover:
1. Escalation denies (wrong-verb → 403 after fix)
2. Allow exact permission per route (incl. data, email-available)
3. Side effects on blocked writes
4. Soft delete: deleted_at; user remains; TenantUser gone; excluded from data
5. HA-shaped: destroy OK; trashed/restore 403
6. Restore SA-shaped: restore OK; status active; TenantUser back
7. Assignments: permission holder 200; non-holder 403; controller must not call hasRole
8. AD-1 redirects
9. UI flags (may stay red until Task 7)
10. `Route::has('doctors.trashed')` / `doctors.restore`

- [ ] **Step 1:** Write RED suite; run; commit: `test: add doctors route scoping coverage (red)`

### Task 5: Scope routes + soft destroy/trashed/restore + assignments + AD-1

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/DoctorController.php`
- Create: `resources/views/admin/doctors/trashed.blade.php`

Route shape (order matters):

```php
Route::get('/doctors/data', …)->middleware('permission:view doctors');
Route::get('/doctors/email-available', …)->middleware('permission:create doctors|edit doctors');
Route::get('doctors/trashed', …)->middleware('permission:restore doctors');
Route::post('doctors/{doctor}/restore', …)->middleware('permission:restore doctors');

Route::resource('doctors', DoctorController::class)
    ->middlewareFor(['index', 'show'], 'permission:view doctors')
    ->middlewareFor(['create', 'store'], 'permission:create doctors')
    ->middlewareFor(['edit', 'update'], 'permission:edit doctors')
    ->middlewareFor('destroy', 'permission:delete doctors');

Route::get('/doctor/assignments', …)
    ->middleware('permission:view doctor assignments')
    ->name('doctor.assignments');
```

Controller:
- `use RedirectsAfterWrite`
- `destroy`: soft delete; `status = inactive`; unregister TenantUser; **no** user delete; AD-1
- `trashed` / `restore` as Patients pattern; restore sets `active` + register TenantUser
- `assignments`: remove `hasRole`; require permission via middleware; `Doctor::where('user_id', auth()->id())->first()` or 404
- store/update: AD-1 to index if `view doctors`

- [ ] **Step 1:** Implement routes + controller + minimal trashed blade.
- [ ] **Step 2:** Re-run scoping test — route/permission/soft-delete/assignments/AD-1 green; UI may still fail.
- [ ] **Step 3:** Commit: `feat: scope doctor routes and soft-delete with trash restore`

### Task 6: Related doctor() withTrashed + exists rules

**Files:** all models with `belongsTo(Doctor::class)`; FormRequests / OTController with `doctor_id` exists.

- [ ] **Step 1:** Create RED `tests/Feature/Doctors/SoftDeletedDoctorRelationsTest.php` (visit/appointment resolves soft-deleted doctor; store validation rejects trashed doctor_id).
- [ ] **Step 2:** Add `->withTrashed()` on doctor relations; `Rule::exists('tenant.doctors', 'id')->whereNull('deleted_at')` on doctor_id rules.
- [ ] **Step 3:** Green; commit: `fix: keep soft-deleted doctors resolvable and block new links`

---

## Phase 3: UI + README

### Task 7: Blade + DataTables gates

**Files:**
- `resources/views/admin/doctors/index.blade.php`
- `resources/views/admin/doctors/show.blade.php`
- `resources/js/doctors-index.js`
- `trashed.blade.php` polish if needed

- [ ] **Step 1:** `@can` create; trash link `@can('restore doctors')`; `data-can-edit` / `data-can-delete`.
- [ ] **Step 2:** JS: gate edit/delete; confirm “Move to trash”.
- [ ] **Step 3:** Show page `@can` edit/delete.
- [ ] **Step 4:** Full `DoctorsRouteScopingTest` green.
- [ ] **Step 5:** Commit: `fix: gate doctor create edit delete and trash UI`

### Task 8: README deploy steps

**Files:** `README.md` (after Patients Wave 3 section)

```markdown
### Required Deploy Step: Doctors RBAC (Wave 3)

> Same deploy action as the Wave 3 Doctors code.

```bash
php artisan tenants:migrate
php artisan rbac:backfill-delete-doctors --dry-run
php artisan rbac:backfill-delete-doctors
php artisan rbac:backfill-view-doctor-assignments --dry-run
php artisan rbac:backfill-view-doctor-assignments
php artisan tenants:sync-permissions
```
```

- [ ] **Step 1:** Add section; commit: `docs: require doctors RBAC backfills for Wave 3 deploy`

---

## Phase 4: Closing gate

### Task 9: Regression + dry-run + report (no merge)

- [ ] **Step 1:** Run:

```bash
php vendor/bin/pest tests/Feature/Permissions/DoctorsRouteScopingTest.php --no-coverage
php vendor/bin/pest tests/Feature/Commands/BackfillDeleteDoctorsPermissionTest.php --no-coverage
php vendor/bin/pest tests/Feature/Commands/BackfillViewDoctorAssignmentsPermissionTest.php --no-coverage
php vendor/bin/pest tests/Feature/Doctors --no-coverage
php vendor/bin/pest tests/Unit/PermissionRegistryTest.php --no-coverage
```

- [ ] **Step 2:** Dry-run both backfills only (document env failure if no landlord DB).
- [ ] **Step 3:** Final report: HEAD SHA; results; **not merged, not pushed, awaiting instruction.**

---

## Spec coverage checklist

| Spec item | Task |
|---|---|
| DOC-1 route scoping | 4–5 |
| DOC-2 soft delete / no user hard-delete | 1, 5 |
| DOC-3 HA delete + backfill | 2–3, 8 |
| DOC-4/5 SA trash + restore | 5, 7 |
| DOC-6 login deactivate/reactivate | 5 |
| DOC-7 assignments Spatie | 2–5 |
| DOC-8 AD-1 | 5 |
| DOC-9 UI | 7 |
| DOC-10 withTrashed / exists | 6 |
| DOC-11 Spatie-only | all |
| README | 8 |
| Closing gate | 9 |

## Execution handoff

Work only in `saasy-rbac-wave3-doctors`. Never touch main-tree WIP/stashes. Never stash.
