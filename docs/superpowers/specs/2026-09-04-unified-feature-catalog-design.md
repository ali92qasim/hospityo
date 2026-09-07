# Unified feature catalog (Track 4)

**Date:** 2026-09-04  
**Status:** investigation + design proposal. **No code in this pass.**  
**Method:** Superpowers brainstorming (spec first). Builds on the two investigation reports and the implemented `TenantModuleProvisioner`; does not repeat their inventories.

**Related**

- `docs/superpowers/specs/2026-09-01-backup-sidebar-drift-investigation.md` — Layer 1 (plan JSON) vs Layer 2 (Spatie) AND-gate; full module inventory; Backup is not a slug typo.
- `docs/superpowers/specs/2026-09-01-ungated-module-ui-surfaces.md` — show-then-403 vs show-and-succeed under a parent route; no Blade/JS `hasModule`.
- `docs/superpowers/specs/2026-09-01-tenant-module-provisioning-design.md` — additive grant, empty-RBAC auto, existing-tenant confirm. Implemented on `feat/tenant-module-provisioning`.
- Track 3 (`ccb3be9`) — nested pharmacy / laboratory / imaging / emergency / IPD **values** under bills, visits, taxes, doctor-share, and the investigation report `test_type` already abort via `Tenant::abortUnlessCurrentHasModule`.

---

## Scope (this pass)

**In:** every `ModuleRegistry` top-level slug except visit-type modules. Concrete nested case: **Reports**, each report a separately sellable child.

**In (schema only, unused now):** a node that is always entitled and plan-independent, so Settings could join later without a second entitlement model.

**Out**

- OPD (`visits`), IPD, Emergency — parked on Visit CTI. Do not change `ModuleRegistry::moduleForRequest` visit_type remapping.
- Migrating Settings off `SettingsSectionRegistry`. Do not touch Settings tabs, routes, or composers.
- Replacing `tenants:sync-permissions`.
- Per-tenant plan JSON overrides.
- Designing sellable children for Pharmacy, Accounting, OT, etc. (flag only).
- Implementation, migrations, or UI code.

---

# Part A — Findings (current system)

## A1. Reports module — every individual report

There is **no** `reports.index` landing page. Sidebar shows a Reports group only when `hasModule('reports')` **and** `$user->can('view reports')`, then lists **every** child with no further Spatie or plan check.

All of the following live in one route group:

```php
Route::prefix('reports')->name('reports.')->middleware('permission:view reports')
```

Controller: `App\Http\Controllers\ReportController`. CheckModule maps every `reports.*` name to slug `reports` via prefix `reports.`.

| Sidebar label | Route name(s) | Controller method | Spatie on the route |
|---|---|---|---|
| Daily Cash Register | `reports.daily-cash-register` | `dailyCashRegister` | `view reports` only |
| Patient Visits | `reports.patient-visits` | `patientVisits` | `view reports` only |
| Revenue Report | `reports.revenue` | `revenue` | `view reports` only |
| Outstanding Bills | `reports.outstanding-bills` | `outstandingBills` | `view reports` only |
| Investigation Report | `reports.investigations` **and** `reports.lab-tests` (same method, two names) | `labTests` | `view reports` only |
| Medicine Sales | `reports.medicine-sales` | `medicineSales` | `view reports` only |
| Inventory Status | `reports.inventory-status` | `inventoryStatus` | `view reports` only |
| Expiry Report | `reports.expiry-report` | `expiryReport` | `view reports` only |
| Doctor Performance | `reports.doctor-performance` | `doctorPerformance` | `view reports` only |
| Appointment Statistics | `reports.appointment-statistics` | `appointmentStatistics` | `view reports` only |
| IPD Report | `reports.ipd-report` | `ipdReport` | `view reports` only |
| Department Performance | `reports.department-performance` | `departmentPerformance` | `view reports` only |
| Patient Demographics | `reports.patient-demographics` | `patientDemographics` | `view reports` only |

**Thirteen sellable reports. One Spatie name.** `PermissionRegistry` group `reports` is only `view reports`. `RolePermissionSeeder` assigns that name to **Super Admin** (all permissions) and **Hospital Administrator**. Other default roles do not get it.

Track 3 already nested-gates Investigation Report `test_type=lab|radiology` on laboratory/imaging. That is **sibling-module** gating, not per-report entitlement. A hospital with `reports` + `view reports` and **no** laboratory still opens the investigation report page; lab rows/options are hidden/403. Per-report upsell is a new axis on top of that.

