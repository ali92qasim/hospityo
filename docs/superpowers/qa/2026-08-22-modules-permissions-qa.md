# Modules & Permissions QA Checklist

**Date:** 2026-08-22  
**Branch:** `plan/super-admin-modules-permissions`  
**Scope:** Manual verification of module gating, granular permissions, sidebar visibility, and role defaults after Phase D.

Use a tenant on a plan with **all modules enabled** unless a row explicitly says to disable a module. Mark each cell: ✅ pass · ❌ fail · ⏭ skip (not applicable).

---

## Setup

- [ ] Run `php artisan tenants:sync-permissions` on the test tenant
- [ ] Run `php artisan tenants:migrate-coarse-permissions` if upgrading from legacy coarse permissions
- [ ] Confirm Super Admin can open **Roles** and see permissions grouped by module
- [ ] Note tenant slug and plan modules under test: _______________

---

## Role × Module Matrix

| Module / Area | Super Admin | Hospital Administrator | Doctor | Nurse | Receptionist | Lab Technician | Pharmacist |
|---------------|:-----------:|:----------------------:|:------:|:-----:|:------------:|:--------------:|:----------:|
| **Dashboard** | | | | | | | |
| Loads without error | | | | | | | |
| Sidebar matches role permissions | | | | | | | |
| **Patients** | | | | | | | |
| Sidebar link visible | | | | | | | |
| List / create / edit as allowed | | | | | | | |
| **Visits (OPD / Emergency / IPD)** | | | | | | | |
| OPD link visible when `visits` module on | | | | | | | |
| Can open visit workflow | | | | | | | |
| IPD group visible with wards/beds perms | | | | | | | |
| **Appointments** | | | | | | | |
| Sidebar + calendar access | | | | | | | |
| **Billing** | | | | | | | |
| Bills / services visible per permission | | | | | | | |
| Tax config hidden without bill access | | | | | | | |
| **Pharmacy** | | | | | | | |
| Sidebar hidden when `pharmacy` module off | | | | | | | |
| POS opens in new tab for pharmacist | | | | | | | |
| Medicines / inventory / purchases | | | | | | | |
| **Laboratory & Imaging** | | | | | | | |
| Lab group (not legacy “Diagnostics”) | | | | | | | |
| Imaging group when radiology perm | | | | | | | |
| Routes blocked without `laboratory` module | | | | | | | |
| **Accounting** | | | | | | | |
| Sidebar when module + view perms | | | | | | | |
| Journal entries route gated | | | | | | | |
| **HR & Payroll** | | | | | | | |
| HR group with `view employees` or `view hr` | | | | | | | |
| Payroll / attendance sub-links | | | | | | | |
| **Doctor Share** | | | | | | | |
| Group visible with share permissions | | | | | | | |
| **Reports** | | | | | | | |
| Reports group when module + perm | | | | | | | |
| **Access Control (RBAC)** | | | | | | | |
| Users list for admin roles | | | | | | | |
| Roles / permissions management | | | | | | | |
| Audit logs when `audit` module on | | | | | | | |
| **Backup** | | | | | | | |
| Backup link for backup permissions | | | | | | | |
| **Settings** | | | | | | | |
| Settings access per role defaults | | | | | | | |

---

## Per-Role Smoke Tests

### Super Admin

- [ ] Has every permission from `RolePermissionSeeder`
- [ ] Can assign any permission to a custom role
- [ ] Sees all module groups enabled on full plan

### Hospital Administrator

- [ ] HR sidebar with Employees, Payroll, Attendance
- [ ] Pharmacy, Lab, Accounting, Reports accessible on full plan
- [ ] Can manage users (`view users`, `manage user roles`)
- [ ] Cannot access super-admin landlord routes

### Doctor

- [ ] Patients (view/edit), Visits, Appointments, Bills (limited)
- [ ] Lab results / investigation orders read access
- [ ] No HR, RBAC, backup, or accounting sidebar groups

### Nurse

- [ ] Patients, Visits, Wards/Beds (IPD)
- [ ] OT checklist / consumables / sterilization permissions
- [ ] No billing create beyond view, no pharmacy POS

### Receptionist

- [ ] Patients CRUD, Appointments, Visits create
- [ ] Bills + payments
- [ ] Settings (`manage settings`) only — no HR/pharmacy admin

### Lab Technician

- [ ] Laboratory sidebar: tests, orders, results
- [ ] Imaging when radiology permissions present
- [ ] No pharmacy or HR groups

### Pharmacist

- [ ] Full pharmacy sidebar on `pharmacy` module
- [ ] POS dispense flow
- [ ] No accounting, HR, or RBAC groups

---

## Module Gate Regression

Disable one module at a time on a copy tenant (or via plan edit) and confirm:

| Module disabled | Route to test | Expected |
|-----------------|---------------|----------|
| `pharmacy` | `/pharmacy/pos` | 403 Forbidden |
| `laboratory` | Lab orders index | 403 Forbidden |
| `imaging` | Imaging orders index | 403 Forbidden |
| `accounting` | Journal entries | 403 Forbidden |
| `hr` | HR employees index | 403 Forbidden |

- [ ] Sidebar omits disabled module groups
- [ ] Direct URL returns 403, not 500

---

## Permission Sync Commands

- [ ] `tenants:sync-permissions` — idempotent (safe to run twice)
- [ ] New permission added to seeder appears after sync
- [ ] `plans:sync-modules` — imaging added when laboratory present
- [ ] Coarse → granular migration preserves access (`view hr` → granular HR views)

---

## Sign-off

| Tester | Date | Environment | Result |
|--------|------|-------------|--------|
| | | | |

**Notes:**
