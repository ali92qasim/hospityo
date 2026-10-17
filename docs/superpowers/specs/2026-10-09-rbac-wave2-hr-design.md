# RBAC Wave 2: HR design

**Date:** 2026-10-12  
**Status:** Confirmed inputs below. Implementation waits for the combined Wave 2 plan after Wards/Beds design is also confirmed.  
**Investigation:** `2026-10-09-rbac-wave2-hr-investigation.md`  
**Branch / worktree:** `feat/rbac-wave2-departments-hr-wards` @ `../saasy-rbac-wave2` (docs only; tip still `06dd82c` + docs).

## Confirmed decisions (HR-1…HR-6)

- **HR-1** — Scope all 73 `hr.*` routes to one permission per action (D6); `view hr` only on reads (D5). → **Yes.**
- **HR-2** — Close H1–H11 for Hospital Administrator with **no seeder change** and **no backfill** (AC-1). Privileged approve/verify/swap actions are **Super Admin only**. Destroy escalations close (Departments precedent). → **Yes.**
- **HR-3** — Apply AD-1 via shared `RedirectsAfterWrite` on HR writes that redirect to lists. → **Yes — automatic.**
- **HR-4** — Gate ungated Blade action controls with `@can` / `@canany`. → **Yes.**
- **HR-5** — Leave CheckModule and HR catalog child slugs (`hr.employees`, `hr.attendance-leave`, `hr.payroll`, `hr.scheduling`) / sidebar module checks unchanged. → **Yes.**
- **HR-6** — Include `hr.department-staff.*` in this module (not Departments). → **Yes.**

**Product clarification (from confirm):** Super Admin only for approve/reject leave, approve payroll, verify/unverify documents, and approve/reject shift swaps. Hospital Administrator remains able to **view** those surfaces where a read exists, and keeps seeder-granted operational **view/create/edit** on HR clusters — but loses the escalate-via-bug write paths listed in H1–H11. Live HA already matches the seeder; closing needs **no** seeder edit and **no** backfill command.

**Carry-forward rules:** D2 (create never implies view), D5 (`view hr` viewing only), D6 (one permission per route), AD-1 (`App\Http\Controllers\Concerns\RedirectsAfterWrite`), Spatie permissions only (no role-name checks), AC-1 as above.

**Out of scope:** CheckModule / catalog slug changes; inventing new permission names; Departments resource; Wards/Beds; Nurse ward/bed decision.

---

## 1. Route mapping (73 routes / 9 clusters)

Shape: replace group / resource any-of middleware with **per-route** `->middleware('permission:…')` (Wave 1 / Departments pattern). URLs, names, and verbs unchanged.

Read routes may keep the deprecated read-superset **`view hr`** (D5). Write routes must **not**.

### 1a. `hr.employees` (9) — `EmployeeController`

| Route | After |
|---|---|
| `hr.employees.index` | `view employees\|view hr` |
| `hr.employees.create` | `create employees` |
| `hr.employees.store` | `create employees` |
| `hr.employees.show` | `view employees\|view hr` |
| `hr.employees.edit` | `edit employees` |
| `hr.employees.update` | `edit employees` |
| `hr.employees.destroy` | `delete employees` |
| `hr.employees.upload-document` | `create employee documents` |
| `hr.employees.delete-document` | `delete employee documents` |

### 1b. `hr.designations` (7) — `DesignationController`

| Route | After |
|---|---|
| `hr.designations.index` | `view designations\|view hr` |
| `hr.designations.create` | `create designations` |
| `hr.designations.store` | `create designations` |
| `hr.designations.show` | `view designations\|view hr` |
| `hr.designations.edit` | `edit designations` |
| `hr.designations.update` | `edit designations` |
| `hr.designations.destroy` | `delete designations` |

### 1c. `hr.attendance` (4) — `AttendanceController`

| Route | After |
|---|---|
| `hr.attendance.index` | `view attendance\|view hr` |
| `hr.attendance.monthly` | `view attendance\|view hr` |
| `hr.attendance.mark` | `create attendance` |
| `hr.attendance.store-daily` | `create attendance` |

`store-daily` uses `updateOrCreate`; D6 still uses **`create attendance`** (mark-attendance action). No `view hr` on the write.

### 1d. `hr.leave` (7) — `LeaveController`

| Route | After |
|---|---|
| `hr.leave.index` | `view leave requests\|view hr` |
| `hr.leave.create` | `create leave requests` |
| `hr.leave.store` | `create leave requests` |
| `hr.leave.approve` | `approve leave requests` |
| `hr.leave.reject` | `approve leave requests` |
| `hr.leave.cancel` | `edit leave requests` |
| `hr.leave.balances` | `view leave balances\|view hr` |

