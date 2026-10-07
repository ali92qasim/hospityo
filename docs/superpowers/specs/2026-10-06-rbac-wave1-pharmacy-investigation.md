# RBAC Wave 1 (continued): Pharmacy investigation (incl. D1 prescriptions)

**Date:** 2026-10-06
**Status:** Findings only. **No code changes.**
**Code read:** `main` @ `6d891b3`.
**Parent:** `2026-10-03-rbac-permission-escalation-investigation.md`
**Rules:**
- D1: prescriptions move out of `edit visits` and onto pharmacy permissions.
- D2: create does not imply view.
- D5: `view pharmacy` / `view services` are view-only.
- D6: one permission per action.
- W1-1 precedent: `manage …` is a superset.
- AC-1: admins are backfilled only for seeder-intended abilities. Applied automatically.
- AC-2 precedent: form pages are gated on their action.
- AD-1: post-write redirect fallback.

**Method:**
- `php artisan route:list --json`: **82 routes**, with the middleware resolved.
- A dead-method check: the app was booted and `method_exists` run on every route action.
- Read the 12 controllers, the related FormRequests, `SidebarService.php:123-160`, the views, `PermissionRegistry` and `RolePermissionSeeder`.
- Read-only SQL role scan of the 7 tenants.

---

## 1. Authorization layers

- **No permission checks** in any pharmacy controller (`authorize`/`can`/`abort_*`).
- **FormRequests:** `CreatePrescriptionRequest` checks login plus the `pharmacy` module, and `StorePrescriptionRequest` checks login only. The only permission check in any FormRequest in the app is `QuickRegisterVisitRequest`, which is unrelated.
- So the route middleware is the only gate, as in every other module.

## 2. Permission vocabulary (registry)

| Area | Permissions | Note |
|---|---|---|
| Catalog | `view/create/edit/delete` × `medicines`, `medicine categories`, `brands`, `units` | |
| Inventory | `view inventory`\*, `manage inventory`\*, `create/edit/delete inventory` | \*marked DEPRECATED, but `view inventory` is the **only** view permission for stock pages |
| Purchases / Suppliers | `view/create/edit/delete` × each | there's no `approve purchases` |
| Prescriptions | `view/create/edit/delete prescriptions` | also used for the **instruction templates** catalog |
| POS | `view pos`, `dispense pharmacy` | |
| General | `view pharmacy` (DEPRECATED coarse view), `manage pharmacy` (registry comment: "bulk import operations") | |
| Foreign | `view services` (Services module) | OR'd into most pharmacy routes for historical reasons. D5 keeps it as a view-only grant. |
| Foreign | `edit visits` (Visits module) | **gates every prescription route today** (D1) |

**`manage pharmacy`:** the routes and sidebar already treat it as a superset everywhere, and the registry calls it "bulk import". Every holder (Super Admin, HA, Pharmacist) also holds every granular permission, so the choice has **zero impact**. I'm applying the **W1-1 precedent (superset)** automatically and not raising it as a decision.

## 3. Routes (82): today and candidate

Shorthand:
- **R** = coarse view set `view pharmacy|view services|manage pharmacy`, accepted on read GETs only (D5 + W1-1).
- **+mp** = `|manage pharmacy` superset.
- **+mi** = `|manage inventory` superset (inventory only).

### 3.1 Catalog: medicines, categories, brands, units (37 routes)

The 82 total breaks down as 37 catalog + 7 instructions + 7 suppliers + 7 purchases + 11 inventory + 4 POS + 9 prescriptions.
- **Today:**
  - categories, brands and units use the whole-resource any-of CRUD list plus `view services|view pharmacy|manage pharmacy`;
  - medicines are split by verb, but **each verb also accepts `view services|view pharmacy`**, so view-only users can create, edit and delete medicines.
- **Candidate** (each of the 4 resources):

| Action | Candidate |
|---|---|
| index, `*.data` (JSON list) | `view X\|R` |
| show (categories, brands) | `view X\|R` |
| create, store | `create X` +mp |
| edit, update | `edit X` +mp |
| destroy | `delete X` +mp |
| import, import-status | `manage pharmacy` (**unchanged**, single permission) |

