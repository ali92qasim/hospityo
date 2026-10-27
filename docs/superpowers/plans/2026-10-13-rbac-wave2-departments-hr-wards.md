# RBAC Wave 2: Departments + HR + Wards/Beds Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish Wave 2 (Departments → HR → Wards/Beds). Every in-scope route gets exactly one action permission (plus confirmed supersets: `view hr` on HR reads only; `edit beds` on `beds.update-status`). Close HA destroy escalations (Departments + wards/beds + HR H1–H11) per AC-1 with no backfill except Nurse **`change bed status`**. Hide every control a user can't use. Stop D2 producing "saved, then 403" (AD-1 via existing `RedirectsAfterWrite`). Ship `rbac:backfill-change-bed-status` and the read-only Super Admin probe in the same deploy action as the code.

**Architecture:**
- Spatie `permission:` route middleware is the enforcement layer, with matching `@can`/`@canany` in the views. No role-name access checks.
- AD-1 uses the **existing** shared trait `App\Http\Controllers\Concerns\RedirectsAfterWrite` (created in Wave 1). Never duplicate it.
- New lightweight route `beds.update-status` (`PATCH beds/{bed}/status`) gated `change bed status|edit beds`.
- One idempotent tenant backfill: `rbac:backfill-change-bed-status` (Nurse only), modelled on `BackfillCreatePrescriptionsPermission`, with a report-only safety list.
- One read-only probe: `rbac:probe-super-admins` (name locked here) — lists SA users per tenant; flags tenants with none. Writes nothing.

**Tech Stack:** Laravel 12, Spatie laravel-permission, Spatie multitenancy, Blade, Pest. (No new Vite/JS for ward/bed lists — Blade-only row actions.)

**Specs:**

| Module | Investigation | Design |
|---|---|---|
| Departments | `docs/superpowers/specs/2026-10-09-rbac-wave2-departments-investigation.md` | `docs/superpowers/specs/2026-10-09-rbac-wave2-departments-design.md` |
| HR | `docs/superpowers/specs/2026-10-09-rbac-wave2-hr-investigation.md` | `docs/superpowers/specs/2026-10-09-rbac-wave2-hr-design.md` |
| Wards/Beds | `docs/superpowers/specs/2026-10-09-rbac-wave2-wards-beds-investigation.md` | `docs/superpowers/specs/2026-10-09-rbac-wave2-wards-beds-design.md` |

## Confirmed decisions

| # | Decision |
|---|---|
| D2 | Create does not imply view. |
| D5 | `view hr` is view-only; accepted on HR read GETs only; never on writes. |
| D6 | One permission per action, with before/after tests per route. |
| AC-1 | Seeder-intended abilities may be backfilled; any-of-only abilities close. |
| AD-1 | After a successful write, redirect to the list only if the user can view it; else form/`back()`. |
| DEP-1…DEP-5 | As Departments design. HA loses `departments.destroy`; no backfill. |
| HR-1…HR-6 | As HR design. Close H1–H11; SA-only for approve/verify/swap; no HR seeder/backfill. |
| WB-1…WB-6 | As Wards/Beds design. **WB-3 = F3:** Nurse gets `change bed status` (not `edit beds`); new `beds.update-status`. WB-2 = close HA ward/bed destroys. |
| STATUS-TRANSITIONS | Proposed in design §3 (`available` ↔ `maintenance` only; never hand-set `occupied`; reject if active admission / current occupied). **Confirm before Task 12 implements `updateStatus`.** |
| FOLLOW-UP | Full edit form can reassign ward on an occupied bed (admins too) — **not this wave.** |

## Global Constraints

- **Worktree, not the main tree.**
  - Main tree: `C:/Users/Qasim/Herd/saasy` on `fix/bug-fixes` with WIP and **4 stashes**. **Never** checkout, add, commit, or stash there.
  - All work: `C:/Users/Qasim/Herd/saasy-rbac-wave2` on `feat/rbac-wave2-departments-hr-wards` (already exists; tip was `06dd82c` + docs).
