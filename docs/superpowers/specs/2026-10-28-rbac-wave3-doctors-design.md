# RBAC Wave 3: Doctors design

**Date:** 2026-10-28  
**Status:** Confirmed in brainstorming. Implementation waits for the Wave 3 Doctors implementation plan.  
**Precedent:** Wave 1, Wave 2, Wave 3 Patients (`2026-10-27-rbac-wave3-patients-design.md`).  
**Module:** Doctors (Wave 3 module 2).

**Confirmed inputs:**
- **DOC-1** — Scope every admin Doctors HTTP route to exactly one action permission (D6). No CRUD any-of. Exception: `doctors.email-available` keeps `create doctors|edit doctors` (form helper for both write forms).
- **DOC-2** — Soft delete only (Laravel `SoftDeletes`). No permanent / force delete in this wave. Stop hard-deleting the linked `User` on destroy.
- **DOC-3** — Soft delete allowed for Super Admin and Hospital Administrator via Spatie `delete doctors`. Seeder today withholds delete from HA → **add** it to HA + deploy backfill.
- **DOC-4** — Restore is **Super Admin only** via new Spatie `restore doctors` (SA via `Permission::all()`; not granted to HA).
- **DOC-5** — Trash list (`doctors.trashed`) is **Super Admin only** (`restore doctors`). HA may soft-delete from the live list but cannot open trash or restore.
- **DOC-6** — On soft-delete: deactivate login without deleting the user — set doctor `status` inactive and `TenantUser::unregister` for the linked email. On restore: set `status` active and `TenantUser::register` again.
- **DOC-7** — `doctor.assignments`: replace `hasRole('Doctor')` with Spatie `view doctor assignments`. Grant to seeded **Doctor** role + deploy backfill. Controllers must still resolve the doctor row for the current user (`Doctor::where('user_id', …)`); 404 if none.
- **DOC-8** — AD-1 via existing `RedirectsAfterWrite` on `store` / `update` / `destroy` / `restore`.
- **DOC-9** — Gate ungated Blade / DataTables controls with `@can` / `data-can-*`.
- **DOC-10** — Related `belongsTo(Doctor::class)` use `->withTrashed()`; `doctor_id` exists rules use `whereNull('deleted_at')` (Patients lesson).
- **DOC-11** — D2 (create ≠ view), Spatie-only on access paths. No `hasRole` / role-name checks for doctors admin or assignments.
- Sidebar / module entitlement (`view doctors` + doctors module) unchanged.

**Out of scope:**
- Doctor Share (`doctor-share` / settlements / rates).
- Visits / appointments / bills business logic beyond relation + exists safety.
- Hard delete / purge of doctor or user.
- Inventing new admin features beyond trash/restore/login deactivate.

---

## 1. Current state (investigation summary)

| Item | Finding |
|---|---|
| Resource + `doctors.data` | `permission:view\|create\|edit\|delete doctors` (any-of escalation) |
| `doctors.email-available` | `create doctors\|edit doctors` (keep) |
| `DoctorController::destroy` | Hard-deletes linked `User` + `TenantUser::unregister` + `$doctor->delete()` |
| Soft deletes | `Doctor` has **no** `SoftDeletes` / `deleted_at` |
| Registry | `view` / `create` / `edit` / `delete doctors` |
| Seeder HA | `view` / `create` / `edit doctors` — **not** `delete doctors` |
| Seeder Doctor role | No doctors CRUD; assignments gated by `hasRole('Doctor')` today |
| `doctor.assignments` | Auth only + `hasRole('Doctor')` — no Spatie permission |
| Index UI | Add New / Edit / Delete ungated in Blade/JS |
| Show UI | Edit ungated |

---

## 2. Route mapping

| Route | Before | After |
|---|---|---|
| `doctors.index` | CRUD any-of | `view doctors` |
| `doctors.data` | CRUD any-of | `view doctors` |
| `doctors.show` | CRUD any-of | `view doctors` |
| `doctors.create` | CRUD any-of | `create doctors` |
| `doctors.store` | CRUD any-of | `create doctors` |
| `doctors.edit` | CRUD any-of | `edit doctors` |
| `doctors.update` | CRUD any-of | `edit doctors` |
| `doctors.email-available` | `create\|edit doctors` | unchanged |
| `doctors.destroy` | CRUD any-of (hard delete) | `delete doctors` — **soft delete + login deactivate** |
| `doctors.trashed` (**new**) | — | `restore doctors` |
| `doctors.restore` (**new**) | — | `restore doctors` |
| `doctor.assignments` | `hasRole('Doctor')` | `permission:view doctor assignments` (+ resolve own doctor profile) |

**Shape:** per-route `->middleware('permission:…')` (Wave 1 / Patients pattern).

**Locked paths** (register `trashed` **before** `doctors/{doctor}`):
- `GET doctors/trashed` → `doctors.trashed`
- `POST doctors/{doctor}/restore` → `doctors.restore` (`onlyTrashed()->findOrFail`)

---

## 3. Soft delete & login lifecycle