### 1e. `hr.leave-types` (6) — `LeaveController`

| Route | After |
|---|---|
| `hr.leave-types.index` | `view leave types\|view hr` |
| `hr.leave-types.create` | `create leave types` |
| `hr.leave-types.store` | `create leave types` |
| `hr.leave-types.edit` | `edit leave types` |
| `hr.leave-types.update` | `edit leave types` |
| `hr.leave-types.destroy` | `delete leave types` |

### 1f. `hr.payroll` (16) — `PayrollController`

| Route | After |
|---|---|
| `hr.payroll.index` | `view payroll runs\|view hr` |
| `hr.payroll.show` | `view payroll runs\|view hr` |
| `hr.payroll.generate` | `create payroll runs` |
| `hr.payroll.cancel` | `edit payroll runs` |
| `hr.payroll.approve` | `approve payroll runs` |
| `hr.payroll.payslip` | `view payslips\|view hr` |
| `hr.payroll.print-payslip` | `view payslips\|view hr` |
| `hr.payroll.mark-paid` | `edit payslips` |
| `hr.payroll.components` | `view salary components\|view hr` |
| `hr.payroll.create-component` | `create salary components` |
| `hr.payroll.store-component` | `create salary components` |
| `hr.payroll.edit-component` | `edit salary components` |
| `hr.payroll.update-component` | `edit salary components` |
| `hr.payroll.destroy-component` | `delete salary components` |
| `hr.payroll.employee-salary` | `view employee salary\|view hr` |
| `hr.payroll.update-employee-salary` | `edit employee salary` |

### 1g. `hr.shifts` (12) — `ShiftController`

| Route | After |
|---|---|
| `hr.shifts.index` | `view shifts\|view hr` |
| `hr.shifts.create` | `create shifts` |
| `hr.shifts.store` | `create shifts` |
| `hr.shifts.edit` | `edit shifts` |
| `hr.shifts.update` | `edit shifts` |
| `hr.shifts.destroy` | `delete shifts` |
| `hr.shifts.roster` | `view duty roster\|view hr` |
| `hr.shifts.store-roster` | `create duty roster` |
| `hr.shifts.auto-generate` | `create duty roster` |
| `hr.shifts.swap-requests` | `view shift swaps\|view hr` |
| `hr.shifts.approve-swap` | `approve shift swaps` |
| `hr.shifts.reject-swap` | `approve shift swaps` |

### 1h. `hr.department-staff` (4) — `DepartmentStaffController`

| Route | After |
|---|---|
| `hr.department-staff.index` | `view department staff\|view hr` |
| `hr.department-staff.show` | `view department staff\|view hr` |
| `hr.department-staff.update-head` | `edit department staff` |
| `hr.department-staff.transfer` | `edit department staff` |

### 1i. `hr.documents` (8) — `DocumentManagementController`

| Route | After |
|---|---|
| `hr.documents.index` | `view hr documents\|view hr` |
| `hr.documents.compliance` | `view hr documents\|view hr` |
| `hr.documents.requirements` | `view hr documents\|view hr` |
| `hr.documents.create-requirement` | `create hr documents` |
| `hr.documents.store-requirement` | `create hr documents` |
| `hr.documents.destroy-requirement` | `delete hr documents` |
| `hr.documents.verify` | `verify hr documents` |
| `hr.documents.unverify` | `verify hr documents` |

**Totals:** 9 + 7 + 4 + 7 + 6 + 16 + 12 + 4 + 8 = **73**.

**Registry / seeder / backfill:** no changes. Global permission strings already exist for Super Admin. No HR backfill command.

---

## 2. Closures (H1–H11) — AC-1

| # | Routes closed for HA | Required permission (SA holds) | Seeder (HA) |
|---|---|---|---|
| H1 | `leave.approve`, `leave.reject` | `approve leave requests` | withheld (355–356) |
| H2 | `payroll.approve` | `approve payroll runs` | withheld (358) |
| H3 | `documents.verify`, `documents.unverify` | `verify hr documents` | withheld (366) |
| H4 | `shifts.approve-swap`, `shifts.reject-swap` | `approve shift swaps` | withheld (364) |
| H5 | `employees.delete-document` | `delete employee documents` | withheld (352) |
| H6 | `employees.destroy` | `delete employees` | withheld (351) |
| H7 | `designations.destroy` | `delete designations` | withheld (353) |
| H8 | `leave-types.destroy` | `delete leave types` | withheld (356) |
| H9 | `payroll.destroy-component` | `delete salary components` | withheld (360) |
| H10 | `shifts.destroy` | `delete shifts` | withheld (362) |
| H11 | `documents.destroy-requirement` | `delete hr documents` | withheld (366) |