**Not in this module** (do not confuse):

- `imaging.reports.*` — Imaging module.
- `doctor-share.reports.*` — Doctor Share (`view share reports`).
- Accounting P&L / Balance Sheet / ledgers — Accounting module, already per-statement Spatie.

## A2. Other in-scope modules (from existing audits, not re-inventoried)

Layer 1 = `plans.modules` + `CheckModule` / `hasModule`. Layer 2 = Spatie. Sidebar = both (except noted flags).

| Module | Slug | What the audits already established | Future sellable sub-items (flag only) |
|---|---|---|---|
| Patients | `patients` | Prefix `patients.`; sidebar `view patients`. Patients-index Emergency/OPD buttons are ungated UI (visit-type; parked). | None this pass. |
| Doctors | `doctors` | `doctors.` + `doctor.`; `view doctors`. | None this pass. |
| Appointments | `appointments` | `appointments.` + `calendar.`; `view appointments`. Dashboard “Schedule Appointment” is show-then-403. | None this pass. |
| Departments | `departments` | `departments.`; `view departments`. Dashboard department stat is ungated. | None this pass. |
| Billing | `billing` | `bills.`, `services.`, `taxes.`. Track 3 now rejects nested `bill_type` / tax `applies_to` / line item lab-imaging when those modules are off. | Line-item types could be SKUs later; not this pass. |
| Accounting | `accounting` | Prefix `accounting.`; **already granular Spatie** per ledger/statement. Plan tick is still the whole module. | P&L, Balance Sheet, ledgers as separately sold reports — closest analogue to Reports, **do not design now**. |
| Pharmacy | `pharmacy` | Many prefixes; coarse `view pharmacy` / `manage pharmacy` aliases remain. Visit prescription + POS live under pharmacy routes; visit *panel* is still a visit surface (parked for CTI, Track 3 already `hasModule('pharmacy')` on handlers). | POS vs inventory vs catalog; not this pass. |
| Laboratory | `laboratory` | Lab prefixes; Track 3 gates nested orders/bill lines. | Individual test catalogs; not this pass. |
| Imaging | `imaging` | **Audit flag:** whole sidebar group hidden without `view radiology results`. | Studies vs results vs reports; not this pass. |
| Operation Theatre | `ot` | **Audit flag:** sidebar shows all OT children if `view surgeries` only. | PAC / checklist / consumables / sterilization as SKUs later. |
| Doctor Share | `doctor-share` | Granular Spatie; Track 3 gates `applies_to` / bill_type filters. | Share Reports vs rules vs settlements later. |
| HR | `hr` | Granular Spatie per subtree; `view hr` deprecated alias. | Payroll vs roster vs leave later. |
| Backup | `backup` | Proof case: plan tick ≠ sidebar. Provisioner now grants the five backup names additively. | None — keep one node. |
| Audit Logs | `audit` | **Audit flag:** sidebar requires **`rbac` AND `audit`** plus `view audit logs`. Routes are `audit-logs.`. | Coupling to User & Role is a catalog parent question (open). |

**Not in the operator’s in-scope list:** `rbac`, Subscription (hardcoded Blade, not `SidebarService`), Dashboard (intentionally ungated). Settings remains on `SettingsSectionRegistry`.

**Ungated-UI report — what Track 4 must eventually close** (except visit-type routing):

1. **Show then 403** — dashboard Appointments / Add Visit / stats; patients Emergency button (parked); IPD bill links (billing 403 if off); radiology/lab result links (route already CheckModule-correct).
2. **Show and succeed under a parent route** — Track 3 already closed the strong billing/visit nested-value leaks at FormRequest/handler layer. Catalog work **replaces/centralizes** those `currentHasModule` calls rather than inventing a third gate. Visit-type URL mapping stays parked.

## A3. `TenantModuleProvisioner::grant()` — extend vs restructure

Current signature: `grant(Tenant $tenant, array $modules, bool $dryRun = false, bool $keepCurrent = false): GrantResult`.

It already iterates a **flat list of slugs**. For each slug it loads `PermissionRegistry::forModule($slug)` and additively `givePermissionTo` Super Admin plus default roles that list those names. `ModuleRegistry::normalize()` only **adds missing parents when a child is selected**; it does **not** expand a parent into children.

Implications:

