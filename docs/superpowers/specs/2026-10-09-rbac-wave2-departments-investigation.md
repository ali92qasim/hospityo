# RBAC Wave 2: Departments investigation

**Date:** 2026-10-09  
**Status:** Findings only. **No code changes.** Design comes after confirmation of §5.  
**Code read:** tip `06dd82c` on branch `feat/rbac-wave2-departments-hr-wards` (`../saasy-rbac-wave2`). Main working tree remains on `fix/bug-fixes` with unrelated WIP.  
**Parent:** `2026-10-03-rbac-permission-escalation-investigation.md`  
**Precedent:** Wave 1 Accounting / OT / Pharmacy investigations and designs.  
**Wave position:** Wave 2 module 1 of 3 (Departments → HR → Wards/Beds). HR and Wards/Beds are **out of scope** for this document.

**Rules (carry forward):**
- D2: create does not imply view.
- D6: one permission per action, with before/after tests per route.
- AC-1: when scoping removes an admin ability, backfill only if `RolePermissionSeeder` intends it; seeder-withheld `delete …` is closed as the bug (no re-ask).
- AD-1: after a successful write, redirect to the list only when the user can view it; otherwise back to the originating form (shared `RedirectsAfterWrite` trait).
- Spatie permissions only.

**Method:**
- Read `routes/web.php` (departments resource), `DepartmentController`, FormRequests, four department blades, `SidebarService`, `PermissionRegistry`, `RolePermissionSeeder`, `ModuleRegistry`.
- Read-only SQL scan of department permissions / role holders / user counts across all 7 local tenant databases. No writes.

**Explicitly out of scope (other modules / later waves):**
- `department-staff.*` (HR permission module — Wave 2 module 2).
- `reports.department-performance` (reports permission — not this wave).
- Dashboard tile visibility (module entitlement; not a route-scoping change).

---

## 1. Routes (7): current gate and candidate permission

**Authorization layers:** `DepartmentController` has **no** `authorize` / `can` / `abort_*` calls. `StoreDepartmentRequest` / `UpdateDepartmentRequest` only check `auth()->check()`. The resource middleware is the only real gate.

| Route | Method | What it does | Current (any-of) | Candidate |
|---|---|---|---|---|
| `departments.index` | GET | List | `view\|create\|edit\|delete departments` | `view departments` |
| `departments.create` | GET | Create form | same | `create departments` |
| `departments.store` | POST | Creates department | same | `create departments` |
| `departments.show` | GET | Detail | same | `view departments` |
| `departments.edit` | GET | Edit form | same | `edit departments` |
| `departments.update` | PUT | Updates department | same | `edit departments` |
| `departments.destroy` | DELETE | Deletes department | same | `delete departments` |

**Total:** all 7 routes change. No coarse read-superset exists (unlike Accounting’s `view accounting`).

**The escalation today:** any one of the four department permissions opens every CRUD action.

**Registry:** `PermissionRegistry` already lists these four under module `departments`. No new permission names. No backfill command if AC-1 closes HA’s delete loss (§2).

---

## 2. Real-role impact (all 7 tenants)

| Role | Tenants holding these perms | Users (sum) | Department permissions held | Loses after scoping |
|---|---|---|---|---|
| Super Admin | 7 | 8 | all four | **nothing** |
| Hospital Administrator | 7 | **1** (demo-hospital only; role exists with 0 users in the other 6) | `view` + `create` + `edit`. **Not** `delete departments`. | **`departments.destroy` only** |
| every other role | 0 | 0 | none | nothing |

No direct user-level grants of any department permission exist in any tenant.

**Seeder intent:** HA gets `view` / `create` / `edit` departments; `delete departments` is withheld (same Wave 1 pattern). HA’s delete ability today exists **only through the any-of bug**.

**AC-1 automatic outcome:** **close** `destroy` for HA (no backfill). Super Admin unchanged. No deploy-time backfill command. Wave 2’s “admin-only backfill” label does **not** produce a Departments backfill — local impact is one HA user losing delete as intended.

---

## 3. UI coupling (must change with the routes)

**No department view has any `@can` / `@canany`.** Every action control renders for anyone who can open the page:

| View | Ungated control | Route it hits |
|---|---|---|
| `index.blade.php:15–18` | “Add New Department” | `departments.create` |
| `index.blade.php:71–73` | per-row Edit | `departments.edit` |
| `index.blade.php:74–80` | per-row Delete | `departments.destroy` |
| `show.blade.php:25–28` | Edit button | `departments.edit` |

**Sidebar** (`SidebarService.php:49–50`): already `view departments` (+ module). **No sidebar change.**

**Writes** (`store` / `update` / `destroy`) all redirect to `departments.index` → AD-1 via existing `RedirectsAfterWrite`.

---

## 4. Existing tests

- Sidebar / module entitlement tests cover `view departments` visibility.
- **No** per-action `DepartmentsRouteScopingTest` exists. Execution will need a new D6 suite + UI `@can` coverage (Wave 1 pattern).

---

## 5. Decisions for confirmation

| # | Question | Recommendation |
|---|---|---|
| **DEP-1** | Scope all 7 resource routes to one permission per action as in §1? | **Yes.** |
| **DEP-2** | HA loses `destroy` with no backfill (AC-1)? | **Yes — automatic.** |
| **DEP-3** | Apply AD-1 via existing `RedirectsAfterWrite` on `store` / `update` / `destroy`? | **Yes — automatic.** |
| **DEP-4** | Gate index / show action controls with `@can` as in §3? | **Yes.** |
| **DEP-5** | Leave `department-staff.*` and `reports.department-performance` for their own modules? | **Yes.** |

No ambiguous AC-1 cases. No new permissions. No backfill command.

---

## 6. Proposed next step (after confirm)

Write the Departments **design** from confirmed decisions, then run the **HR investigation** (Wave 2 module 2) before any implementation.
