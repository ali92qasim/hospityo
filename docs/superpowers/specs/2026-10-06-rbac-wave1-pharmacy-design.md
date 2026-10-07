# RBAC Wave 1 (continued): Pharmacy design (incl. D1 prescriptions)

**Date:** 2026-10-06
**Status:** Draft, for review before the combined Wave 1 implementation plan.
**Investigation:** `2026-10-06-rbac-wave1-pharmacy-investigation.md`
**Confirmed inputs:**
- D1: prescriptions move onto pharmacy permissions.
- D2: create does not imply view.
- D5: `view pharmacy` / `view services` are view-only.
- D6: one permission per action, with before/after tests per route.
- W1-1: `manage pharmacy` (and `manage inventory`) are supersets.
- AC-1, AC-2: form pages are gated on their action.
- **AD-1:** conditional redirect, applied automatically.
- **PH-1:**
  - backfill `create prescriptions` to **Doctor** and **Nurse**, with the seeder updated, **in the same deploy action**, plus a README deploy note;
  - the instruction-template side effect is accepted;
  - dispense and the URL-only prescription list/show are closed.
- Spatie permissions only.

**Out of scope:**
- Laboratory/imaging ordering from visits (`visits.order-multiple-*`): Laboratory wave (D1).
- Stock, pricing and billing logic.
- The 3 known-baseline Pharmacy test failures.

**One change from the investigation (§3.5, POS):** see §2.5. It follows AC-2.

---

## 1. Notation

- **R** = `view pharmacy|view services|manage pharmacy`. Accepted on **read GETs only**.
- **+mp** = `|manage pharmacy`. **+mi** = `|manage inventory`.

## 2. Route mapping (82 routes)

**Shape:**
- Each `Route::resource(...)->middleware(any-of)` is split into `->only([...])` registrations, one per permission, as `medicines` already does.
- Non-resource routes get a per-route `->middleware`.
- URLs, names and verbs stay the same.
- **Dead routes are removed** with `->except()`: `prescriptions.edit/update/destroy`, `units.show`, `prescription-instructions.show`. That's 82 → **77** live routes.

### 2.1 Catalog: medicines, medicine-categories, medicine-brands, units

| Action | After |
|---|---|
| `index`, `show` (categories, brands), `medicines.data`, `units.data` | `view <X>\|R` |
| `create`, `store` | `create <X>` +mp |
| `edit`, `update` | `edit <X>` +mp |
| `destroy` | `delete <X>` +mp |
| `*.import`, `*.import-status` | `manage pharmacy` (unchanged) |

Here `<X>` is `medicines`, `medicine categories`, `brands` or `units`.

### 2.2 Prescription instruction templates
- `index` → `view prescriptions|R`
- `create`/`store` → `create prescriptions` +mp
- `edit`/`update` → `edit prescriptions` +mp
- `destroy` → `delete prescriptions` +mp

**Accepted side effect (PH-1):** Doctors and Nurses can **add** templates. They can't list, edit or delete them.

### 2.3 Suppliers and purchases
- **Suppliers:** per verb, as in §2.1 with `<X>` = `suppliers`.
- **Purchases:**

| Route | After |
|---|---|
| `index`, `show` | `view purchases\|R` |
| `create`, `store` | `create purchases` +mp |
| `approve`, `receive` | `edit purchases` +mp |
| `cancel` | `delete purchases` +mp |

### 2.4 Inventory

| Route | After |
|---|---|
| `inventory.index`, `.low-stock`, `.expiring`, `.opening-stock` | `view inventory\|R` +mi |
| `inventory.opening-stock.import`, `.import-status` | `create inventory` +mp +mi |
| `inventory.stock-in`, `.process-stock-in` | `create inventory` +mp +mi |
| `inventory.stock-out`, `.process-stock-out`, `.medicines.batches` | `edit inventory` +mp +mi |

### 2.5 POS (changed from the investigation, per AC-2)