- Nested grants do **not** require a tree walker or a new service. A report child can be another slug in the same `$modules` array (`reports.daily-cash-register`), if `PermissionRegistry` has a key for that slug.
- **Cannot** encode implication policy inside `grant()` as “always grant children with parent”. That would silently sell every report whenever `reports` is granted — the opposite of the upsell rule. Implication belongs in the **entitlement gate** (and in the plan form), not in the provisioner.
- Empty-RBAC path runs `RolePermissionSeeder`, which today assigns **`view reports`** to Hospital Administrator. That single name currently unlocks all thirteen reports. After per-report permissions exist, the seeder/auto-grant must **not** treat “module `reports`” as “every report permission”, or new tenants get the full report pack for free.
- Idempotency and “never `syncPermissions` / never revoke on plan remove” stay valid at child-slug granularity.
- Existing-tenant confirm already keys off **added slugs**. Report-level adds are the same flash/POST if those slugs appear in plan JSON. New-tenant auto-grant at **module** level must still skip report children unless the plan list contains that child slug.

**Verdict:** extend `grant()` and `PermissionRegistry::forModule` to catalog node slugs. Do not replace the provisioner with a tree API. Do add a documented rule: **nodes with `child_access_requires_explicit_grant` on the parent are never implied by granting the parent.**

---

# Part B — Proposed design (needs confirmation)

Everything in this part is **proposed**. Open questions in Part C can change it.

## B1. Approaches considered

**A — Grow `ModuleRegistry` + keep `plans.modules` as a flat slug list** (recommended)

Settings already uses `parent` / `children` and dotted slugs (`settings.hospital-info`) in the same JSON array. Super-admin plan `_form` already renders nested checkboxes. `CheckModule` already 403s if a resolved child slug is missing **or** its parent is missing. Longest route-prefix match can resolve `reports.daily-cash-register` instead of the whole `reports.` prefix.

**B — Separate `plans.report_slugs` (or similar) column**

Splits entitlement into two shapes. Super-admin UI, provisioner, `hasModule`, and CheckModule all need a second reader. Rejected: more moving parts for the same data.

**C — Nested JSON object** (`{ "reports": ["daily-cash-register", ...] }`)

Breaks `Plan::hasModule` (`in_array` on a flat list), `ModuleRegistry::normalize`, and the existing checkbox `name="modules[]"`. Rejected for this pass.

**Recommendation:** A. Dotted child slugs beside existing module slugs.

## B2. Catalog schema

Keep one registry (today: `ModuleRegistry`). Each node:

| Field | Purpose |
|---|---|
| `slug` | Key. Children dotted: `reports.daily-cash-register`. |
| `name` | Super-admin + 403 copy. |
| `parent` | Null on top-level. |
| `children` | Child slugs (parent only). |
| `routes` | Route-name prefixes for CheckModule longest-match. |
| `permission` | Single Spatie name for this node’s nav/route (see B3). |
| `child_access_requires_explicit_grant` | **Parent only.** `true` = Reports-style. `false` = Settings-style (parent tick implies children). Unused on leaves. |
| `entitlement` | `plan` (default) or `always`. `always` = skip plan JSON (future Settings / Dashboard-like). **No module in this pass uses `always`.** |

`ModuleRegistry::normalize()` stays: selecting a child adds the parent. It must **not** add all children when the parent is selected. That is already true in PHP; the **plan form JS is not** (see B5).

**Reports parent**

- `child_access_requires_explicit_grant: true`
- `permission: view reports` (section exists; zero children ⇒ empty/hidden group)
- `routes: ['reports.']` only as fallback; each child lists its exact route name(s) so longest-match wins

**Reports children** (one node per sellable report; investigation aliases share one slug)

- `reports.daily-cash-register` → `reports.daily-cash-register`
- `reports.patient-visits`
- `reports.revenue`
- `reports.outstanding-bills`
- `reports.investigations` → routes `reports.investigations` **and** `reports.lab-tests`
- `reports.medicine-sales`
- `reports.inventory-status`
- `reports.expiry-report`
- `reports.doctor-performance`
- `reports.appointment-statistics`
- `reports.ipd-report`
- `reports.department-performance`
- `reports.patient-demographics`

Proposed child Spatie names: `view {slug}` e.g. `view reports.daily-cash-register` (same pattern as `access settings.hospital-info`).

**Settings (not migrated):** if it later joins, parent `child_access_requires_explicit_grant: false` and optionally `entitlement: always` on the parent. The schema allows that; this pass does not flip Settings.

## B3. Single gate function

Replace ad-hoc `hasModule` + `can` pairs in **new** call sites with one function on the same registry (no second catalog class in Slice 0). Proposed:

