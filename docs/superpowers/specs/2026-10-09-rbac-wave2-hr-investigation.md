# RBAC Wave 2: HR investigation

**Date:** 2026-10-09  
**Status:** Findings only. **No code changes.** Design comes after confirmation of §6.  
**Code read:** tip `06dd82c` + docs on `feat/rbac-wave2-departments-hr-wards` (`../saasy-rbac-wave2`).  
**Precedent:** Wave 1; Departments investigation/design (`2026-10-09-rbac-wave2-departments-*`).  
**Wave position:** Wave 2 module 2 of 3 (Departments ✓ → **HR** → Wards/Beds).

**Rules (carry forward):**
- D2: create does not imply view.
- D5: `view hr` is valid for **viewing only**; every HR route that accepts it for a write must be fixed.
- D6: one permission per action (+ confirmed read supersets).
- AC-1: seeder-intended abilities may be backfilled; any-of-only abilities are closed (recommend, don’t decide where workflow ambiguity exists).
- AD-1: `RedirectsAfterWrite` after successful writes.
- Spatie permissions only. No role-name checks.
- **CheckModule** and HR catalog child slugs (`hr.employees`, `hr.attendance-leave`, `hr.payroll`, `hr.scheduling`) stay untouched — only route permissions change.

**Method:**
- `php artisan route:list --json` filtered to `hr.*` (73 named routes, 9 second-level clusters).
- Read `routes/web.php:441–582`, HR controllers, `SidebarService` HR block, `PermissionRegistry` / `ModuleRegistry` / `RolePermissionSeeder`.
- Read-only SQL of HR-related permissions across 7 tenants. No writes.

**In scope:** all `hr.*` routes including `hr.department-staff.*`.  
**Out of scope:** Departments resource (separate design); Wards/Beds; Nurse ward/bed decision (later).

---

## 1. Inventory

| Cluster | Routes | Controllers |
|---|---|---|
| `hr.employees` | 9 | `EmployeeController` |
| `hr.designations` | 7 | `DesignationController` |
| `hr.attendance` | 4 | `AttendanceController` |
| `hr.leave` | 7 | `LeaveController` |
| `hr.leave-types` | 6 | `LeaveController` |
| `hr.payroll` | 16 | `PayrollController` |
| `hr.shifts` | 12 | `ShiftController` |
| `hr.department-staff` | 4 | `DepartmentStaffController` |
| `hr.documents` | 8 | `DocumentManagementController` |
| **Total** | **73** | |

Earlier audit cited ~71 / 15 clusters; current tree has **73** named routes under **9** `hr.<cluster>` prefixes (middleware groups are finer-grained inside payroll/leave/shifts). No missing controller surface found relative to `ModuleRegistry` route prefixes.

**Module / catalog (untouched):**
- Parent `hr` + children `hr.employees`, `hr.attendance-leave`, `hr.payroll`, `hr.scheduling` in `ModuleRegistry`.
- Sidebar already gates items with `view <x> || view hr` + `hasModule(...)`.
- Existing tests (`HrCatalogEntitlementTest`, `HrPermissionsTest`, `HrViewHrRoleFormTest`) encode that contract — do not change CheckModule or child slugs in this wave.

---

## 2. Escalation pattern

Almost every cluster uses **CRUD any-of OR `view hr`**. Privileged actions often use **`approve/verify X|view hr`**, so coarse `view hr` alone opens writes (violates D5).

Nested write middleware (e.g. `department-staff.update-head`, `payroll.mark-paid`) still ORs `view hr`, so `view hr` alone passes those too.

---

## 3. Candidate mapping (shape for design)

Same Wave 1 rules:

| Kind | Candidate |
|---|---|
| List / show / read GET | `view <resource>\|view hr` (D5) |
| Create form + store | `create <resource>` (no `view hr`) |
| Edit form + update | `edit <resource>` (no `view hr`) |
| Destroy | `delete <resource>` (no `view hr`) |
| Approve / reject / verify / unverify | dedicated `approve …` / `verify …` only (no `view hr`) |
| Form-only pages with no list (if any) | action permission only (AC-2 style) |

**Specials already partly correct:**
- `leave.approve` / `reject` → today `approve leave requests|view hr` → candidate **`approve leave requests` only**
- `payroll.approve` → **`approve payroll runs` only**
- `documents.verify` / `unverify` → **`verify hr documents` only**
- `shifts.approve-swap` / `reject-swap` → **`approve shift swaps` only**
- `employees.upload-document` → `create employee documents` only (drop `view hr`)
- `employees.delete-document` → `delete employee documents` only
- `department-staff` index/show → `view department staff\|view hr`; update-head/transfer → `edit department staff` only
- `payroll.employee-salary` GET → `view employee salary\|view hr`; POST update → `edit employee salary` only
- `payroll.mark-paid` → `edit payslips` only
- Attendance `store-daily` → `create attendance` or `edit attendance` (design to pick; both exist) — not `view hr`
- Roster `store-roster` / `auto-generate` → `create`/`edit duty roster` — not `view`/`view hr` alone
- Payroll `generate` → `create payroll runs`; `cancel` → likely `edit` or `delete payroll runs` (design decision)