The POS index is the **checkout terminal**: a pending-prescription queue, the patient list and a counter-sale form. It has no history or reporting. Its only purpose is to dispense. Under AC-2, which gated the deposit/transfer forms on `create …`, the terminal is gated on its action:

| Route | After |
|---|---|
| `pharmacy.pos.index`, `.medicines.search`, `.prescription` | `dispense pharmacy` +mp |
| `pharmacy.pos.checkout` | `dispense pharmacy` +mp |

**Why this changed:**
- The investigation proposed `view pos` for the terminal, and that would contradict AC-2.
- It would also break the existing `PharmacyPosNavigationTest` contract, where a `dispense pharmacy` user opens POS.

**What it means:**
- The escalation (**`view pos` checks out**) is closed either way.
- `view pos` now opens nothing. It stays in the registry, the same treatment as `view deposits` in AC-2.
- **Impact: zero.** Every holder of `view pos` (Super Admin, HA, Pharmacist) also holds `dispense pharmacy`.

### 2.6 Prescriptions (D1)

| Route | Before | After |
|---|---|---|
| `visits.prescription` (POST, visit-workflow panel) | `edit visits` | **`create prescriptions`** |
| `prescriptions.create`, `.store` | `edit visits` | `create prescriptions` |
| `prescriptions.index`, `.show` | `edit visits` | `view prescriptions` +mp |
| `prescriptions.dispense` | `edit visits` | **`dispense pharmacy`** +mp |
| `prescriptions.edit`, `.update`, `.destroy` | `edit visits` (dead) | removed (`->except`) |

`manage pharmacy` is **not** accepted on prescribing. Prescribing is clinical.

**Visit panel:** `VisitController:350` becomes `'can_prescribe' => $handler->canPrescribe($visit) && Tenant::currentHasModule('pharmacy') && auth()->user()->can('create prescriptions')`. Users without it don't see a form whose submit would return 403. `care_team_prescribe_message` is unchanged.

## 3. Real-role effect (re-verified at execution, before merge)

| Role | Users | After |
|---|---|---|
| Super Admin, Hospital Administrator | 9 | unchanged (they hold every pharmacy permission) |
| Pharmacist | 0 | unchanged |
| **Doctor** | **11** | **keeps prescribing** (backfill). Loses dispense and the URL-only prescription list/show (closed). |
| **Nurse** | **1** | the same as Doctor |
| others | 7 | unchanged |

## 4. Backfill: `create prescriptions` (PH-1)

| Piece | Change |
|---|---|
| `RolePermissionSeeder` | Add `'create prescriptions'` to `ROLE_PERMISSIONS['Doctor']` and `['Nurse']`. |
| **Command** | `rbac:backfill-create-prescriptions`, the same shape as `BackfillManageSubscriptionPermission`: all tenants or the current one; cache isolation; idempotent; `--dry-run` writes nothing. It grants the permission to the roles **named** `Doctor` and `Nurse` (names select targets only). |
| Expected result | 14 grants (2 roles × 7 tenants), covering 12 users. |
| **Safety report (for review)** | Production role data is unknown to me, and a tenant may have **custom** roles that prescribe today through `edit visits`. So the command (dry run and real run) also **lists every other role that holds `edit visits` but not `create prescriptions`**, without granting anything. That lets the deploy operator see who would lose prescribing before it ships. It's **report only**, staying within PH-1's confirmed scope. Locally it would list none: only Super Admin, HA, Doctor and Nurse hold `edit visits`, and the two admins already hold `create prescriptions`. |
| Deploy | It must run **in the same deploy action** as the route change. Otherwise all doctors lose prescribing at go-live. |

## 5. Redirects (AD-1)

There's one trait, `App\Http\Controllers\Concerns\RedirectsAfterWrite`, shared with Accounting and OT.