- Migration: nullable `deleted_at` on `doctors`; model uses `SoftDeletes`.
- `doctor_no` generation: `Doctor::withTrashed()->max('id')` (avoid unique collisions).
- Default queries (index, data, show, edit, email-available): exclude soft-deleted.
- **`destroy`:**
  1. Soft-delete doctor (`$doctor->delete()`).
  2. Set `status` to `inactive` (existing vocabulary: `active` \| `inactive`).
  3. If linked user: `TenantUser::unregister($email, $tenantId)` only — **do not** delete the `User` row.
  4. Success flash; AD-1 redirect.
- **`restore`:**
  1. `onlyTrashed()->findOrFail` → `$doctor->restore()`.
  2. Set `status` to `active`.
  3. If linked user/email: `TenantUser::register($email, $tenantId)`.
  4. Success flash; AD-1 redirect.
- **`doctors.trashed`:** `Doctor::onlyTrashed()`; gated `restore doctors` only.
- Related models (`Visit`, `Appointment`, `Bill`, `LabOrder`, `ImagingOrder`, `Prescription`, `Surgery`, care-team notes, doctor-share rows, etc.): `belongsTo(Doctor::class)->withTrashed()`.
- FormRequests / OT validation with `doctor_id`: `Rule::exists('tenant.doctors', 'id')->whereNull('deleted_at')`.

---

## 4. Registry, seeder, backfills

| Change | Detail |
|---|---|
| `PermissionRegistry` | Add `restore doctors`, `view doctor assignments` under doctors group |
| `RolePermissionSeeder::PERMISSIONS` | Same two names |
| HA role list | Add `delete doctors` (keep view/create/edit) |
| Doctor role list | Add `view doctor assignments` |
| Super Admin | Continues via `syncPermissions(Permission::all())` |
| Backfill 1 | `rbac:backfill-delete-doctors {--dry-run}` → Hospital Administrator only |
| Backfill 2 | `rbac:backfill-view-doctor-assignments {--dry-run}` → Doctor role only |
| Restore backfill | None for HA; SA via seeder sync (`tenants:sync-permissions`) |

README deploy section: tenant migrate + both backfills + `tenants:sync-permissions` in the same deploy action as the code.

---

## 5. Real-role effect (after code + backfills)

| Role | After |
|---|---|
| Super Admin | view/create/edit/delete + trash + restore + login deactivate/reactivate |
| Hospital Administrator | view/create/edit/soft-delete; **no** trash/restore |
| Doctor | `view doctor assignments` for own assignments page; still no admin doctors CRUD from seeder |
| Other roles | unchanged |

---

## 6. Redirects after write (AD-1)

Use `App\Http\Controllers\Concerns\RedirectsAfterWrite`:

| Action | Prefer if `view doctors` | Fallback |
|---|---|---|
| `store` | `doctors.index` | `doctors.create` |
| `update` | `doctors.index` | `doctors.edit` |
| `destroy` | `doctors.index` | `back()` |
| `restore` | `doctors.index` | `doctors.trashed` |

---

## 7. UI

| View | Control | Gate |
|---|---|---|
| `index.blade.php` | Add New Doctor | `@can('create doctors')` |
| `index.blade.php` | Link to Trash | `@can('restore doctors')` |
| `index` DataTables Edit | `data-can-edit` + JS | `edit doctors` |
| `index` DataTables Soft-delete | `data-can-delete` + JS | `delete doctors` — confirm “Move to trash” |
| `index` View | always for list viewers | `view doctors` |
| `show.blade.php` | Edit | `@can('edit doctors')` |
| `show.blade.php` | Soft-delete | `@can('delete doctors')` |
| `trashed.blade.php` (**new**) | Restore | `@can('restore doctors')` |
| Assignments view / any entry link | | `@can('view doctor assignments')` |

Sidebar doctors admin link unchanged (`view doctors` + module).

---

## 8. Assignments access path

```php
// doctor.assignments middleware: permission:view doctor assignments
// Controller (no hasRole):
$doctor = Doctor::where('user_id', auth()->id())->first();
if (!$doctor) {
    abort(404);
}
```

Still shows that user’s assigned patients only (existing query). Soft-deleted doctor profile → 404 on assignments (default scope).

---

## 9. Tests (plan will task these)

New `tests/Feature/Permissions/DoctorsRouteScopingTest.php` (+ focused feature tests as needed):

- D6 deny / allow / side-effect per admin route (including data + email-available).
- Soft delete: `deleted_at` set; user row remains; TenantUser mapping removed; excluded from index/data.
- HA-shaped: destroy OK; trashed/restore **403**.
- SA restore: doctor returns; status active; TenantUser re-registered.
- Assignments: holder 200; non-holder 403; no role-name check in controller.
- AD-1 redirects; UI gates.
- Backfill command tests (both commands).
- Relation `withTrashed` + exists reject trashed `doctor_id` (at least one Visit/Appointment path).
- Registry test updated for new permission names.

---

## 10. Execution note

Doctors is Wave 3 module 2. Remaining later: Visits, Appointments, Billing, Services, Laboratory, Imaging.

Implementation: isolated worktree from `origin/main`, TDD, no merge/push until explicit approval. Both backfills + tenant migrate + SA permission sync in the **same deploy action** as the code.