- **Never run `git stash` in the worktree.** Worktrees share one stash list with the main repo. To compare pre-change state: `git show HEAD:<path> > <scratch file>`. Every subagent prompt must repeat this.
- **Wave 2 stays off `main` until production has been updated with Wave 1.** No merge, no push until Task 17 (after explicit human approval).
- **TDD:** failing test first. Run red and confirm each failure is for the **right reason** (Expected 403 received 200/302, or side-effect seeing a write). Not setup error. Then implement, green, commit. ≥1 commit per task.
- **Before/after proof per route (D6), every phase:**
  - (a) **Deny:** permission holder who passes today but must not → **403**. Red before the fix.
  - (b) **Positive control:** exactly the new permission passes.
  - (c) State-changing routes: **side-effect assertion** — blocked request wrote nothing.
- **"Passes for the wrong reason" check:** wherever an existing test's permission fixture changes, prove tests meant to hit plan/module **403** still hit that 403 (not a Spatie miss). List every fixture change in the phase report with reason.
- **Spatie only** in access paths. Role names only as backfill target selectors.
- **No real tenant data writes during execution.** Backfills and probes: `--dry-run` / read-only in the closing gate. Real backfill only in Task 17 after approval.
- **Full regression is chunked:** one Pest process per `tests/Feature/*` entry + `tests/Unit`. Pass = failures match the known **34-test baseline by file and count** (same table as Wave 1 completion plan). If a baseline test starts passing, report it; not a failure.
- **Phase boundaries:** report to the human at the end of Phases 1, 2, 3, and 4.
- **Pest helpers are global:** grep `tests/` for every new helper name before adding it.
- **CheckModule** and HR catalog child slugs (`hr.employees`, `hr.attendance-leave`, `hr.payroll`, `hr.scheduling`) stay untouched.

## File Structure

| File | Responsibility |
|---|---|
| `routes/web.php` departments resource | Per-route department middleware |
| `app/Http/Controllers/DepartmentController.php` | `RedirectsAfterWrite` |
| `resources/views/admin/departments/{index,show}.blade.php` | `@can` |
| `routes/web.php` `hr.` block (~L441–584) | Per-route HR middleware (73 routes) |
| HR controllers (Employee, Designation, Attendance, Leave, Payroll, Shift, DepartmentStaff, DocumentManagement) | `RedirectsAfterWrite` where list-bound |
| `resources/views/admin/hr/**` | `@can` on action controls |
| `routes/web.php` wards/beds (~L593–594) | Per-route middleware + `beds.update-status` |
| `app/Support/PermissionRegistry.php` | beds group += `change bed status` |
| `WardController`, `BedController` | `RedirectsAfterWrite`; `BedController@updateStatus` |
| `resources/views/admin/{wards,beds}/index.blade.php` | `@can` / `@canany`; beds list status control |
| `database/seeders/RolePermissionSeeder.php` | PERMISSIONS + Nurse += `change bed status` (not `edit beds`) |
| `app/Console/Commands/BackfillChangeBedStatusPermission.php` | **Create** |
| `app/Console/Commands/ProbeSuperAdminsCommand.php` (or similar) | **Create** read-only SA probe |
| `README.md` → Deployment | `change bed status` backfill + SA probe sections |
| `tests/Feature/Permissions/DepartmentsRouteScopingTest.php` | **Create** |
| `tests/Feature/Permissions/HrRouteScopingTest.php` (may split by cluster) | **Create** |
| `tests/Feature/Permissions/WardsBedsRouteScopingTest.php` | **Create** (13 routes + status rules; show routes removed) |
| `tests/Feature/Commands/BackfillChangeBedStatusPermissionTest.php` | **Create** |
| `tests/Feature/Commands/ProbeSuperAdminsTest.php` | **Create** |

---

## Phase 0: Hygiene + docs on branch

### Task 0: Record baselines and force-add Wave 2 docs

**Files:** docs under `docs/superpowers/specs/` and this plan (gitignored `/docs` — force-add).

- [ ] **Step 1:** Record main-tree baseline (scratchpad only; do not modify main tree):

```bash
cd C:/Users/Qasim/Herd/saasy
git status --short > <scratchpad>/main-tree-status-before.txt
git stash list    > <scratchpad>/main-tree-stash-before.txt
# expect 4 stash entries; WIP on fix/bug-fixes
```

- [ ] **Step 2:** In the worktree, confirm branch and that `vendor` resolves inside the worktree:

```bash
cd C:/Users/Qasim/Herd/saasy-rbac-wave2
git rev-parse --abbrev-ref HEAD   # feat/rbac-wave2-departments-hr-wards
php -r "require 'vendor/autoload.php'; echo (new ReflectionClass(App\Http\Controllers\DepartmentController::class))->getFileName();"
# must print a path inside saasy-rbac-wave2
```