| Controller | Writes that redirect to the index today | Fallback when the index isn't viewable |
|---|---|---|
| Medicine, MedicineCategory, MedicineBrand, Unit, Supplier | store, update | that resource's `create` / `edit` form |
| same | destroy | `back()` |
| Purchase | store | `purchases.create` |
| Inventory | process-stock-in, process-stock-out | `inventory.stock-in` / `inventory.stock-out` |
| Prescription | store (standalone page) | `prescriptions.create` |

**Unchanged:**
- import (redirects to the index, but `manage pharmacy` also opens the index through R);
- purchase approve/receive/cancel, POS checkout and dispense (they use `back()` or the bill page);
- `visits.prescription` (returns to the visit).

## 6. UI gating

**Blade, one `@can` per control, matching its route:**

| View | Controls |
|---|---|
| `medicines/index:27`, `medicine-categories/index:32`, `medicine-brands/index:32`, `units/index:33`, `suppliers/index:21` | New → `create <X>` +mp (`@canany`) |
| `medicine-categories/index:143,146`, `medicine-brands/index:139,142`, `suppliers/index:61,64` | row Edit → `edit <X>`, row Delete → `delete <X>` (+mp) |
| `medicine-categories/show:13`, `medicine-brands/show:13`, `suppliers/show:26` | Edit → `edit <X>` +mp |
| `purchases/index:28` | New → `create purchases` +mp |
| `purchases/index:84,93`, `purchases/show:90,99` | Approve / Receive → `edit purchases` +mp |
| `purchases/index:102`, `purchases/show:107` | Cancel → `delete purchases` +mp |
| `inventory/index:40`, `low-stock:63`, `opening-stock:50` | Stock In → `create inventory` +mp +mi |
| `inventory/index:37`, `expiring:74` | Stock Out → `edit inventory` +mp +mi |
| `prescription-instructions/index:10` | New → `create prescriptions` +mp |
| `prescription-instructions/index:52,55` | Edit → `edit prescriptions`, Delete → `delete prescriptions` (+mp) |
| `prescriptions/index:8` | New → `create prescriptions` |
| `prescriptions/index:77`, `prescriptions/show:11` | Dispense → `dispense pharmacy` +mp |

The existing import gates (`@can('manage pharmacy')`, `@canany(['manage pharmacy','manage inventory'])`) are unchanged.

**JS-rendered row actions:**
- `resources/js/medicines-index.js:256-261` and `resources/js/units-index.js:326-331` build Edit and Delete for every row without any check.
- **Change:** the index Blade emits `data-can-edit` / `data-can-delete` on the root container (`#medicines-index`, and the units equivalent). The JS renders each action only when its flag is `"1"`.
- The routes stay the enforcement layer; this only stops buttons that would return 403.
- After the JS change, run `npm run build` and the Vite freshness gate (the established pattern).

**Sidebar** (`SidebarService.php:126`): POS changes from `view pos || dispense pharmacy || manage pharmacy` to **`dispense pharmacy || manage pharmacy`** (§2.5). Every other pharmacy item is unchanged.

## 7. README deploy note (with OT-1)

Add to `README.md` → Deployment a "Required Deploy Step" section, in the same form as the `manage subscription` one. It covers **both** Wave 1-completion backfills, run in the **same deploy action** as the code:
1. `php artisan rbac:backfill-manage-theatres --dry-run`, then the real run. Without it, admins get 403 on theatre create/edit.
2. `php artisan rbac:backfill-create-prescriptions --dry-run`, then the real run. Without it, **every Doctor and Nurse loses prescribing**. Check the dry run's "other roles holding `edit visits`" list before going live.

## 8. Tests

**New: `tests/Feature/Permissions/PharmacyRouteScopingTest.php`.** It may be split by area if it gets large.

**For every live route:**
- **Red before the fix:** a user holding only the permission that passes today but shouldn't gets **403**. Examples:
  - `view services`-only on every catalog, supplier and purchase write and on stock-in/out;
  - `view pharmacy`-only on `medicines.destroy`;
  - `view pos`-only on `pharmacy.pos.checkout`;
  - `edit visits`-only on `prescriptions.dispense` and `prescriptions.index`;
  - `create <X>`-only on `index`, `edit` and `destroy`.