HA **keeps** seeder-matched writes that are not escalations (e.g. `edit department staff`, `edit payslips`, `edit employee salary`, create/edit employees, generate payroll, mark attendance, roster writes).

Local impact: 1 HA user (demo-hospital). No other roles hold HR permissions locally.

---

## 3. Redirects after write (AD-1)

Use `RedirectsAfterWrite` on successful writes that today hard-redirect to an index/show list:

| Write | Preferred list | View gate |
|---|---|---|
| employee/designation/leave-type/shift/document-requirement store/update/destroy | matching `hr.*.index` | matching `view …` |
| attendance `store-daily` | `hr.attendance.index` | `view attendance` |
| leave store | `hr.leave.index` | `view leave requests` |
| payroll generate | `hr.payroll.show` (existing) — only if user can `view payroll runs`; else `back()` | `view payroll runs` |
| roster store / auto-generate | `hr.shifts.roster` | `view duty roster` |
| department-staff update-head / transfer | `show` / `index` as today | `view department staff` |

Fallback when the user lacks the list `view …`: `back()` with success flash (Wave 1 pharmacy delete pattern). Exact call sites locked in the combined plan.

Approve / reject / verify / cancel / mark-paid that already `back()` stay as-is (no list redirect).

---

## 4. UI (`@can` / `@canany`)

Inventory every action control in `resources/views/admin/hr/**` that posts or links to a write route; gate with the After permission from §1. At minimum (plan expands file:line):

| Surface | Controls |
|---|---|
| Employees index/show | create, edit, destroy, upload/delete document |
| Designations index | create, edit, destroy |
| Leave index | create, approve, reject, cancel |
| Leave types | create, edit, destroy |
| Attendance mark | submit (create attendance) |
| Payroll index/show | generate, approve, cancel, mark-paid |
| Components | create, edit, destroy |
| Employee salary | save (edit) |
| Shifts / roster / swaps | create/edit/destroy, roster writes, approve/reject swap |
| Department staff | update head, transfer |
| Documents | create/destroy requirement, verify/unverify |

Sidebar / CheckModule / catalog child slugs: **unchanged** (HR-5).

---

## 5. Tests (plan will task)

- New `HrRouteScopingTest` (or split by cluster): deny / allow / side-effect per route (D6); seeded HA cannot hit H1–H11; SA can.
- UI `@can` assertions for representative view-only vs editor vs approver.
- AD-1 redirect cases for list-bound writes.
- Existing `HrCatalogEntitlementTest`, `HrPermissionsTest`, `HrViewHrRoleFormTest` remain green (catalog untouched).

---

## 6. Deploy checklist — read-only Super Admin probe

**Not a write command.** Before (and after) Wave 2 go-live, run a **read-only** probe across every tenant that:

1. Lists each user holding the **Super Admin** role (id, email, name).
2. **Flags any tenant where nobody holds Super Admin.**

Why it matters for Wave 2 + Wave 1 closures already on `main`:

| Closure | Who retains the privileged action after scoping |
|---|---|
| Wave 1 Accounting `close fiscal years` / `pre-close` | Super Admin only (`close fiscal years`) |
| Wave 2 Departments `departments.destroy` | Super Admin only (`delete departments`) |
| Wave 2 HR H1–H11 (approve/verify/swap + destroys) | Super Admin only |
| Wave 2 Wards/Beds destroys (if confirmed like Departments) | Super Admin only (`delete wards` / `delete beds`) |

If a tenant has **zero** Super Admin users, those closed actions become unreachable for that tenant until an SA is assigned. The probe reports that risk; it must **not** grant roles or permissions.

**Shape (implementation later — plan task, then README):** artisan-style **report-only** command or documented one-liner that only SELECTs via the tenant connection after `makeCurrent()`, prints per-tenant SA users, exits non-zero (or prints a hard `FLAG`) when any tenant has none. **No** `givePermissionTo` / `assignRole` / seeder writes.

**README deploy checklist entry (to add when Wave 2 ships — draft text):**

```markdown
### Required Deploy Check: Super Admin coverage (RBAC Wave 2, read-only)

> Run in the same deploy window as Wave 2 route scoping. Writes nothing.
> Lists Super Admin users per tenant; flags tenants with none.
> Covers Wave 1 `close fiscal years` and all Wave 2 delete / approve closures
> that remain Super-Admin-only after scoping.

php artisan rbac:probe-super-admins
# (name locked in plan; must be read-only)
```

No HR permission backfill step — live HA already matches the seeder.

---

## 7. Execution note

HR is large (73 routes). Implementation is deferred to the **combined Wave 2 plan** after Wards/Beds design is confirmed, unless you ask to ship Departments+HR earlier.