**Dead:** `units.show` (no method), so it gets `->except(['show'])`.

### 3.2 Prescription instruction templates (7 routes)
- **Today:** any-of across `view/create/edit/delete prescriptions` plus R.
- **Candidate:**
  - index → `view prescriptions|R`
  - create/store → `create prescriptions` +mp
  - edit/update → `edit prescriptions` +mp
  - destroy → `delete prescriptions` +mp
- **Dead:** `prescription-instructions.show`, so it gets `->except`.

### 3.3 Suppliers (7) and purchases (7)
- **Suppliers:** per verb, the same shape as the catalog.
- **Purchases:**

| Route | Today | Candidate |
|---|---|---|
| index, show | any-of CRUD + R | `view purchases\|R` |
| create, store | same | `create purchases` +mp |
| approve (pending → approved) | **`view services`**\|manage pharmacy\|edit purchases | `edit purchases` +mp |
| receive (**creates stock batches**) | **`view services`**\|… | `edit purchases` +mp |
| cancel (terminal) | **`view services`**\|…\|delete purchases | `delete purchases` +mp (the OT-2 reasoning) |

### 3.4 Inventory (11 routes)

| Route | Today | Candidate |
|---|---|---|
| index, low-stock, expiring, opening-stock (page) | `view/create/edit/delete inventory\|R\|manage inventory` | `view inventory\|R` +mi |
| opening-stock import, import-status | `manage pharmacy\|manage inventory\|create\|edit inventory` | `create inventory` +mp +mi |
| stock-in (GET form), process-stock-in | **`view services`**\|mp\|mi\|create\|edit inventory | `create inventory` +mp +mi |
| stock-out (GET form), process-stock-out, medicines/{id}/batches (JSON for the stock-out form) | **`view services`**\|… | `edit inventory` +mp +mi |

`delete inventory` gates no route, because there's no delete action. It stays in the registry.

### 3.5 POS (4 routes)
- **Today:** the group is gated by `view pos|dispense pharmacy|manage pharmacy`, so **`view pos` can check out**: it creates a bill and deducts stock.
- **Candidate:**
  - index (the terminal: pending in-house prescriptions and the patient list), medicine search and prescription lookup → `view pos` +mp;
  - **checkout → `dispense pharmacy` +mp.**

### 3.6 Prescriptions: D1 (9 routes, all `edit visits` today)

| Route | What it does | Candidate |
|---|---|---|
| `visits.prescription` (POST) | **Prescribing from the visit workflow** (OPD/ER/IPD prescription panel) | `create prescriptions` |
| `prescriptions.create` / `.store` | Standalone prescribing page | `create prescriptions` |
| `prescriptions.index` / `.show` | Prescription list / detail (no sidebar entry; URL only) | `view prescriptions` +mp |
| **`prescriptions.dispense`** | **Deducts stock** and marks the prescription dispensed | **`dispense pharmacy`** +mp |
| `prescriptions.edit` / `.update` / `.destroy` | **Dead** (no methods) | `->except([...])` |

`manage pharmacy` is deliberately **not** accepted for prescribing. Writing a prescription is a clinical act, not pharmacy management.

**The panel itself:** `can_prescribe` is visit-state only (`VisitController:350`). It needs a permission term so that users who can no longer prescribe don't see the form: `&& auth()->user()->can('create prescriptions')`.

## 4. Real-role impact (all 7 tenants)

| Role | Users | Relevant permissions held | Loses after scoping | AC-1 / policy outcome |
|---|---|---|---|---|
| Super Admin | 8 | every pharmacy permission + `edit visits` | nothing | n/a |
| Hospital Administrator | 1 | every pharmacy permission (incl. deletes, which the seeder grants HA in pharmacy) + `edit visits` | nothing | n/a |
| Pharmacist | 0 | every granular pharmacy permission + R + `manage pharmacy`/`manage inventory` | nothing | n/a. **Corrects the parent doc,** which said the Pharmacist lacked the catalog write permissions; the seeder has since granted them. |
| **Doctor** | **11** | `edit visits` only | **prescribing** (`visits.prescription`), plus the URL-only prescription list/show and **dispense** | **Flagged: PH-1** |
| **Nurse** | **1** | `edit visits` only | the same as Doctor | **Flagged: PH-1** |
| Receptionist, Lab Technician, Medical Records Clerk, Test Role | 7 | none relevant | nothing | n/a |

