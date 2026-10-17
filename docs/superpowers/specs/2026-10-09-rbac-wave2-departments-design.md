# RBAC Wave 2: Departments design

**Date:** 2026-10-09  
**Status:** Confirmed inputs below. Implementation waits for the combined Wave 2 plan after HR and Wards/Beds designs are also confirmed.  
**Investigation:** `2026-10-09-rbac-wave2-departments-investigation.md`  
**Branch / worktree:** `feat/rbac-wave2-departments-hr-wards` @ `../saasy-rbac-wave2` (tip currently `06dd82c` + docs only).

**Confirmed inputs:**
- DEP-1: scope all 7 resource routes to one permission per action.
- DEP-2: HA loses `destroy` with **no backfill** (AC-1). Verified: `RolePermissionSeeder` line **317** grants HA `'view departments', 'create departments', 'edit departments'` only — `delete departments` is absent. Introduced that way in `f3723f4` (2026-02-03); git history shows no later grant then removal. Live data matches seeder. Global `PERMISSIONS` list still defines `delete departments` (line 41) for Super Admin.
- DEP-3: AD-1 via existing `RedirectsAfterWrite` on `store` / `update` / `destroy`.
- DEP-4: gate index / show action controls with `@can` / `@canany`.
- DEP-5: leave `department-staff.*` and `reports.department-performance` for their own modules.
- D2, D6, Spatie-only. No role-name checks.

**Out of scope:**
- `hr.department-staff.*` (HR design).
- `reports.department-performance`.
- Dashboard tile / module entitlement (unchanged).
- Inventing routes for unused permissions.

---

## 1. Route mapping

| Route | Before (any-of) | After |
|---|---|---|
| `departments.index` | `view\|create\|edit\|delete departments` | `view departments` |
| `departments.create` | same | `create departments` |
| `departments.store` | same | `create departments` |
| `departments.show` | same | `view departments` |
| `departments.edit` | same | `edit departments` |
| `departments.update` | same | `edit departments` |
| `departments.destroy` | same | `delete departments` |

**Shape:** replace the single resource middleware string with per-route `->middleware('permission:…')` (Wave 1 pattern). URLs, names and verbs unchanged.

**Registry / seeder / backfill:** no changes. No backfill command.

## 2. Real-role effect

| Role | Users (local) | After |
|---|---|---|
| Super Admin | 8 | unchanged (holds all four). |
| Hospital Administrator | 1 | loses `departments.destroy` only (AC-1). Keeps view/create/edit. |
| every other role | 0 with these perms | unchanged |

## 3. Redirects after write (AD-1)

`store`, `update`, and `destroy` currently redirect to `departments.index`.

Use `RedirectsAfterWrite`:
- target list: `departments.index` when the user can `view departments`;
- fallback: create form after store; edit form after update; index is skipped on destroy for view-less users — fall back to `departments.create` is wrong for delete; prefer `back()` / originating index was view-gated… For destroy, a view-less deleter is rare (they'd need `delete` without `view`). Fallback: `redirect()->back()` with success flash, or `departments.create` is inappropriate. Match Wave 1 pharmacy delete pattern: redirect to index if viewable, else `back()`.

Exact fallback strings to lock in the plan: same helper signature already used in Accounting/Pharmacy.

## 4. UI

| View | Control | Gate |
|---|---|---|
| `index.blade.php` | Add New Department | `@can('create departments')` |
| `index.blade.php` | per-row Edit | `@can('edit departments')` |
| `index.blade.php` | per-row Delete | `@can('delete departments')` |
| `show.blade.php` | Edit button | `@can('edit departments')` |

Show / eye on index stays for anyone who can open the list (`view departments`).

Sidebar unchanged (`view departments` + module).

## 5. Tests (plan will task these)

- New `DepartmentsRouteScopingTest`: deny / allow / side-effect proof per route (D6); seeded-HA cannot destroy.
- UI assertions for gated controls (view-only vs editor).
- AD-1 redirect cases for store/update(/destroy).
- No registry/seeder changes → existing registry tests unchanged.

## 6. Execution note

Departments alone is small. Implementation is deferred to the **combined Wave 2 plan** after HR and Wards/Beds designs are confirmed, unless you ask to ship Departments earlier.
