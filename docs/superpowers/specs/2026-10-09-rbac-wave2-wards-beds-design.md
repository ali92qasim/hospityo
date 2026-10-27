# RBAC Wave 2: Wards / Beds design

**Date:** 2026-10-14 (revised WB-3 → F3)  
**Status:** Confirmed inputs below. Implementation waits for the combined Wave 2 plan.  
**Investigation:** `2026-10-09-rbac-wave2-wards-beds-investigation.md`  
**Branch / worktree:** `feat/rbac-wave2-departments-hr-wards` @ `../saasy-rbac-wave2`.

## Confirmed decisions (WB-1…WB-6)

- **WB-1** — Scope all resource `wards.*` / `beds.*` routes to one permission per action (D6). → **Yes.**
- **WB-2** — Close HA `wards.destroy` / `beds.destroy` with **no backfill** (AC-1, Departments precedent). → **Yes.**
- **WB-3** — **F3 (replaces option D).** Nurse gets new permission **`change bed status`** instead of `edit beds`. No bed edit/create/delete; no ward create/edit/delete. → **Confirmed.**
- **WB-4** — Apply AD-1 via shared `RedirectsAfterWrite` on resource store/update/destroy (and status action as below). → **Yes — automatic.**
- **WB-5** — Gate index action controls with `@can` / `@canany`. → **Yes.**
- **WB-6** — Leave IPD module / sidebar `view wards` / `view beds` checks unchanged. → **Yes.**

**Carry-forward:** D2 (create never implies view; view permissions are read-only), D6, AD-1 (`RedirectsAfterWrite`), `@can` on every action control, Spatie only.

---

## 1. Route mapping

### Wards (6) — `WardController`

| Route | After |
|---|---|
| `wards.index` | `view wards` |
| `wards.create` | `create wards` |
| `wards.store` | `create wards` |
| `wards.edit` | `edit wards` |
| `wards.update` | `edit wards` |
| `wards.destroy` | `delete wards` |

### Beds resource (6) — `BedController`

| Route | After |
|---|---|
| `beds.index` | `view beds` |
| `beds.create` | `create beds` |
| `beds.store` | `create beds` |
| `beds.edit` | `edit beds` |
| `beds.update` | `edit beds` |
| `beds.destroy` | `delete beds` |

Shape: per-route `->middleware('permission:…')`. Controllers have no `show` actions; **`wards.show` / `beds.show` are removed** (dead routes, no app/Blade callers). **Full edit form / `beds.update` unchanged** (all fields, including status for editors).

### New status-only route (+1) — **13 bed/ward routes total in scope**

| Item | Proposal |
|---|---|
| Method / URI | `PATCH beds/{bed}/status` (POST acceptable if CSRF forms prefer it; same name either way) |
| **Route name** | **`beds.update-status`** |
| Controller | `BedController@updateStatus` (new) |
| Middleware | `permission:change bed status\|edit beds` |
| Body | `status` only |

**Superset:** `edit beds` is a **confirmed read/write superset** on this action so HA/SA (who hold `edit beds`, not the Nurse permission) can use the same list control without a second grant. **Agree** with that shape — same pattern as Wave 1 `manage pharmacy` on catalog writes. Disagree only if product wants status UI exclusive to the new permission (would force granting `change bed status` to HA too).

**Totals:** 6 wards + 6 beds resource + 1 status = **13** routes (was 15 before removing dead show routes).

---

## 2. Permission registration (WB-3 F3)

### PermissionRegistry

Under `ipd` → `beds` group, beside existing bed permissions:

```php
'beds' => [
    'view beds',
    'create beds',
    'edit beds',
    'delete beds',
    'change bed status',
],
```

### RolePermissionSeeder

1. Add `'change bed status'` to the global `PERMISSIONS` list (with the other bed lines ~73–76).
2. Nurse `ROLE_PERMISSIONS` becomes:

```php
'view wards', 'view beds', 'change bed status',
```

**Do not** give Nurse `edit beds`.

HA lines **322–323** unchanged (`view`/`create`/`edit` wards and beds; no deletes; no need for `change bed status` because `edit beds` supersets the status route).

Super Admin gets it via `Permission::all()`.

---

## 3. Status transitions — investigate & propose (do not decide)

### Bed status values today

Enum / validation: **`available` | `occupied` | `maintenance`** (`StoreBedRequest` / `UpdateBedRequest` / create & edit blades).

### What the admission flow already does

| Event | Code | Bed status set |
|---|---|---|
| Admit | `VisitController::admitPatient` | `occupied` |
| Discharge | `VisitController::dischargePatient` | `available` |

Admission row: `admissions.status` is `active` | `discharged` (default `active`). Active occupancy for a bed = admission with `bed_id = $bed->id` and `status = 'active'` (equivalently `discharge_date` null while status active).

Picker for admit only lists `status = available` beds (`IpdVisitHandler`).

### Proposed rules for `beds.update-status` (aligns with your default)

