# Investigation: Backup & Restore missing from sidebar

**Date:** 2026-09-01  
**Status:** Report only — no code changes  
**Method:** Superpowers systematic debugging, Phase 1 (root cause). Live landlord `plans.modules` JSON plus tenant Spatie tables, read against `ModuleRegistry`, `CheckModule`, `SidebarService`, and `RolePermissionSeeder`.

This is **not** a catalog refactor design. It is the evidence base for whether to hotfix Backup separately, and for any later single-catalog work.

## Verdict (Backup bug)

There is **no slug typo**. The canonical string is `backup` (lowercase, no spaces or hyphens) in plan JSON, `ModuleRegistry`, CheckModule route prefixes, and SidebarService.

The sidebar does **not** appear from a super-admin plan tick alone. Visibility is:

`hasModule(tenant, 'backup')` **AND** `$user->canAny(['view backup', 'create backup', 'restore backup', 'delete backup', 'manage backup'])`.

Super admin never writes Spatie permissions. Ticking Backup & Restore only appends `"backup"` to **that plan’s** JSON. Hospitals on a different plan are unaffected.

That AND-gate, plus plan-scoped JSON (not per-tenant entitlements), is the mismatch. It is **not** a one-line `Backup` vs `backup` fix.

---

## STEP 1 — Backup & Restore end to end

### Code path (all layers use slug `backup`)

| Layer | Expected if super admin enabled Backup | Actual | Match |
|---|---|---|---|
| Plan JSON (`plans.modules`) | Slug `backup` present on the **assigned** plan | Exact slug is `backup`. Live **enterprise** includes it. Live **starter** and **professional** do **not**. | Match on enterprise; **mismatch** if the hospital is on starter/professional |
| `ModuleRegistry` | Entry whose key equals the plan slug | `'backup' => name Backup & Restore, routes ['backup.']` | Match |
| `CheckModule` | Tenant group middleware; `backup.*` routes resolve to `backup` | Backup routes live inside `Route::middleware('tenant')`. Prefix `backup.` maps to `backup`. No explicit `module:backup` alias needed. | Match |
| `SidebarService` | Show “Backup & Restore” when the plan has `backup` | Also requires Spatie `canAny` on the five backup permission names. | **Mismatch vs super-admin intent**: plan tick is not sufficient |
| Spatie | Permission names equal what the sidebar checks; assigned to the logged-in role | Names match: `view backup`, `create backup`, `restore backup`, `delete backup`, `manage backup`. Seeded onto **Super Admin** (all perms) and **Hospital Administrator** only. Not on Doctor, Nurse, Receptionist, Pharmacist, Lab Technician. | Names match; **role assignment is a second gate** |

### Live landlord / tenant snapshot (2026-09-01)

| Plan | `backup` in JSON? | Hospitals on this plan |
|---|---|---|
| starter | No | Three hospitals (starter tenants) |
| professional | No | None |
| enterprise | Yes (`"backup"`) | Two hospitals |

| Hospital (slug only) | Plan | Plan has `backup`? | Spatie backup rows | Roles with backup perms | Notes |
|---|---|---|---|---|---|
| hassan-health-care-centre | enterprise | Yes | All five names present | Super Admin, Hospital Administrator | 1 Super Admin user; 0 Hospital Administrator users; Receptionist/Doctor have none |
| demo-hospital | enterprise | Yes | **Zero** permissions and **zero** roles/users | None | Empty RBAC database — every permission-gated sidebar item would hide |
| Three starter hospitals | starter | **No** | Not required for this report | — | Super-admin Backup tick on **enterprise** cannot show here |

### Exact point of failure

1. **If the hospital’s plan JSON does not contain `backup`** (live starter hospitals): Layer 1 fails. Sidebar `hasModule('backup')` is false. Super admin “enabled Backup” on another plan, or never saved it onto this hospital’s plan. Not a registry slug bug.

2. **If the plan JSON does contain `backup` but the user lacks Spatie backup permissions** (Hassan Receptionist/Doctor, or demo-hospital’s empty permission tables): Layer 2 fails. Sidebar hides the link even though CheckModule would allow the route **if** the user also passed `permission:view backup|manage backup`. Super admin did not grant those Spatie names.

3. **If the user is Hassan Super Admin** (role has all five backup permissions, plan has `backup`): code and live data say the link **should** render. Remaining explanations would be UI (scroll/overflow), a different subdomain/hospital, or Spatie cache — not a slug mismatch. Confirm which hospital subdomain and which role were used before treating this as a one-line code fix.

---

## STEP 2 — Full module inventory (catalog evidence)

Legend for **Drift**: `ok` = slugs/gates agree for the happy path; `flag` = layers disagree or a layer is missing / special-cased.