**Catalog, inventory, suppliers, purchases and POS: zero real impact.** Every holder of any coarse permission also holds the granular one.

**Prescriptions: real impact on 12 users.** Doctor and Nurse:
- **Dispense: closed automatically as the bug.** Dispensing deducts pharmacy stock, it's the pharmacist's `dispense pharmacy` action, and it reached `edit visits` only through `2536e11` copying the prescriptions gate.
- **Prescription list/show (URL-only, no navigation): closed automatically.** Not a workflow; the visit panel already shows a visit's prescriptions.
- **Prescribing: flagged (PH-1).** The AC-1 test doesn't settle it:
  - the seeder grants neither role any `… prescriptions` permission;
  - **but** prescribing has always ridden on `edit visits`, which the seeder *does* grant both roles. So the seeder's intent was that Doctors and Nurses prescribe.
  - The Nurse case is a designed workflow too. In IPD, `IpdVisitHandler::showOrderDoctorPicker` deliberately gives non-care-team users (e.g. nurses) a doctor picker so they can order **on behalf of** a care-team doctor (`IpdClinicalService::resolveOrderDoctorId`).

## 5. UI coupling

- **Write controls:** about 55 references to write routes across the pharmacy views. The only existing gates are `@can('manage pharmacy')` / `@canany(['manage pharmacy','manage inventory'])` around the **import** controls (medicines, categories, brands, units, opening stock).
- **Ungated:**
  - every New, Edit and Delete in the catalog, suppliers, purchases and instructions;
  - approve, receive and cancel on purchases;
  - stock-in and stock-out;
  - dispense on the prescriptions index/show;
  - POS checkout.

  Each gets an `@can` matching its route; the design will list them file by file.
- **Sidebar** (`SidebarService.php:126-152`): every item is `view X || view pharmacy || manage pharmacy` (inventory: `|| manage inventory`). That already matches the read candidates, so **no change**. POS is `view pos || dispense pharmacy || manage pharmacy`. With checkout split off, a `dispense pharmacy`-only user would see a POS link they can't open, so drop `dispense pharmacy` from that condition.
- **Redirects (AD-1):** catalog, supplier and purchase stores and updates, and the inventory processes, redirect to their index pages. The AD-1 fallback is applied automatically. POS checkout and dispense use `back()` or the bill page; that will be checked in design.

## 6. Existing tests

- `tests/Feature/Pharmacy/*` has 93 tests, 3 of them in the known failing baseline (MedicineImport, PharmacyPosNavigation, UnitImport). **Any test that currently relies on `edit visits` to prescribe or dispense** will be found and updated in design. The visit-workflow tests in `tests/Feature/Visits/*` (127) are the likeliest.

## 7. Decision needed before design

| # | Question | Recommendation |
|---|---|---|
| **PH-1** | Once prescribing requires `create prescriptions` (D1), should **Doctor** (11 users) and **Nurse** (1 user) keep it? That means a backfill plus a seeder update, run **in the same deploy action** as the code (like `manage subscription`). | **Yes for both. Backfill `create prescriptions` only.** Prescribing was always seeder-intended through `edit visits`, and the IPD on-behalf-of-doctor picker makes Nurse ordering a designed workflow. If they don't keep it, 11 doctors stop being able to prescribe the moment it deploys. **Not backfilled** (closed as the bug): `view prescriptions` (URL-only list) and `dispense pharmacy`. **Side effect to accept:** instruction templates share the prescription permissions (§3.2), so `create prescriptions` also lets Doctors and Nurses **add** instruction templates (dosage text presets), though not edit or delete them. That's low risk. The alternative, moving template writes onto `manage pharmacy`, can be done in design if you prefer. |