```php
ModuleRegistry::allows(?Tenant $tenant, ?User $user, string $slug): bool
```

Layering (must not be hardcoded “parent implies children”):

1. Unknown slug → false.
2. `entitlement === always` → skip plan JSON; still apply Spatie if a permission is set.
3. Plan JSON (`Plan::hasModule` exact slug):
   - Leaf under a parent with `child_access_requires_explicit_grant === true`: require **parent slug and child slug** in `plans.modules`.
   - Leaf under a parent with flag `false`: require parent slug; child slug optional (implied).
   - Top-level: require that slug.
4. No plan (trial/unlimited): keep today’s `Tenant::hasModule` true-if-no-plan, unless we explicitly close that (open question).
5. Spatie: user must `can(node.permission)` when a user is present. Sidebar keeps AND. HTTP: CheckModule stays Layer 1 only; route `permission:` stays Layer 2 — but the group-wide `permission:view reports` must become **per-report** (or `permission:view reports|view reports.daily-cash-register|...` is wrong; use the child name on that route).

`Tenant::currentHasModule` / `abortUnlessCurrentHasModule` should become thin wrappers around step 3 (plan only) **or** around the full `allows()` depending on call site:

- CheckModule / nested FormRequests that today only check plan → plan steps (1–4).
- Sidebar / Blade / JS “should we show this” → full `allows()` including Spatie.

Do not invent a Blade `@module` in the first implementation slice if PHP helpers + existing Track 3 flags suffice; adding `@module` / a JS data blob is an open question (C5).

### Where it must be called (ungated-UI resolution, minus visit-type routing)

| Surface | Gate slug(s) | Notes |
|---|---|---|
| Sidebar Reports children | each `reports.*` child | Hide child unless `allows(child)`. Hide group if no child passes. |
| `CheckModule` / `moduleForRequest` | longest-match child, then parent | Already parent-checks; needs child slugs in registry `routes`. |
| Each `reports.*` route | child permission | Split the blanket `permission:view reports` group. |
| Dashboard Schedule Appointment, appointment stats | `appointments` | Show-then-403 → hide. |
| Dashboard department stat | `departments` | Hide or N/A. |
| Dashboard near-expiry banner | `pharmacy` | Role list is not a plan check. |
| Bills / taxes / doctor-share nested values | already Track 3 `abortUnlessCurrentHasModule` | Point those helpers at the catalog wrapper; no second policy. |
| Investigation report `test_type` | `laboratory` / `imaging` **and** `reports.investigations` | Keep Track 3 sibling-module rules **plus** the report child slug. |
| Visit workflow lab/imaging/prescription **flags** | `laboratory` / `imaging` / `pharmacy` | Already Track 3 on handlers. Catalog wrapper only. **Do not** change visit_type → module map. |
| Patients Emergency / OPD buttons, visit type select, doctor dashboard OPD/IPD/Emergency tiles | parked | Visit CTI. |
| Settings tabs | untouched | Stay on `SettingsSectionRegistry`. |

## B4. Provisioner extension (same confirm rules)

Keep `grant($tenant, array $slugs, ...)`.

| Event | Module-level (`reports`) | Report-level (`reports.revenue`, …) |
|---|---|---|
| New tenant / empty RBAC | Auto-grant Spatie for **that node only** if the slug is in the plan list | Auto-grant **only if that child slug is in the plan list**. Never expand from parent. |
| Existing tenant, plan adds slugs | Flash confirm; no silent Spatie write | **Same.** Each added report slug is its own confirm payload. |
| Plan removes slugs | Do not revoke Spatie (unchanged) | Do not revoke. CheckModule/sidebar hide via Layer 1. |
| Second grant | Idempotent, additive, extra custom perms survive | Same. |

`PermissionRegistry::forModule('reports')` stays `['view reports']`. `forModule('reports.revenue')` returns `['view reports.revenue']`. Super Admin still receives whatever names `grant()` is asked to apply; it must not be asked to apply every report because the parent was granted.

Hospital Administrator default list: `view reports` plus **intersection with granted child names**, not a hardcoded pack of thirteen.

## B5. Super-admin plan JSON and UI

Keep `plans.modules` as `string[]`. Example after an upsell of two reports:

```json
["patients", "visits", "billing", "reports", "reports.daily-cash-register", "reports.revenue"]
```

A hospital can have `"reports"` and **no** `reports.*` children: module on, zero reports. That is required.

