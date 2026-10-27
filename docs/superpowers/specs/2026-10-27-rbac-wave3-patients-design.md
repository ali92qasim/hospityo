# RBAC Wave 3: Patients design

**Date:** 2026-10-27  
**Status:** Confirmed in brainstorming. Implementation waits for the Wave 3 Patients implementation plan.  
**Precedent:** Wave 1 (Accounting / OT / Pharmacy), Wave 2 (Departments / HR / Wards-Beds).  
**Module:** Patients only (first Wave 3 module).

**Confirmed inputs:**
- **PAT-1** — Scope every Patients HTTP route to exactly one action permission (D6). No CRUD any-of.
- **PAT-2** — Soft delete only (Laravel `SoftDeletes`). No permanent / force delete in this wave.
- **PAT-3** — Soft delete allowed for Super Admin and Hospital Administrator via Spatie `delete patients` (not role-name checks). Seeder today withholds `delete patients` from HA → **add** it to HA + deploy backfill (inverse of Wave 2 Departments AC-1 close).
- **PAT-4** — Restore is **Super Admin only** via new Spatie `restore patients` (SA gets it through `Permission::all()`; not granted to HA or any other seeded role).
- **PAT-5** — Trash list (`patients.trashed`) visible to anyone with `delete patients` (SA + HA). Restore button / `patients.restore` only for `restore patients`.
- **PAT-6** — AD-1 via existing `RedirectsAfterWrite` on `store` / `update` / `destroy` / `restore`. Keep “save and add another” → `patients.create`.
- **PAT-7** — Gate ungated Blade / DataTables action controls with `@can` / `data-can-*` (pharmacy catalog row-action pattern for JS-built rows).
- **PAT-8** — `api.patients.search` currently has **no** permission middleware → gate with `view patients`.
- **PAT-9** — D2 (create ≠ view), Spatie-only. No `hasRole` / role-name access checks.
- Sidebar / module entitlement (`view patients` + patients module) unchanged.

**Out of scope:**
- Visits, appointments, bills, lab/imaging ordering, services.
- Hard delete / purge.
- Changing related visit/bill cascade behavior when a patient is soft-deleted.
- Other Wave 3 candidate modules (Doctors, Visits, Appointments, Billing, Services, Laboratory, Imaging).

---

## 1. Current state (investigation summary)

| Item | Finding |
|---|---|
| Resource + `patients.data` | `permission:view patients\|create patients\|edit patients\|delete patients` (any-of escalation) |
| `patients.history` | Already `view patients` |
| `api.patients.search` | Auth only — **ungated** |
| `PatientController::destroy` | **Missing** — resource still registers `patients.destroy` |
| Soft deletes | `Patient` model has **no** `SoftDeletes` / `deleted_at` |
| Registry | `view` / `create` / `edit` / `delete patients` under module `patients` |
| Seeder HA | `view` / `create` / `edit patients` — **not** `delete patients` |
| Seeder Doctor / Nurse | `view` + `edit` only (any-of today lets them create/delete) |
| Seeder Receptionist | `view` / `create` / `edit` |
| Seeder Lab / Pharmacist | `view` only (any-of today lets them write) |
| Index UI | “Add New Patient” ungated; DataTables always shows Edit; no Delete |
| Show UI | Edit ungated |

---

## 2. Route mapping

| Route | Before | After |
|---|---|---|
| `patients.index` | CRUD any-of | `view patients` |
| `patients.data` | CRUD any-of | `view patients` |
| `patients.show` | CRUD any-of | `view patients` |
| `patients.history` | `view patients` | `view patients` (unchanged) |
| `api.patients.search` | none | `view patients` |
| `patients.create` | CRUD any-of | `create patients` |
| `patients.store` | CRUD any-of | `create patients` |
| `patients.edit` | CRUD any-of | `edit patients` |
| `patients.update` | CRUD any-of | `edit patients` |
| `patients.destroy` | CRUD any-of (broken: no method) | `delete patients` — **implement soft delete** |
| `patients.trashed` (**new**) | — | `delete patients` |
| `patients.restore` (**new**) | — | `restore patients` |

**Shape:** per-route `->middleware('permission:…')` (Wave 1 / Departments pattern). Existing URL names for CRUD stay the same; add named trash/restore routes.

**Locked paths** (register `trashed` **before** `patients/{patient}` so it is not captured as an id):
- `GET patients/trashed` → `patients.trashed`
- `POST patients/{patient}/restore` → `patients.restore` (explicit `withTrashed()` route binding / controller resolution)