- **Positive control:** exactly the new permission passes.
- **Coarse read:** `view pharmacy` and `view services` each get 200 on every read GET and 403 on every write.
- **Supersets:** `manage pharmacy` passes every route except prescribing (403 on `visits.prescription` / `prescriptions.store`). `manage inventory` passes every inventory route.

**Side effects** (blocked *and* nothing written):
- a `view services`-only `DELETE medicines/{id}` leaves the row in place;
- a `view services`-only purchase `approve` / `receive` / `cancel` leaves the status unchanged and creates no inventory transactions on receive;
- a `view services`-only stock-out leaves the batch quantity unchanged;
- a `view pos`-only checkout creates no bill and moves no stock;
- an `edit visits`-only dispense moves no stock and leaves the status `pending`.

**Prescribing (PH-1):**
- an `edit visits`-only user POSTing `visits.prescription` gets 403 and no prescription;
- a `create prescriptions` user creates one;
- the visit workflow page shows the prescription panel only with `create prescriptions`;
- a seeded Doctor or Nurse (after `RolePermissionSeeder`) can prescribe.

**Redirects (AD-1):** both cases (index viewable or not) for one representative write per controller in §5, plus each distinct fallback shape: create form, edit form, `back()` on destroy, and the stock forms.

**UI:**
- view-only users see no New/Edit/Delete/Approve/Receive/Cancel/Stock In/Stock Out/Dispense controls, and users with each permission see theirs;
- the medicines and units indexes emit the `data-can-*` flags correctly;
- POS sidebar: `dispense pharmacy` shows it, `view pos`-only doesn't.

**Dead routes:** `Route::has()` is false for the 5 removed names, and nothing in `resources/views` or `resources/js` references them (grep test).

**`tests/Feature/Commands/BackfillCreatePrescriptionsPermissionTest.php`:**
- grants exactly Doctor and Nurse;
- idempotent;
- other roles untouched;
- the dry run writes nothing;
- the safety report lists a custom role that holds `edit visits` without `create prescriptions` **and grants it nothing**;
- the seeder now gives Doctor and Nurse `create prescriptions`.

**Existing tests whose permission fixtures change** (behaviour assertions stay; each change shown failing on the old fixture first):
- `Pharmacy/PrescriptionDispenseRouteTest`: `edit visits` → `dispense pharmacy`.
- `Pharmacy/PrescriptionRoutesRegistrationTest`: `edit visits` → the per-route permissions, and it must assert the 3 dead names are gone.
- `Pharmacy/PrescriptionRequiresSellingPriceTest`, `VisitPrescriptionFulfillmentTypeTest`, `VisitPrescriptionPricingTest`: `edit visits` → `create prescriptions` (+ `edit visits` where the test also uses visit routes).
- `Module/ParentRouteEntitlementTest` (the 2 visit-prescription cases): add `create prescriptions`. The lab/imaging cases are unchanged (Laboratory wave).
- `Visits/IpdClinicalFeaturesTest` (`visits.prescription` cases at `:262-280`): add `create prescriptions` to the acting doctor users.
- `PharmacyPosNavigationTest`: unchanged. A `dispense pharmacy` user still gets POS (§2.5). It's in the known baseline; record whether this wave changes its status.
- `tests/Unit/PermissionRegistryTest.php`: no change (no pharmacy permission is added).

**Regression:**
- `tests/Feature/Pharmacy` (93) and `tests/Feature/Visits` (127) stay green apart from the known baseline;
- the full suite stays at the 34-failure baseline;
- the Vite freshness gate passes after the JS change.

**Real-render check:** a quick in-app check of the medicines and units index row actions as a view-only user and as an editor, because those buttons are rendered in JS and a server test can't fully see them.