The plan form already nests child checkboxes under parents (`settings.*`). Reuse that for Reports **with one JS change:** today’s script sets `child.checked = parentBox.checked`, which **implies all children** when the parent is ticked — Settings-style, fatal for Reports upsell.

Proposed: JS branches on catalog flag (data attribute):

- `child_access_requires_explicit_grant === true`: parent tick **enables** children, does **not** check them; unchecking parent unchecks+disables children.
- `false`: keep current “parent checks all children” (Settings later).

Validation: `modules.*` remains `Rule::in(ModuleRegistry::all())`. Normalize still adds parent if a child is posted without it.

## B6. Migration plan (smallest blast radius)

Same discipline as Backup-first provisioning.

**Slice 0 — schema + gate, no product change**  
Add fields on existing nodes with defaults that preserve today’s behavior (`child_access_requires_explicit_grant: false` or unused; no new child slugs). Introduce `ModuleRegistry::allows()` as a wrapper around current `hasModule` + permission. Tests: existing ParentRouteEntitlement + provisioner tests still pass.

**Slice 1 — Backup as first catalog consumer** (optional but smallest HTTP/sidebar proof)  
One node, no children. Point sidebar + CheckModule copy through `allows('backup')`. Behavior should match today if provisioner already granted Spatie. Confirms the gate without upsell.

**Slice 2 — Reports per-report granularity (first nested product change)**  
Register thirteen children. Split route middleware. Sidebar per child. Plan form JS for explicit-grant parents. **Data migration (proposed, needs confirmation C1):** for every plan whose `modules` contains `reports` and **no** `reports.*` children, append all thirteen child slugs so live hospitals do not go dark. Operators then uncheck to create SKUs. Provisioner: confirm flash when those slugs are added on existing tenants.

**Slice 3 — remaining show-then-403 chrome** (dashboard appointments/departments/pharmacy banner, etc.) using the same gate. Still no visit-type routing.

**Later (not this program):** Accounting statements as children; OT children; Audit vs rbac parent; Settings `entitlement: always` / parent-implies-children; Visit CTI for OPD/IPD/Emergency.

---

# Part C — Open questions (confirm before any migration or code)

**C1. Grandfathering.** Plans that already have `"reports"` currently entitle all thirteen reports. Slice 2 without a data backfill would show **zero** reports until each child is ticked. Confirm: **backfill all current report children onto every plan that has `reports`** (recommended), vs accept a breaking empty Reports menu, vs a one-release compatibility mode where missing children still imply all.

**C2. Spatie vs plan as the upsell layer.** This design uses **plan JSON children** as what the hospital bought, and Spatie as what a role may use of what was bought. Confirm that hospital admins must not be able to Spatie-grant a report the plan does not list (gate Layer 1 first). Confirm Super Admin’s “all permissions” seeder is acceptable because Layer 1 still hides unpurchased reports.

**C3. Trial / no-plan tenants.** `Tenant::hasModule` is true when `plan` is null. That would entitle every catalog node, including every report child, on trial. Confirm keep vs require explicit slugs even on trial.

**C4. Audit Logs parent.** Sidebar requires `rbac` **and** `audit`. Catalog parent of `audit`: keep the dual check, or make `audit` a child of `rbac`? Out of Slice 2; needs a decision before Audit is converted.

**C5. Blade/JS helper.** Track 2’s leak class is UI without a helper. First slices can stay PHP (`allows()` + existing handler flags). Confirm whether Slice 3 includes `@feature` / a JSON blob for `patients-index.js`, or JS stays hardcoded until Visit CTI.

**C6. `view reports` after children exist.** Keep it as “can see the Reports group” with children gated separately (proposed), vs delete it and treat any child permission as group access.

**C7. Investigation aliases.** One catalog slug `reports.investigations` for both route names (proposed) vs two SKUs. Recommend one SKU.

**C8. Slice 1 (Backup) vs skip-to-Reports.** Backup is the smallest gate proof; Reports is the first nested upsell. Confirm order.

---

## Out of scope (repeat)

No implementation. No Settings migration. No Visit CTI. No revoke-on-uncheck. No second JSON column. No designing Pharmacy/Accounting/OT child SKUs beyond the flags in A2.

## Self-review

- Findings vs proposal are separate (Parts A / B).
- Implication is a per-parent flag, not a gate hardcoded to one rule.
- Provisioner stays flat slugs; parent does not expand explicit-grant children.
- Ungated-UI surfaces mapped except parked visit-type routing.
- Open questions are blocking for code, not TODOs inside the design.