Full per-route table belongs in the design doc after §6 confirm.

---

## 4. Real-role impact (7 tenants)

| Role | Tenants | Users | HR-ish perms | Notes |
|---|---|---|---|---|
| Super Admin | 7 | 8 | all (~54) including approve/verify/deletes | unchanged after scoping |
| Hospital Administrator | 7 | **1** (demo-hospital) | 41 — matches seeder: view/create/edit across HR clusters, `view hr`, **no** approve/verify/delete* | see §5 |
| every other role | 0 | 0 | none | no frontline HR holders locally |

No direct user grants of HR permissions.

---

## 5. HA capabilities today that seeder does **not** grant (any-of / `view hr` bug)

Each row: capability reachable **now** → seeder intent → **recommendation** (not a decision).

### 5a. Privileged actions opened by `view hr`

| # | Routes | Required if scoped correctly | In HA seeder? | Recommendation |
|---|---|---|---|---|
| H1 | `hr.leave.approve`, `hr.leave.reject` | `approve leave requests` | **No** (lines 355–356: view/create/edit leave requests only) | **Close** (AC-1). Approvals are a distinct privilege; seeder never granted them. |
| H2 | `hr.payroll.approve` | `approve payroll runs` | **No** (358: view/create/edit payroll runs only) | **Close** (AC-1). |
| H3 | `hr.documents.verify`, `hr.documents.unverify` | `verify hr documents` | **No** (366: view/create/edit hr documents only) | **Close** (AC-1). |
| H4 | `hr.shifts.approve-swap`, `hr.shifts.reject-swap` | `approve shift swaps` | **No** (364: `view shift swaps` only) | **Close** (AC-1). |
| H5 | `hr.employees.delete-document` | `delete employee documents` | **No** (352: view/create employee documents only) | **Close** (AC-1). |

### 5b. Deletes opened by CRUD any-of (HA lacks `delete …` in seeder)

| # | Route(s) | Permission | In HA seeder? | Recommendation |
|---|---|---|---|---|
| H6 | `hr.employees.destroy` | `delete employees` | **No** | **Close** (AC-1). |
| H7 | `hr.designations.destroy` | `delete designations` | **No** | **Close** (AC-1). |
| H8 | `hr.leave-types.destroy` | `delete leave types` | **No** | **Close** (AC-1). |
| H9 | `hr.payroll.destroy-component` | `delete salary components` | **No** | **Close** (AC-1). |
| H10 | `hr.shifts.destroy` | `delete shifts` | **No** | **Close** (AC-1). |
| H11 | `hr.documents.destroy-requirement` | `delete hr documents` | **No** | **Close** (AC-1). |

### 5c. Not an escalation for HA (seeder already grants the write)

| Routes | Why HA keeps them after scoping |
|---|---|
| `department-staff.update-head`, `transfer` | seeder has `edit department staff` |
| `payroll.mark-paid` | seeder has `edit payslips` |
| `payroll.update-employee-salary` | seeder has `edit employee salary` |
| leave cancel | seeder has `edit leave requests` (cancel middleware is edit\|delete\|view hr) |
| generate payroll / store attendance / roster writes / CRUD create+edit | seeder has matching create/edit |

### 5d. Ambiguity (recommend only)

If product intent is that Hospital Administrator **should** approve leave/payroll/swaps or verify documents in real hospitals, that would be a **seeder + backfill** change (new grants), not preserving a bug. Locally only 1 HA user is affected. **Recommendation remains Close (H1–H4)** unless you explicitly choose to expand the seeder.

No other roles hold HR permissions locally, so there is **no frontline backfill** candidate in live data. Any “admin-only backfill” for Wave 2 HR would only apply if you choose to **add** seeder-intended permissions that live DB is missing — none found (live HA matches seeder).

---

## 6. Decisions for confirmation

| # | Question | Recommendation |
|---|---|---|
| **HR-1** | Scope all 73 `hr.*` routes per §3 (D5: `view hr` on reads only; never on writes)? | **Yes.** |
| **HR-2** | Close H1–H11 for HA with no backfill (AC-1)? | **Yes**, unless you override H1–H4 as seeder expansions. |
| **HR-3** | Apply AD-1 via `RedirectsAfterWrite` on HR writes that redirect to lists? | **Yes — automatic.** |
| **HR-4** | Gate ungated Blade action controls with `@can` / `@canany`? | **Yes** (inventory in design). |
| **HR-5** | Leave CheckModule + `hr.*` child catalog slugs / sidebar module checks unchanged? | **Yes.** |
| **HR-6** | Include `hr.department-staff.*` here (not Departments)? | **Yes** (already in this doc). |

---

## 7. Proposed next step (after confirm)

Write the HR **design** doc from confirmed decisions, then start the **Wards/Beds investigation** (Nurse ward/bed CRUD left as your decision — not auto-closed).
