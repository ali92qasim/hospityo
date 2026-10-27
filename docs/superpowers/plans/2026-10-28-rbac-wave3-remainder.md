# RBAC Wave 3 Remainder Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Per-route Spatie scoping for Appointments, Services, Billing, Laboratory, Imaging, and Visits; remove all CRUD destroy routes/UI and drop matching `delete *` permissions (payments keep `delete payments`); cut Imaging off lab investigation perms; replace Visits `hasRole('Doctor')` with `view assigned visits`.

**Architecture:** Wave 1/2 playbook — per-route `permission:` middleware, registry/seeder cleanup, RouteScopingTest (TDD), UI gates, README + backfills. No SoftDeletes. Sequential SDD on `feat/rbac-wave3-remainder`. Linear ff to `main` only after human approval at closing gate.

**Tech Stack:** Laravel 12, Spatie permission + multitenancy, Blade, Vite/JS, Pest.

**Spec:** `docs/superpowers/specs/2026-10-28-rbac-wave3-remainder-design.md`

## Global Constraints

- Worktree only: `C:/Users/Qasim/Herd/saasy-rbac-wave3-remainder` on `feat/rbac-wave3-remainder`. Never touch `C:/Users/Qasim/Herd/saasy` (`fix/bug-fixes` WIP / stashes).
- Never `git stash` in the worktree.
- No merge/push to `main` until closing-gate human approval.
- TDD: red RouteScopingTest first where practical; then implement; green; ≥1 commit per task.
- Pest: do **not** add `uses(Tests\TestCase::class)`.
- Spatie only on access paths (Visits: no `hasRole('Doctor')`; SA carve-out via Doctor profile existence, not role name).
- Valid APP_KEY for tests: `base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=`
- Pattern refs: `tests/Feature/Permissions/DoctorsRouteScopingTest.php`, Patients/Doctors route blocks in `routes/web.php`.

## File map (high level)

| Area | Files |
|---|---|
| Registry / seeder | `app/Support/PermissionRegistry.php`, `database/seeders/RolePermissionSeeder.php` |
| Routes | `routes/web.php` |
| Controllers | Appointment, Service, Bill, Investigation*, Lab*, Imaging*, Radiology*, Visit |
| UI | `resources/views/admin/{appointments,services,bills,lab,imaging,visits,radiology}/**`, related `resources/js/*` |
| Backfills | `BackfillViewAssignedVisitsPermission`, imaging studies/orders cutover command(s) |
| Tests | `tests/Feature/Permissions/*RouteScopingTest.php`, Command tests, `tests/Unit/PermissionRegistryTest.php` |
| Docs | `README.md` |

---

### Task 0: Docs already on branch

- [x] Design committed (`d4151b2`).
- [ ] Commit this plan (force-add).

```bash
git add -f docs/superpowers/plans/2026-10-28-rbac-wave3-remainder.md
git commit -m "docs: add RBAC wave 3 remainder implementation plan"
```

---

### Task 1: Appointments

**Files:** registry, seeder, `routes/web.php`, `AppointmentController` (remove destroy), appointments Blade/JS, `tests/Feature/Permissions/AppointmentsRouteScopingTest.php`, PermissionRegistryTest.

- [ ] **Step 1 (RED):** Write `AppointmentsRouteScopingTest` — index/create/store/show/edit/update require exact perms; destroy route missing (404) or not registered; calendar.events = view; user with only delete appointments cannot access anything; UI index has no Delete when implemented later.
- [ ] **Step 2:** Drop `delete appointments` from registry + seeder; per-route middleware; `except(['destroy'])`; remove `destroy` method; remove Delete UI.
- [ ] **Step 3:** Green test; commit `feat: scope appointments routes and remove delete`

---

### Task 2: Services

Same playbook for services resource + import routes.

- [ ] RED `ServicesRouteScopingTest`
- [ ] Drop `delete services`; per-route; except destroy; UI; green
- [ ] Commit `feat: scope services routes and remove delete`

---

### Task 3: Billing (bills)

Keep payment delete routes/permissions. Drop bill destroy + `delete bills` only.

- [ ] RED `BillsRouteScopingTest` — bills CRUD without destroy; payments create/edit/delete still scoped as today
- [ ] Registry/seeder drop `delete bills` only; bills resource except destroy; remove Delete bill UI; taxes unchanged
- [ ] Commit `feat: scope bills routes and remove bill delete`

---

### Task 4: Laboratory

Drop delete for investigations, investigation orders, lab orders, lab results. Aliases `/lab/*` and `lab-tests` match. Remove all destroy routes.

- [ ] RED `LaboratoryRouteScopingTest` (representative routes across catalog/orders/results + /lab aliases)
- [ ] Registry/seeder; per-route; except destroy everywhere; UI
- [ ] Commit `feat: scope laboratory routes and remove delete`

---

### Task 5: Imaging cutover

- [ ] Add registry: view/create/edit imaging studies, view/create/edit imaging orders; drop delete radiology results
- [ ] Seeder grants for HA (and roles that had investigation/lab order perms)
- [ ] Backfill command `rbac:backfill-imaging-permissions` mapping investigation→studies and investigation/lab orders→imaging orders (view/create/edit); dry-run; report custom roles
- [ ] Routes: imaging studies/orders use imaging perms; no DESTROY; radiology results no destroy
- [ ] RED/green `ImagingRouteScopingTest` + backfill test
- [ ] Commit `feat: cut over imaging permissions and remove delete`

---

### Task 6: Visits + view assigned visits

- [ ] Registry + seeder: drop `delete visits`; add `view assigned visits` to Doctor role
- [ ] Backfill `rbac:backfill-view-assigned-visits` (Doctor only)
- [ ] Routes: per-route view/create/edit; ensure destroy stays excluded; check-patient middleware can stay `edit visits` but controller uses Spatie assigned rule
- [ ] VisitController: replace `hasRole('Doctor')` with `can('view assigned visits') && Doctor::where('user_id', auth()->id())->exists()` for index scoping; checkPatient same permission + profile + care-team
- [ ] RED/green `VisitsRouteScopingTest` + backfill test (include assigned scoping + checkPatient 403 without perm)
- [ ] Commit `feat: scope visits and replace doctor role check with Spatie`

---

### Task 7: README + closing gate

- [ ] README section: Wave 3 Remainder deploy (sync-permissions + imaging backfill + view-assigned-visits backfill)
- [ ] Run all new RouteScoping + backfill + PermissionRegistry tests
- [ ] Commit `docs: add Wave 3 Remainder RBAC deploy notes`
- [ ] Report HEAD; **not merged, not pushed** until human says ff main

---

## Closing verification commands

```bash
$env:APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='
php artisan test tests/Feature/Permissions/AppointmentsRouteScopingTest.php
php artisan test tests/Feature/Permissions/ServicesRouteScopingTest.php
php artisan test tests/Feature/Permissions/BillsRouteScopingTest.php
php artisan test tests/Feature/Permissions/LaboratoryRouteScopingTest.php
php artisan test tests/Feature/Permissions/ImagingRouteScopingTest.php
php artisan test tests/Feature/Permissions/VisitsRouteScopingTest.php
php artisan test tests/Feature/Commands --filter=Backfill
php artisan test tests/Unit/PermissionRegistryTest.php
```
