# Ungated module UI surfaces (beyond sidebar)

> Track 2 only. Investigation. No code changes. Complements `2026-09-01-backup-sidebar-drift-investigation.md` (sidebar). Catalog / single-gate design is **out of scope** for this pass.

**Date:** 2026-09-01  
**Method:** grep of `resources/views`, `resources/js`, form requests, visit workflow handlers, vs `SidebarService` + `CheckModule` + `ModuleRegistry`.

---

## Headline

`hasModule()` does not appear in any Blade or JS file. Plan entitlement is applied in:

| Layer | Entitlement check |
|---|---|
| Sidebar | `SidebarService` (`hasModule` AND Spatie `can`) |
| HTTP routes | `CheckModule` in the tenant middleware group (plan JSON only) |
| Settings tabs | `SettingsController` + `AppServiceProvider` view composer (`hasModule` per section) |

Every other control — dropdown option, quick-action, stat label, workflow tab, form checkbox — is **Spatie-only, always-on, or hardcoded**. Clicking a hidden-from-sidebar feature may still **403** (if `CheckModule` maps that route to the module) or **succeed** (if the route is registered under a parent module such as `visits.*` or `bills.*`).

---

## Confirmed examples (the two that were already reported)

### Visit-type selectors

Live create flow is **not** the old dropdown.

`VisitController::create` requires `visit_type` in `{opd,ipd,emergency}` and renders `admin.visits.create.{type}` with a **hidden** `visit_type`. Missing type redirects to `visit_type=opd`.

`resources/views/admin/visits/create.blade.php` still contains an ungated `<select>` with OPD / IPD / Emergency. The controller never returns that view. Dead code; still a landmine if rewired.

**Live ungated Emergency control:** `resources/js/patients-index.js` `buildVisitRegisterLinks()` always emits OPD and Emergency POST buttons when `@can('create visits')`. No `hasModule('emergency')` / `hasModule('visits')`. `QuickRegisterVisitRequest` allows `opd|emergency` with no plan check.

Route layer: `CheckModule` + `ModuleRegistry::moduleForRequest` **does** map `visits.*` + `visit_type=emergency` to module `emergency`. So the Emergency button is visible without the module; submit should 403 if Emergency is off the plan. OPD button is visible without checking `visits` in the UI (patients page itself is `patients` module).

### Dashboard quick actions

`resources/views/admin/dashboard.blade.php` (admin branch) has **no** `hasModule` / `@can` around:

- Search-result **Add Visit** → `visits.create` (then forced `visit_type=opd`)
- Search-result **Schedule Appointment** → `appointments.create`
- Search-result **View History**
- Grid **Add New Patient**, **View All Patients**, **Schedule Appointment** again

`DashboardController` near-expiry banner is role-gated (`Super Admin` / `Hospital Administrator` / `Pharmacist`) then links to `inventory.expiring` (pharmacy routes). No `hasModule('pharmacy')`.

Doctor dashboard always shows **My Appointments**, **OPD Visits**, **IPD Visits**, **Emergency Cases** counts with no module filter.

---

## Evidence table

Legend for **Route after click**: what `CheckModule` does if the user activates the control. UI mismatch is independent of 403-vs-success.