| Feature | Plan JSON slug | ModuleRegistry slug | CheckModule coverage | Sidebar gating | Spatie name(s) checked for nav | Drift |
|---|---|---|---|---|---|---|
| Dashboard | none | none | Ungated | Always shown | none | ok (intentionally ungated) |
| Subscription | none | none | Ungated (`subscription.*`) | Hardcoded in `partials/sidebar.blade.php` for Super Admin / Hospital Administrator — **not** in `SidebarService` | Role names, not permissions | **flag**: second nav renderer; not a module |
| Patients | `patients` | `patients` | `patients.` | module + `view patients` | `view patients` | ok |
| Doctors | `doctors` | `doctors` | `doctors.`, `doctor.` | module + `view doctors` | `view doctors` | ok (`doctor.assignments` uses `doctor.` prefix) |
| Appointments | `appointments` | `appointments` | `appointments.`, `calendar.` | module + `view appointments` | `view appointments` | ok |
| OPD | `visits` | `visits` | `visits.*` / `test-orders.*` when visit type is opd or unset | module `visits` + `view visits` | `view visits` | **flag**: one slug serves OPD; Emergency/IPD visit URLs are remapped |
| Emergency | `emergency` | `emergency` | **Not** via `routes[]` (empty). `moduleForRequest` maps visit_type `emergency` | module `emergency` + `view visits` | `view visits` (shared with OPD) | **flag**: empty `routes`; Spatie not split from OPD |
| IPD (wards/beds) | `ipd` | `ipd` | `wards.`, `beds.` | module `ipd` + `view wards` / `view beds` | those view perms | ok for ward/bed routes |
| IPD (admitted list) | `ipd` (+ sidebar also wants `visits`) | visit URLs still `visits.*` | `visit_type=ipd` → module `ipd` | `ipd` **and** `visits` + `view visits` | `view visits` | **flag**: sidebar requires two modules; middleware only `ipd` for typed IPD visits |
| Departments | `departments` | `departments` | `departments.` | module + `view departments` | `view departments` | ok (this was the previous “link shows / route 403” hole; module check is now present) |
| Billing | `billing` | `billing` | `bills.`, `services.`, `taxes.` | module + `view bills` or `view services` | those | ok |
| Accounting | `accounting` | `accounting` | `accounting.` | module; each child has its own view perm or `view accounting` | granular accounting perms | ok |
| Pharmacy | `pharmacy` | `pharmacy` | medicines, inventory, POS, etc. | module; children OR `view pharmacy` / `manage pharmacy` | many pharmacy perms | ok (coarse aliases still accepted) |
| Laboratory | `laboratory` | `laboratory` | `lab.`, investigations, etc. | module + lab view perms per child | lab perms | ok |
| Imaging | `imaging` | `imaging` | `imaging.`, `radiology-results.` | module **and** `view radiology results` to show the whole group | `view radiology results` plus lab-like perms for children | **flag**: group hidden without radiology view even if imaging is on the plan |
| Operation Theatre | `ot` | `ot` | `ot.` | module + **`view surgeries` only**, then **all** OT children render | `view surgeries`; children `manage surgical checklists`, etc. unused for nav | **flag**: sidebar over-shows children vs Spatie |
| Doctor Share | `doctor-share` | `doctor-share` | `doctor-share.` | module; children or `manage doctor shares` | share perms | ok |
| HR | `hr` | `hr` | `hr.` | module; children or `view hr` | HR perms | ok |
| Reports | `reports` | `reports` | `reports.` | module + `view reports`; **all** report children listed | `view reports` only | **flag**: no per-report Spatie on nav |
| User & Role (RBAC) | `rbac` | `rbac` | `users.`, `roles.`, `permissions.` | module `rbac` + user/role/permission access | user/role/permission perms; Hospital Administrator/Super Admin bypass for Users | **flag**: Audit Logs live inside this group |
| Audit Logs | `audit` | `audit` | `audit-logs.` | Shown only if **`rbac` module** is on **and** `audit` module and `view audit logs` (or admin roles) | `view audit logs` | **flag**: audit entitlement hidden unless `rbac` is also granted |
| Backup & Restore | `backup` | `backup` | `backup.` | module **and** backup `canAny` | five backup names | **flag**: super-admin JSON cannot show nav alone |
| Settings (parent) | `settings` | `settings` | `settings.index` only | parent module + any settings section access | `access settings` / legacy view/edit/manage | ok as parent |
| Hospital Info | `settings.hospital-info` | `settings.hospital-info` | `settings.hospital-info`, `settings.update` | parent + child slug + Spatie section | `access settings.hospital-info` (or parent access) | ok |
| Prescription print | `settings.prescription-print` | `settings.prescription-print` | `settings.prescription-print-templates.` | parent + child slug + Spatie section | `access settings.prescription-print` | ok |
| Timezone detect | none | none (`settings.detect-timezone` unmapped) | Ungated at SaaS layer | not in sidebar | — | **flag**: intentional POS carve-out; catalog must keep it ungated |

### Additional drift the catalog must account for

- **Two nav implementations:** `SidebarService` vs hardcoded Subscription block in the Blade partial.
- **Visit-type special case** in `ModuleRegistry::moduleForRequest` is the only reason Emergency/IPD visit URLs are not stolen by `visits.`.
- **Enabling a module on a plan does not sync Spatie** onto tenant roles. Backup is the live example; any new module will repeat this.
- **Empty tenant RBAC** (demo-hospital: 0 permissions) makes every `can()` sidebar check fail even when enterprise JSON includes the module.

---

## What not to do yet

Do not hotfix until the reporter confirms **hospital subdomain** and **logged-in role**. If they are on a starter plan, the fix is plan JSON (or assigning enterprise), not a code slug change. If they are Hassan Super Admin and still cannot see the link, investigate UI/cache next — the data path already agrees.

Do not start the catalog refactor from a hypothetical Backup typo. The inventory flags above are the real inconsistencies.