- [ ] **Step 3:** Force-add all Wave 2 specs + this plan; commit:

```bash
git add -f docs/superpowers/specs/2026-10-09-rbac-wave2-* docs/superpowers/plans/2026-10-13-rbac-wave2-departments-hr-wards.md
git commit -m "$(cat <<'EOF'
docs: add RBAC wave 2 departments, HR, wards/beds specs and plan

EOF
)"
```

- [ ] **Step 4:** Note worktree path, branch, HEAD, main-tree status/stash unchanged for Phase 1 report.

---

## Phase 1: Departments

### Task 1: Per-route departments middleware (7 routes)

**Files:** `routes/web.php`; create `tests/Feature/Permissions/DepartmentsRouteScopingTest.php`.

**Mapping:** as Departments design §1 (`view` / `create` / `edit` / `delete departments`).

- [ ] **Step 1: Write failing tests.**
  - Helpers: new names e.g. `depScopeTenant()` / `depScopeUser(array $perms)` (grep first). Mock tenant with departments module if other scoping tests do.
  - **(a) Deny dataset** — each escalation that works today via any-of, e.g. `view departments`-only on create/store/edit/update/destroy → 403 after; `create departments`-only on index/show → 403 (D2).
  - **(b) Positive controls** — each of the 7 routes with exactly its After permission (GET 200; writes valid payload → 302 not to 403).
  - **(c) Side effects:** `view departments`-only POST store / PUT update / DELETE destroy → 403 and `Department::count()` / attributes unchanged.
  - **AC-1:** seeded HA (RolePermissionSeeder) → 403 on destroy; 200 on index/create/edit.
- [ ] **Step 2: Run red.** Confirm Expected 403 received 200/302 and side-effect failures. Record counts + 2 sample messages.
- [ ] **Step 3: Implement** per-route middleware. Paths/names/verbs unchanged.
- [ ] **Step 4: Green.** New file + any related department tests.
- [ ] **Step 5: Commit:** `fix: scope department routes to one permission per action`

### Task 2: AD-1 on DepartmentController

**Files:** `DepartmentController.php`; append tests.

- [ ] **Step 1: Failing tests** — store/update/destroy with write-only vs write+`view departments`; assert redirect target + success flash (trait already exists from Wave 1).
- [ ] **Step 2: Red → Step 3: `use RedirectsAfterWrite;` replace hard redirects → Step 4: Green → Step 5: Commit `feat: redirect after department writes only when user can view the list`

### Task 3: Gate department UI

**Files:** `resources/views/admin/departments/index.blade.php`, `show.blade.php`; append tests.

- [ ] **Step 1: Failing tests** — view-only: no Add/Edit/Delete; create/edit/delete holders see their controls.
- [ ] **Step 2–4:** Red → `@can` per design §4 → Green.
- [ ] **Step 5: Commit:** `fix: hide department actions users are not permitted to perform`
- [ ] **Step 6: Phase 1 report** (include Task 0): SHAs; red/green; 7-row route table; side-effect list; seeded-HA; fixture changes (expect none); main tree unchanged.

---

## Phase 2: HR

Implement against HR design §1a–§1i (73 routes). Prefer **one scoping test file** with cluster datasets, or split if memory requires (`HrEmployeesRouteScopingTest`, etc.). Grep for helper name collisions.

### Task 4: Scope `hr.employees` + `hr.designations` (16 routes)

- [ ] **Step 1: Failing tests** — D6 deny/positive/side-effect for all 16; H5/H6/H7 closes for seeded HA (`delete-document`, `employees.destroy`, `designations.destroy`); positive SA (or direct `delete …` / `approve …` grants) where needed.
- [ ] **Step 2: Red for the right reason** → Step 3: per-route middleware → Step 4: Green → Step 5: Commit `fix: scope HR employee and designation routes`

### Task 5: Scope attendance, leave, leave-types (17 routes)

- [ ] Same TDD cycle. H1 close: HA 403 on approve/reject; SA/`approve leave requests` positive. H8: leave-types.destroy. `view hr` 200 on reads only; 403 on writes.
- [ ] Commit: `fix: scope HR attendance and leave routes`

### Task 6: Scope payroll (16 routes)