| Surface | Expected gate (same idea as sidebar / catalog) | Actual behavior | Route after click | Match |
|---|---|---|---|---|
| Patients index row actions: Register OPD / Register Emergency (`patients-index.js`) | Show OPD only if `visits`; Emergency only if `emergency` | Both buttons if Spatie `create visits` | POST `visits.quick-register`; CheckModule uses posted `visit_type` | **Mismatch** |
| `admin/visits/create.blade.php` visit type `<select>` | Options filtered by `visits` / `ipd` / `emergency` | All three always listed | Dead view today | **Mismatch** (dead) |
| Typed `visits.create?visit_type=emergency` page | Must not be linked unless `emergency` | No in-app link except sidebar (gated) + patients JS (ungated) + crafted URL | GET gated by CheckModule → `emergency` | UI **mismatch** where linked; URL **match** |
| Dashboard “Schedule Appointment” (search + grid) | `appointments` | Always shown on admin dashboard | `appointments.create` → 403 if module off | **Mismatch** |
| Dashboard “Add Visit” | `visits` | Always shown | `visits.create` → default OPD / `visits` | **Mismatch** |
| Dashboard stat: Appointments | Hide or N/A without `appointments` | Always counted | Display only | **Mismatch** |
| Dashboard stat: Departments | `departments` | Always counted | Display only | **Mismatch** |
| Dashboard stat: Emergency Cases | `emergency` | Always counted (`visit_type = emergency`) | Display only | **Mismatch** |
| Dashboard near-expiry medicine banner | `pharmacy` | Role list only, not plan | `inventory.expiring` → pharmacy CheckModule | **Mismatch** |
| Doctor dashboard Appointment / OPD / IPD / Emergency tiles | Respective modules | Always shown for Doctor role | Display only | **Mismatch** |
| Bills create/edit `bill_type` (opd, ipd, emergency, pharmacy) | Each value needs matching module (`visits`/`ipd`/`emergency`/`pharmacy`) plus `billing` | All options always | `bills.store` is **billing** only — pharmacy/emergency **bills can be saved without those modules** | **Mismatch** (strong) |
| Bill line `item-type` Lab test / Imaging study (`bills/partials/item-row.blade.php`) | `laboratory` / `imaging` | Always in the select | Stays on billing routes | **Mismatch** (strong) |
| Taxes “applies to” checkboxes: opd, ipd, emergency, lab, imaging, pharmacy | Matching modules | Hardcoded list | `taxes.*` → billing | **Mismatch** |
| Doctor-share rule `applies_to` (create/edit): opd, ipd, lab, imaging, emergency | Matching modules | Hardcoded | `doctor-share.*` | **Mismatch** |
| Doctor-share reports `bill_type` filter: opd, ipd, investigation, emergency | Nested modules | Hardcoded | doctor-share | **Mismatch** |
| Reports Investigation Report `test_type` lab / radiology | `laboratory` / `imaging` (and `reports`) | Both options always | `reports.*` → reports module only | **Mismatch** |
| Sidebar already shows every report child with only `reports` + `view reports` | Nested pharmacy/IPD/lab reports should follow those modules | Same ungated set reachable once Reports is on | reports CheckModule | **Mismatch** (in-page same as sidebar flag) |
| OPD workflow Lab + Imaging order sections | `laboratory` / `imaging` | `OpdVisitHandler` sets `show_investigations => true` always | `visits.order-multiple-lab-tests` / `...-imaging-studies` are `visits.*` → **succeeds without laboratory/imaging** | **Mismatch** (strong) |
| IPD tabs Lab / Imaging | `laboratory` / `imaging` | Shown when `show_investigations` (always true on IPD handler) | same visits.* orders | **Mismatch** (strong) |
| OPD / IPD / Emergency prescription panel | `pharmacy` | Included on all three layouts; IPD Prescription tab has no `show` flag | `visits.prescription` is `visits.*` | **Mismatch** (strong) |
| IPD admission bill links | `billing` | Rendered from IPD workflow with no `hasModule('billing')` | `bills.show/edit/print` → billing 403 if off | **Mismatch** (UI) |
| Radiology result / lab result links on investigation cards | `imaging` / `laboratory` | Always if an order exists | `radiology-results.*` / `lab-results.*` — CheckModule **does** gate those prefixes | **Mismatch** (UI); route **match** |
| Settings section tabs | `settings` + child slug | View composer skips sections without `hasModule` | SettingsController same | **Match** |
| OT surgery `surgery_type` Emergency | Not the ED module; clinical surgery class | Always listed | `ot` / surgeries | **N/A** (name collision) |
| Ward / bed `*_type` Emergency | Not the ED module | Always listed | `ipd` wards/beds | **N/A** (name collision) |
| Patient form emergency_name / emergency_phone | Contact fields, not ED module | Always | patients | **N/A** |
| Layout trial “Upgrade Now” → subscription | Not a clinical module; Subscription is missing from SidebarService (sidebar audit) | Shown when `onTrial()` | `subscription.index` | Flag (already in sidebar report) |

---

## Pattern (for the later catalog spec — do not implement here)

Two leak shapes, both caused by there being **no UI helper** equivalent to `CheckModule`:

1. **Show then 403** — dashboard Appointments, patients Emergency button, IPD bill links. Sidebar hides; page chrome still offers the action; middleware blocks.
2. **Show and succeed under a parent route** — billing `bill_type=pharmacy`, OPD lab/imaging orders, visit prescriptions. Sidebar hides Pharmacy / Laboratory / Imaging; the parent module’s form still writes that data because the route prefix is `bills.` or `visits.`.

Visit-type OPD/IPD/Emergency still depends on `visit_type` query/body guessing inside `ModuleRegistry`. That is why the catalog work for those three waits on the Visit CTI refactor.

Backup remains the smallest proof case: one slug, sidebar AND Spatie, no visit_type guessing.

---

## Out of scope

- No fixes in this pass.
- Sidebar inventory: existing Backup report.
- Unified catalog + two-tier provisioning: next spec, after both tracks.