---

## 3. Soft delete behavior

- Migration: add nullable `deleted_at` to `patients`; model uses `Illuminate\Database\Eloquent\SoftDeletes`.
- Default queries (index, DataTables `data`, show, edit, history, `api.patients.search`): exclude soft-deleted rows.
- `destroy`: `$patient->delete()` (soft). Success flash. **No** `forceDelete`.
- `patients.trashed`: list only `Patient::onlyTrashed()`.
- `restore`: `$patient->restore()` after `withTrashed` resolution. HA with only `delete patients` gets **403**; row stays deleted.
- Related visits / bills / prescriptions: unchanged; they keep `patient_id`. Soft-deleted patients are simply unreachable via normal patient UI until restored.

---

## 4. Registry, seeder, backfill

| Change | Detail |
|---|---|
| `PermissionRegistry` | Add `restore patients` under patients group |
| `RolePermissionSeeder::PERMISSIONS` | Add `restore patients` |
| HA role list | Add `delete patients` (keep view/create/edit) |
| Other roles | Unchanged — no `delete patients`, no `restore patients` |
| Super Admin | Continues to receive all permissions via `syncPermissions(Permission::all())` |
| Backfill | `rbac:backfill-delete-patients {--dry-run}` — grant `delete patients` to role name `Hospital Administrator` per tenant only; report-only for unexpected holders. Same deploy action as code (Wave 1/2 pattern). README deploy section required. |
| Restore backfill | **None** for HA/frontline. New permission must land on Super Admin via the usual seeder `syncPermissions(Permission::all())` (or equivalent deploy seeder run) so existing SA users can restore after go-live. |

---

## 5. Real-role effect (after code + HA backfill)

| Role | After |
|---|---|
| Super Admin | view/create/edit/delete + trash + restore |
| Hospital Administrator | view/create/edit/delete + trash; **no** restore |
| Receptionist | view/create/edit; no delete/trash |
| Doctor / Nurse | view/edit only — **lose** create (and delete) wrongly allowed by any-of |
| Lab Technician / Pharmacist | view only — **lose** create/edit/delete escalation |

---

## 6. Redirects after write (AD-1)

Use existing trait `App\Http\Controllers\Concerns\RedirectsAfterWrite`:

| Action | Prefer if `view patients` | Fallback |
|---|---|---|
| `store` | `patients.index` | `patients.create` (unless `save_and_add_another` → always `patients.create` with flash) |
| `update` | `patients.index` | `patients.edit` |
| `destroy` | `patients.index` | `back()` |
| `restore` | `patients.index` | `patients.trashed` |

---

## 7. UI

| View | Control | Gate |
|---|---|---|
| `index.blade.php` | Add New Patient | `@can('create patients')` |
| `index.blade.php` | Link to Trash | `@can('delete patients')` |
| `index` DataTables row Edit | `data-can-edit="1"` + JS | `edit patients` |
| `index` DataTables row Soft-delete | `data-can-delete="1"` + JS confirm/POST | `delete patients` |
| `index` View / History icons | always for list viewers | (list already requires `view patients`) |
| `show.blade.php` | Edit | `@can('edit patients')` |
| `show.blade.php` | Soft-delete | `@can('delete patients')` |
| `trashed.blade.php` (**new**) | Restore button | `@can('restore patients')` |

Sidebar unchanged (`view patients` + patients module).

---

## 8. Tests (plan will task these)

New `tests/Feature/Permissions/PatientsRouteScopingTest.php` (and focused feature coverage as needed):

- D6 deny / allow / side-effect proof per route (including search + data).
- Soft delete sets `deleted_at`; excluded from index/data/search; present on trashed.
- Seeded HA (after permission grant): can destroy + open trashed; **403** on restore; `deleted_at` unchanged.
- `restore patients` holder: restore succeeds; patient returns to normal index/data.
- AD-1 redirect cases for store/update/destroy/restore.
- UI: create / edit / delete / trash / restore controls gated (view-only vs HA vs SA).
- Backfill command: dry-run writes nothing; real run grants HA only; idempotent.
- Registry test updated for `restore patients`.

---

## 9. Execution note

Patients is Wave 3 module 1 of the remaining unscoped modules (Doctors, Visits, Appointments, Billing, Services, Laboratory, Imaging still later).

Implementation: isolated worktree from `origin/main`, TDD, no merge/push until explicit approval. `rbac:backfill-delete-patients` runs in the **same deploy action** as the code (dry-run first in closing gate; real run only after merge + human approval).