- [ ] Same TDD cycle. H2/H9 closes; mark-paid / update-employee-salary stay for HA (`edit payslips` / `edit employee salary`).
- [ ] Commit: `fix: scope HR payroll routes`

### Task 7: Scope shifts, department-staff, documents (24 routes)

- [ ] Same TDD cycle. H3/H4/H10/H11 closes; department-staff writes stay for HA (`edit department staff`).
- [ ] Commit: `fix: scope HR shifts, department-staff, and document routes`

### Task 8: AD-1 on HR list-bound writes

**Files:** HR controllers that `redirect()->route('hr.…index'|show)`.

- [ ] Failing redirect tests for representative writes per cluster (employees store, leave store, attendance store-daily, payroll generate, roster store, etc.).
- [ ] Implement via `RedirectsAfterWrite`; approve/reject/verify/`back()` paths unchanged.
- [ ] Commit: `feat: redirect after HR writes only when user can view the target`

### Task 9: Gate HR Blade action controls

**Files:** `resources/views/admin/hr/**` per HR design §4.

- [ ] Failing UI tests for view-only vs editor vs approver on representative pages (employees show docs, leave index approve, payroll show, swaps, etc.).
- [ ] `@can` / `@canany`; **do not** change CheckModule or catalog slugs.
- [ ] **"Passes for the wrong reason":** if any existing `Hr*Test` / catalog test fixture changes, prove module/plan 403 still fires.
- [ ] Commit: `fix: hide HR actions users are not permitted to perform`
- [ ] **Phase 2 report:** full 73-row route→permission table; H1–H11 proof; side-effect list; fixture changes; main tree unchanged.

---

## Phase 3: Wards / Beds (WB-3 = F3)

**Gate:** Before Task 12 implements transition logic, human must confirm **STATUS-TRANSITIONS** (design §3 proposal, or an override). If unanswered, **STOP and ask**. WB-3 F3 itself is already confirmed.

### Task 10: Registry + seeder + `rbac:backfill-change-bed-status` + tests

**Files:**
- `app/Support/PermissionRegistry.php` — beds group += `change bed status`
- `database/seeders/RolePermissionSeeder.php` — global `PERMISSIONS` += `change bed status`; Nurse += `change bed status` (**not** `edit beds`)
- Create `BackfillChangeBedStatusPermission.php` + `BackfillChangeBedStatusPermissionTest.php`

- [ ] **Step 1: Failing tests** (copy structure from `BackfillCreatePrescriptionsPermissionTest`):
  - seeds / grants **`change bed status`** to **Nurse** only;
  - Nurse does **not** receive `edit beds` from this command or seeder;
  - idempotent second run;
  - `--dry-run` writes nothing (permission row not created if missing; no role_has_permissions inserts — assert counts);
  - real run creates the permission row where missing;
  - **custom role** holding e.g. `view beds` (not SA/HA/Nurse) appears in `[report only]` and receives **nothing** (neither `edit beds` nor `change bed status`);
  - report-only excludes roles that already hold `edit beds` **or** `change bed status`;
  - Super Admin / Hospital Administrator never appear on the report-only list (exclude by name).
- [ ] **Step 2: Red** → Step 3: implement registry, seeder, command → Step 4: Green → Step 5: Commit `feat: backfill change bed status for Nurse with report-only ward/bed safety list`

### Task 11: Per-route wards + beds middleware (12 resource routes; show removed)

**Files:** `routes/web.php`; create/extend `WardsBedsRouteScopingTest.php`.

- [ ] **Step 1: Failing tests:**
  - D6 deny/positive/side-effect for all 12 resource routes (`wards.show` / `beds.show` removed as dead).
  - Seeded HA: 403 destroy wards/beds; 200 create/edit/index.
  - **Seeded Nurse:** **403** on `beds.edit` / `beds.update` / `beds.create` / `beds.store` / `beds.destroy` and every ward write (`create`/`store`/`edit`/`update`/`destroy`); **200** on `wards.index` / `beds.index` with view perms; does **not** hold `edit beds`.
  - Side effects: Nurse POST `beds.store` / DELETE `beds.destroy` / PUT `wards.update` / PUT `beds.update` write nothing.
- [ ] **Step 2: Red** → Step 3: per-route middleware on the two resources → Step 4: Green → Step 5: Commit `fix: scope ward and bed resource routes to one permission per action`