| Rule | Behaviour |
|---|---|
| Allowed **current** statuses | `available`, `maintenance` only |
| Allowed **target** statuses | `available`, `maintenance` only |
| Never set `occupied` by hand | If request `status === occupied` → **reject**, write nothing |
| Active admission | If any `admissions` row for this bed with `status = active` → **reject**, write nothing (even if bed status were inconsistent) |
| Current `occupied` | Reject any change via this action (occupancy is admit/discharge’s job) |
| Allowed transitions | `available` ↔ `maintenance` only |
| Reject response | Redirect **back** (or to list) with a clear **error** flash (e.g. “Cannot change status while the bed has an active admission.” / “Occupied status can only be set by admitting a patient.”); **no DB write** |
| Full `beds.edit` / `update` | **Unchanged** — admins with `edit beds` can still set any field including `occupied` on the full form |

**Confirm or override these transition rules before Task 12 implements `updateStatus`.**

---

## 4. Seeder + backfill

### Backfill command

**Create** `App\Console\Commands\BackfillChangeBedStatusPermission` (same shape as `BackfillCreatePrescriptionsPermission`):

| Constant / behaviour | Value |
|---|---|
| Signature | `rbac:backfill-change-bed-status {--dry-run}` |
| Permission | `change bed status` (create row if missing on real run; dry-run does not) |
| Target roles (names select only) | `['Nurse']` only |
| Idempotent | skip if Nurse already holds it |
| `--dry-run` | writes nothing; cache untouched |
| Tenants | current if `Tenant::checkCurrent()`, else all; testing fallback like prescriptions |
| Cache | per-tenant isolation on real runs |

**Report-only safety list (never grants):** every role that:

1. is **not** `Super Admin` or `Hospital Administrator`, and  
2. is **not** in the grant target list (`Nurse`), and  
3. holds **any** ward/bed permission (`view|create|edit|delete wards`, `view|create|edit|delete beds`, or `change bed status`), and  
4. holds **neither** `edit beds` **nor** `change bed status`,

is printed as `[report only] … holds a ward/bed permission but neither edit beds nor change bed status; not granted.`

That list is who **loses accidental bed editing** once any-of is removed and who also does not get the new status grant.

### Deploy

Same deploy action as Wave 2 code. README section (draft):

```markdown
### Required Deploy Step: `change bed status` (RBAC Wave 2, WB-3 F3)

> **Hard requirement.** Same deploy action as the Wave 2 code. Not before
> (command missing), not later (from the moment routes are scoped, Nurses
> cannot change bed status until the backfill runs).

# 1. Preview: writes nothing. Expect "would update 1 role(s)" (Nurse) per tenant.
#    Read every [report only] line.
php artisan rbac:backfill-change-bed-status --dry-run

# 2. Apply in the same deploy action. Grants `change bed status` to Nurse only.
php artisan rbac:backfill-change-bed-status
```

Keep the read-only Super Admin probe section from the HR design in the Wave 2 checklist.

---

## 5. Real-role effect (local)

| Role | Users | After scoping + backfill |
|---|---|---|
| Super Admin | 8 | all bed/ward perms + `change bed status` via sync |
| Hospital Administrator | 1 | loses destroys; keeps view/create/edit; uses status action via **`edit beds` superset** |
| Nurse | 1 | `view wards`, `view beds`, **`change bed status`**; **no** `edit beds` / create / delete; no ward writes |
| other roles | 0 | unchanged |

---

## 6. Redirects (AD-1)

| Write | After success |
|---|---|
| wards/beds store/update/destroy | `RedirectsAfterWrite` → index if `view …`, else form/`back()` |
| **`beds.update-status`** | Control lives on the **list**. Prefer `redirectAfterWrite('beds.index', [], ['view beds'], $msg)` (Nurse holds `view beds`); fallback `back()`. On **reject**, `back()->with('error', …)` — not the success helper |

---

## 7. UI

| View | Control | Gate |
|---|---|---|
| `wards/index` | Add / Edit / Delete | `@can` create/edit/delete wards |
| `beds/index` | Add Bed | `@can('create beds')` |
| `beds/index` | per-row Edit | `@can('edit beds')` |
| `beds/index` | per-row Delete | `@can('delete beds')` |
| `beds/index` | **per-row status control** (small select + submit, Blade-only) | `@canany(['change bed status', 'edit beds'])` |

Nurse sees status control only (plus list via `view beds`); **no** Edit or Delete. No JS row-action module (Blade-only — confirmed). Full edit form unchanged for `edit beds` holders.

Sidebar unchanged (WB-6).

---

## 8. Follow-up (not this wave)

The full bed edit form can still **reassign `ward_id` on an occupied bed** (and otherwise mutate master data while occupied). That applies to admins with `edit beds` too. **Log as a follow-up**; do not fix in Wave 2.

---

## 9. Tests (plan tasks)

- Seeded Nurse: can change a **free** bed’s status (`available` ↔ `maintenance`); **403** on `beds.edit`/`update`/`create`/`store`/`destroy` and every ward write; blocked status attempts write nothing.
- Occupied bed, or request `status=occupied`, or bed with active admission → rejected, write nothing.
- User with `edit beds` (no `change bed status`) can use `beds.update-status`.
- Backfill: Nurse granted; custom role in report-only list receives nothing; dry-run writes nothing.
- UI: Nurse has status control, no Edit/Delete; view-only has neither.

---

## 10. Execution note

Implementation is in the **combined Wave 2 plan** Phase 3 (Tasks 10–14) + closing gate. Departments and HR designs unchanged.