### Task 12: `beds.update-status` + AD-1 + transition rules (F3)

**Files:** `routes/web.php` (register `PATCH beds/{bed}/status` → `beds.update-status`); `BedController@updateStatus`; `WardController` / `BedController` AD-1 for resource writes; append to `WardsBedsRouteScopingTest`.

**Route:** name **`beds.update-status`**; middleware `permission:change bed status|edit beds` (`edit beds` = confirmed superset).

- [ ] **Step 1:** Confirm STATUS-TRANSITIONS with human if not already accepted. Record in phase report. Default proposal: `available` ↔ `maintenance` only; never set `occupied`; reject if current status is `occupied` or bed has an active admission (`admissions.status = active`); clear error flash; write nothing on reject. Full `beds.update` form unchanged.
- [ ] **Step 2: Failing tests:**
  - Seeded Nurse (or user with only `change bed status` + `view beds`): **can** change a free bed `available` → `maintenance` (and back); success redirects via AD-1 to `beds.index` (or `back()` fallback).
  - Nurse **403** still on full edit/update/create/destroy and ward writes (regression from Task 11).
  - Blocked status change writes nothing: request `occupied`; bed already `occupied`; bed with active admission (any requested non-occupied target) → error response, `beds.status` unchanged, no other columns changed.
  - User with **`edit beds` only** (no `change bed status`) **can** use `beds.update-status` (superset positive control).
  - User with only `view beds` → 403 on status action; write nothing.
  - AD-1 for wards/beds store/update/destroy as in design §6.
- [ ] **Step 3: Implement** route + `updateStatus` + resource AD-1 → Step 4: Green → Step 5: Commit `feat: add beds.update-status with change bed status or edit beds`

### Task 13: Gate wards/beds UI (incl. status control)

**Files:** `resources/views/admin/wards/index.blade.php`, `beds/index.blade.php`.

- [ ] Failing UI tests:
  - Nurse: status control present (`@canany`); **no** Edit / Delete / Add bed; no ward Add/Edit/Delete.
  - View-only (`view beds`): no status control, no Edit/Delete/Add.
  - `edit beds` holder: status control + Edit; Delete only with `delete beds`.
  - HA: create/edit present; destroy absent.
- [ ] Blade-only small per-row status form (no JS). Full edit form unchanged.
- [ ] Commit: `fix: hide ward and bed actions; show status control to permitted users`

### Task 14: README deploy sections

**Files:** `README.md` (after existing Wave 1 backfill sections).

- [ ] Add **"Required Deploy Step: `change bed status` (RBAC Wave 2, WB-3 F3)"** per Wards/Beds design §4 (dry-run first; same deploy action; report-only lines).
- [ ] Add **"Required Deploy Check: Super Admin coverage (RBAC Wave 2, read-only)"** per HR design (probe name must match Task 15).
- [ ] Commit: `docs: require change-bed-status backfill and Super Admin probe in Wave 2 deploy`
- [ ] **Phase 3 report:** Nurse matrix; backfill output; STATUS-TRANSITIONS choice; 13-row route table; README texts; follow-up note (occupied ward move on full edit); main tree unchanged.

---

## Phase 4: Closing gate

### Task 15: Regression, route dump, dry-run, probes, role scan

- [ ] **Step 1: Focused suites.**

```bash
vendor/bin/pest tests/Feature/Permissions tests/Feature/Commands tests/Feature/Module tests/Feature/Navigation tests/Feature/Visits tests/Unit --no-coverage
# plus any HR/department feature dirs touched
```

- [ ] **Step 2: Full chunked regression** — one Pest process per `tests/Feature/*` + `tests/Unit`. Failures must match the **34-test baseline by file and count** (Wave 1 completion plan table). Anything else: failing test → fix → commit → re-run.

- [ ] **Step 3: Diff review** (`git diff origin/main...HEAD` on app/resources/routes/database/README):
  - only Spatie in access paths;
  - no CRUD any-of left on departments / `hr.*` / wards / beds;
  - `beds.update-status` uses `change bed status|edit beds` only;
  - CheckModule + HR catalog slugs unchanged;
  - AD-1 only via shared trait.

- [ ] **Step 4: Route dump proof.** `php artisan route:list --json` filtered to `departments.*`, `hr.*`, `wards.*`, `beds.*`. Diff against the three design tables (13 ward/bed rows including `beds.update-status`; no `wards.show` / `beds.show`). Every row must match.

- [ ] **Step 5: Implement + test the read-only Super Admin probe** (if not done earlier):
  - Command e.g. `rbac:probe-super-admins` — per tenant list SA users; flag tenants with zero; **exit non-zero or print FLAG** when any tenant has none; **never writes**.
  - Feature test with a tenant missing SA users asserts the flag.
  - Run against all 7 local tenants; attach output to the report.
  - Note coverage: Wave 1 `close fiscal years` + Wave 2 delete/approve closures that remain SA-only.

- [ ] **Step 6: Backfill dry-run + before/after SQL probe** (read-only real tenants, from worktree):
  - Before: per-tenant counts of `permissions`, `role_has_permissions` for `change bed status` / Nurse.
  - `php artisan rbac:backfill-change-bed-status --dry-run` — expect would update Nurse × tenants missing the grant; read `[report only]` lines.
  - After: counts **identical** to before.
  - `php artisan rbac:probe-super-admins` — read-only.

- [ ] **Step 7: Role scan** (read-only SQL, 7 tenants) — HA lacks deletes/approves; Nurse has/will have `change bed status` (not `edit beds`) after real backfill; no unexpected holders.

- [ ] **Step 8: Final report** — HEAD SHA; chunked baseline table; route dump; dry-run + probe outputs; role scan; fixture-change list; **not merged, not pushed, awaiting instruction.** Explicit: Wave 2 must not land on main until production has Wave 1.

### Task 16: Worktree hygiene

- [ ] Main tree `git status --short` and `git stash list` match Phase 0 scratchpad (4 stashes). Worktree kept.

### Task 17: After human approval only (not part of execution)

1. Confirm production already has Wave 1. Only then fast-forward / merge `feat/rbac-wave2-departments-hr-wards` to `main` (rebase or fast-forward only as approved), push if approved.
2. **With explicit approval,** run `php artisan rbac:backfill-change-bed-status` for real (after a final `--dry-run`).
3. Run `php artisan rbac:probe-super-admins`; act on any FLAG.
4. Verify Nurse holds `change bed status` (and **not** newly granted `edit beds`) in every tenant; HA still lacks deletes/approves; README "applied locally" lines updated; commit.
5. Remove Wave 2 worktree (`git worktree remove`, `prune`); confirm main tree WIP + 4 stashes unchanged.

**Deployment hard requirement:** `rbac:backfill-change-bed-status` in the **same deploy action** as this code; run SA probe in the same window. Not before, not later.

---

## Spec coverage self-check

| Spec item | Task |
|---|---|
| Departments §1–§4 mapping, AD-1, UI, AC-1 destroy | 1–3 |
| HR §1 all 73 routes / 9 clusters | 4–7 |
| HR H1–H11 close, no seeder/backfill | 4–7 |
| HR AD-1, UI, CheckModule untouched | 8–9 |
| HR Super Admin probe | 15 (+ README 14) |
| Wards/Beds §1 resource mapping | 11 |
| WB-2 HA destroys | 11 |
| WB-3 F3 registry/seeder/backfill + safety report | 10 |
| `beds.update-status` + transitions + AD-1 | 12 |
| Wards/Beds UI status control, no JS | 13 |
| README deploy | 14 |
| Occupied ward-move on full edit | **follow-up — not this wave** |
| D6 TDD + side effects + wrong-reason fixture check | all scoping tasks |
| Chunked 34-baseline + route dump + dry-run probe | 15 |
| Merge/real backfill only after approval | 17 |

## Placeholder scan

- Route line numbers from tip `06dd82c`; verify in worktree before editing; report drift.
- STATUS-TRANSITIONS is the only open product decision left for Phase 3; execution stops at Task 12 Step 1 if unset.
- No other deferred decisions in-wave.

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-10-13-rbac-wave2-departments-hr-wards.md`.

**Do not begin execution until the human confirms this plan** (and confirms STATUS-TRANSITIONS before or at Task 12).

When confirmed:
1. **Subagent-Driven (recommended):** fresh subagent per task; every prompt repeats: work only in `saasy-rbac-wave2`, **never `git stash`**, never touch main tree, no push/merge.
2. **Inline Execution:** `superpowers:executing-plans` with the same checkpoints.

Human reports at end of Phases 1, 2, 3, and 4. Task 17 only after explicit approval.
