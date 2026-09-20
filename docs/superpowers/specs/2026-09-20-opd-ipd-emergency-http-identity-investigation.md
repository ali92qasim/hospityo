# OPD / IPD / Emergency HTTP identity — investigation

**Date:** 2026-09-20  
**Status:** Findings only. No design, no code, no implementation plan.  
**Method:** Superpowers brainstorming Phase 0 (context exploration). Code on `main` @ `5d62117` (post versioning release). Parallel codebase inventory of Visit CTI, `visits.*` / `test-orders.*` routes, `ModuleRegistry::moduleForRequest`, route-name dependency counts, bill/Doctor Share coupling, and IPD sidebar vs middleware asymmetry.

**Related**

- `docs/superpowers/specs/2026-08-10-visit-domain-separation-design.md` — CTI schema + dual-write plan (approved; this pass checks drift).
- `docs/superpowers/specs/2026-08-10-visit-workflow-architecture-design.md` — handler / blade organization.
- `docs/superpowers/specs/2026-09-01-ungated-module-ui-surfaces.md` — visit_type UI / Emergency button / CheckModule guessing.
- `docs/superpowers/specs/2026-09-07-slice-3-ungated-ui-visibility-design.md` — later UI gating fixes (patients-index module attrs).
- `docs/superpowers/specs/2026-09-01-sidebar-saas-module-gating-design.md` / parent-route entitlement work — IPD dual-module sidebar.
- `docs/superpowers/specs/2026-09-17-doctor-share-module-investigation.md` + rates rebuild — `resolveDoctorId` from Visit.

**Out of this pass:** HTTP identity redesign, typed route names, ModuleRegistry reshape, implementation plan. No confirmation questions.

---

# Verdict (what HTTP identity is today)

**One shared route family** (`visits.*` + two `test-orders.*`) serves three clinical products. Type identity is carried by:

1. **Spine column** `visits.visit_type` on the bound `Visit` model (reliable once `{visit}` is resolved).
2. **Query/body** `visit_type` on list/create/quick-register/DataTables (request-guessed; missing → **OPD default** in several places).
3. **`ModuleRegistry::moduleForRequest`** special-case for `visits.*` / `test-orders.*` that maps those request sources onto SaaS slugs `visits` | `ipd` | `emergency` for `CheckModule`.

CTI child tables (`opd_visits` / `ipd_visits` / `emergency_visits`) and workflow **handlers** exist and are partially real — but they do **not** replace HTTP identity. There are **no** alternate route names such as `opd.visits.*` / `emergency.visits.*`.

**Consequential for later design:** **187** `route('visits.…')` call sites across **57** files (production Blade + app + **132** test hits), plus **327** quoted `'visits.*'` string literals across **71** files (includes route registrations and Sidebar). Renaming routes is a large blast radius. Preserving names and changing only resolution/middleware is the lower-churn path unless aliases are introduced.

---

# 1. Visit CTI work — current state vs last audit

## 1.1 Child models (real tables + Eloquent)

| Model | Table PK | Fillable | Relation on `Visit` |
|---|---|---|---|
| `App\Models\OpdVisit` | `visit_id` (non-incrementing) | `visit_id`, `queue_priority` | `opdDetails()` |
| `App\Models\IpdVisit` | `visit_id` | `visit_id`, `expected_discharge_date` | `ipdDetails()` |
| `App\Models\EmergencyVisit` | `visit_id` | `visit_id` only | `emergencyDetails()` |

Migration: `database/migrations/tenant/2026_08_10_000001_create_visit_type_detail_tables.php` (and follow-ons as in the 2026-08-10 design). Spine `Visit` still owns `visit_type`, `doctor_id`, status, patient, timestamps, `closed_at`.

`Visit::typeDetails()` / `queuePriority()` / `readsTypeDetailFromChild()` / `typeDetailForRead()` exist on the spine model.

## 1.2 Handlers (real — HTTP workflow path)

| Class | Role |
|---|---|
| `App\Contracts\VisitTypeHandler` | Steps, transitions, workflowData flags, doctor resolution, canConsult/Prescribe/OrderLabs, tabs |
| `App\Workflows\Handlers\OpdVisitHandler` | OPD accordion / vitals / consultation UX flags |
| `App\Workflows\Handlers\IpdVisitHandler` | IPD tabs, care-team messaging, beds/wards data keys, `expected_discharge_date` from `ipdDetails` |
| `App\Workflows\Handlers\EmergencyVisitHandler` | Emergency triage-oriented steps/flags |
| `App\Workflows\VisitHandlerFactory::for(Visit)` | `match ($visit->visit_type)` → handler |
| `App\Services\VisitWorkflowService::for(Visit)` | Transitions via handler `allowedTransitions` |

**Used by:** `VisitController::workflow` (primary), and every transition through `VisitWorkflowService` (vitals, assign doctor, complete, admit, discharge, triage, test-result completion, check-patient).

Handlers read **`$visit->visit_type`** (spine), not ModuleRegistry and not child-table identity alone.

## 1.3 `VisitTypeDetailSyncService` — dual-write

**File:** `app/Services/VisitTypeDetailSyncService.php`  
**Config:** `config/visits.php`

| Flag | Default | Effect today |
|---|---|---|
| `dual_write_enabled` | **true** | On `Visit::created`, calls `createForVisit` → `firstOrCreate` matching child row |
| `dual_write_legacy_columns` | **false** | **Dead config** — never read in `app/` code |
| `log_child_mismatches` | true | Logger runs; only detects **missing child row**, not field parity |
| `read_from_child.{opd,ipd,emergency}` | **all false** | `typeDetailForRead()` unused in production; **OPD `queuePriority()` ignores this flag** and always reads `opdDetails` |
| `require_typed_visit_routes` | **true** | index/data require `visit_type` query (else redirect/empty) |

**What `createForVisit` actually writes**

- OPD → `OpdVisit` with default `queue_priority = medium`
- IPD → empty `IpdVisit` row
- Emergency → empty `EmergencyVisit` row
- Runs in a **separate** tenant transaction after Eloquent `created` (not the same txn as spine insert) — child failure can leave an orphan spine row

**What `syncLegacyToChild` does today:** if dual-write enabled, **only runs mismatch audit** — `$changedAttributes` unused; **no** child column updates. Stub after Phase 10 dropped spine `priority`.

**Additional real child write (not the sync service):** `VisitAdminViewService::update` `OpdVisit::updateOrCreate(… queue_priority …)` on admin edit for OPD — this is the live priority write path.

**Backfill:** `php artisan visits:backfill-type-details` (`BackfillVisitTypeDetails`) can create missing child rows (always `queue_priority = medium` for OPD). Operator tool, not request path.

**Name collision:** `IpdClinicalService::ensureIpdVisit()` only asserts spine `visit_type === 'ipd'`; it does **not** touch the `IpdVisit` CTI model.

## 1.4 Real vs theoretical

| Surface | Verdict |
|---|---|
| Child row create on Visit create | **Real** (default config) |
| Handlers + workflow views per type | **Real** |
| Admin present/update via `VisitAdminViewService` | **Real** (type branching) |
| OPD `queue_priority` on child | **Real** — create default + admin update; **reads always from child** (spine `priority` dropped in Phase 10) |
| IPD `expected_discharge_date` on child | Model/handler/UI **read**; **no app write path** |
| Emergency child columns beyond `visit_id` | **Theoretical / empty shell** (urgency on `triages`) |
| `read_from_child` / `typeDetailForRead` | **Ceremonial** for OPD priority (always child); flag still false; mainly test vestige |
| `dual_write_legacy_columns` / field mismatch logging | **Ceremonial / dead** |
| `VisitClassHistory` | **Theoretical** — model + relation; **zero creates** in `app/` or tests |
| Typed HTTP route names per type | **Does not exist** — still shared `visits.*` + query/body |

## 1.5 Drift vs `2026-08-10-visit-domain-separation-design.md`

| Design expectation | Current code |
|---|---|
| Thin CTI children + spine `visit_type` | Matches |
| Create child on store | Matches (`Visit::created` + dual_write) |
| Same DB transaction for spine + child | **Drift:** separate txn in `createForVisit` after `created` |
| Dual-write legacy columns → children | **Drift:** `syncLegacyToChild` stub; spine `priority` **dropped** (Phase 10 migration); admin writes child directly |
| `queuePriority()` gated on `read_from_child`, else spine | **Drift:** always reads child; flags false but irrelevant for OPD priority |
| `dual_write_legacy_columns` default true / used | **Drift:** default false; unused |
| `visit_class_histories` for ER→IPD | Table/model exist; **no runtime writers** |
| Heavy clinical data stays in related tables | Matches (admissions, triage, care team, consultations, etc.) |
| HTTP still shared VisitController | Matches — identity refactor not done in that design |

**Bottom line:** CTI **create-side + thin OPD queue priority + type handlers** are real. Strangler bridge APIs (`syncLegacyToChild`, `read_from_child`, mismatch field compare, class history) are largely ceremonial after Phase 10. Specs still describe the mid-migration bridge; code leapfrogged to “child is source of truth for OPD queue priority” without cleaning the bridge.

---

# 2. Route inventory — `visits.*` and `test-orders.*`

**Source:** `routes/web.php` only (no API duplicates). Stack: `web` → `tenant` (`EnsureTenantActive`, `SetTenantTimezone`, **`CheckModule`**) → `auth` → route `permission:…`.

## 2.1 Named routes (complete)

| Name | Method | URI | Controller@method | Type branching in method |
|---|---|---|---|---|
| `visits.data` | GET | `/visits/data` | `data` | **BRANCH** — request `visit_type`; IPD care-team filter in query |
| `visits.quick-register` | POST | `/visits/quick-register` | `quickRegister` | **IDENTICAL** body (validated `opd\|emergency` only — no IPD) |
| `visits.index` | GET | `/visits` | `index` | **BRANCH** — query `visit_type`; titles; default redirect → `opd` |
| `visits.create` | GET | `/visits/create` | `create` | **BRANCH** — view `admin.visits.create.{type}`; missing → redirect `opd` |
| `visits.store` | POST | `/visits` | `store` | **IDENTICAL** create; redirects use `$visit->visit_type` |
| `visits.show` | GET | `/visits/{visit}` | `show` | Controller thin; **BRANCH** in `VisitAdminViewService` |
| `visits.edit` | GET | `/visits/{visit}/edit` | `edit` | Same as show |
| `visits.update` | PUT/PATCH | `/visits/{visit}` | `update` | **BRANCH** in `VisitAdminViewService` |
| `visits.destroy` | DELETE | `/visits/{visit}` | *(missing method)* | **Broken registration** |
| `visits.workflow` | GET | `/visits/{visit}/workflow` | `workflow` | **BRANCH** + **handler delegate**; IPD draft bill / beds |
| `visits.print` | GET | `/visits/{visit}/print` | `print` | **BRANCH** — IPD print view vs OPD/ER PDF/HTML |
| `visits.vitals` | POST | `/visits/{visit}/vitals` | `updateVitals` | **IDENTICAL** body; transitions via handler |
| `visits.assign-doctor` | POST | `/visits/{visit}/assign-doctor` | `assignDoctor` | **IDENTICAL**; spine `doctor_id` |
| `visits.consultation` | POST | `/visits/{visit}/consultation` | `updateConsultation` | **BRANCH** — IPD strips GPE + syncs complaints |
| `visits.order-test` | POST | `/visits/{visit}/order-test` | *(missing `orderTest`)* | **Broken registration** |
| `visits.add-test-orders` | POST | `/visits/{visit}/add-test-orders` | `addTestOrders` | **IDENTICAL** (legacy test_orders) |
| `test-orders.remove` | DELETE | `/test-orders/{testOrder}` | `removeTestOrder` | **IDENTICAL** |
| `test-orders.result` | POST | `/test-orders/{testOrder}/result` | `updateTestResult` | **IDENTICAL** (+ workflow transition) |
| `visits.complete` | GET | `/visits/{visit}/complete` | `completeVisit` | **IDENTICAL** transition; redirect keeps type query |
| `visits.check-patient` | POST | `/visits/{visit}/check-patient` | `checkPatient` | **BRANCH** — IPD care-team access |
| `visits.admit` | POST | `/visits/{visit}/admit` | `admitPatient` | **IPD-oriented**; **no visit_type guard** in method |
| `visits.discharge` | POST | `/visits/{visit}/discharge` | `dischargePatient` | **BRANCH** — IPD discharge billing |
| `visits.admission-advance` | POST | `/visits/{visit}/admission-advance` | `storeAdmissionAdvance` | **BRANCH** — rejects non-IPD |
| `visits.triage` | POST | `/visits/{visit}/triage` | `triagePatient` | **Emergency-oriented**; **no visit_type guard** |
| `visits.care-team.*` (3) | POST/DELETE | care-team URIs | care-team methods | **IPD gate** via `IpdClinicalService::ensureIpdVisit` |
| `visits.complaints.*` (2) | POST | complaints URIs | complaint methods | **IPD gate** |
| `visits.gpe-records.store` | POST | gpe URI | `storeIpdGpeRecord` | **IPD gate** |
| `visits.doctor-visit-notes.*` (2) | POST/PUT | notes URIs | note methods | **IPD gate** |
| `visits.prescription` | POST | prescription URI | `createPrescription` | **BRANCH** via `resolveOrderDoctorId` |
| `visits.order-multiple-lab-tests` | POST | lab multi URI | `orderMultipleLabTests` | **BRANCH** via `resolveOrderDoctorId` |
| `visits.order-multiple-imaging-studies` | POST | imaging multi URI | `orderMultipleImagingStudies` | **BRANCH** via `resolveOrderDoctorId`; **registered twice** (lines 590–591) |

**Counts:** ~34 unique `visits.*` names + 2 `test-orders.*`. Missing methods: `destroy`, `orderTest`.

## 2.2 Pattern summary

- **Shared route, three behaviors:** list/create/workflow/print/consultation/check-patient/discharge/care-team cluster/prescription/lab/imaging.
- **Genuinely shared logic:** vitals append, assign doctor, legacy test-order CRUD, store (aside from validated type), many transitions.
- **Type-specific without HTTP guard:** `admit` / `triage` assume caller already on correct workflow UI.
- **Delegation:** type behavior often lives in handlers / `IpdClinicalService` / `VisitAdminViewService`, not only `if ($visit->visit_type)` in the controller — but the **route name is still shared**.

---

# 3. `ModuleRegistry::moduleForRequest`

## 3.1 Exact behavior

**File:** `app/Models/ModuleRegistry.php` ~732–796  
**Sole production caller:** `CheckModule::detectModule()` (`app/Http/Middleware/CheckModule.php:65`). Tenant group always runs CheckModule (`bootstrap/app.php`).

For route names starting with `visits.` or `test-orders.`:

```text
match (visitTypeFromRequest($request)) {
  'emergency' => 'emergency',
  'ipd'       => 'ipd',
  default     => 'visits',   // includes null and 'opd'
}
```

### `visitTypeFromRequest` sources (priority)

| # | Source | Notes |
|---|---|---|
| 1 | Route param `{visit}` if **object** with `visit_type` | Bound `Visit` model — **DB spine**, no whitelist |
| 2 | `$request->query('visit_type')` | Whitelist `opd\|ipd\|emergency` |
| 3 | `$request->input('visit_type')` | Body/JSON; same whitelist |
| — | else | `null` → **default module `visits` (OPD)** |

**Not read:** child CTI tables, handler type, `bill_type`.

### Module definition asymmetry (still true)

| Slug | `routes[]` | How visit HTTP is gated |
|---|---|---|
| `visits` | `visits.`, `test-orders.` | Overridden by special-case; OPD / unknown |
| `emergency` | **`[]` empty** | **Only** via special-case `visit_type=emergency` |
| `ipd` | `wards.`, `beds.` | Visit IPD via special-case; wards/beds via prefix match |

### Critical edge: `test-orders.*`

Routes bind `{testOrder}`, not `{visit}`. Unless input carries `visit_type`, `visitTypeFromRequest` → null → module **`visits`**, even when the test order belongs to an IPD visit. **Request-guessing failure mode.**

## 3.2 Cross-reference — prior audit behaviors

| Behavior | Still through `moduleForRequest` / request guess? | Reliable source today? |
|---|---|---|
| **Dashboard tiles** | **No** (gating) | `ModuleRegistry::allows(..., 'visits'|'ipd'|'emergency')` on tiles. Counts use `Visit::where('visit_type', …)`. |
| **patients-index.js buttons** | **UI: fixed (slice-3).** **POST CheckModule: yes** | Blade sets `data-allow-visits` / `data-allow-emergency` via `ModuleRegistry::allows`. JS posts body `visit_type`. Quick-register has no Visit yet → CheckModule uses **body**. **Drift vs 2026-09-01 ungated audit** (that audit said buttons always emitted; current code gates attrs). Still **no IPD** quick button (`QuickRegisterVisitRequest` is `opd\|emergency` only). |
| **bill_type validation** | **No** | `StoreBillRequest` / `UpdateBillRequest` / `entitledBillTypes()` match on **`bill_type` input** → plan modules. Not `moduleForRequest`. |
| **OPD/IPD lab–imaging order gating** | **Partial** | UI `can_order_labs`: **Visit handler** + laboratory/imaging modules. CheckModule on order routes: **bound Visit** → DB type. |
| **Prescription panels** | **Partial** | `can_prescribe` from **Visit handler** + pharmacy. CheckModule on `visits.prescription`: **bound Visit**. |
| **IPD admission bill links** | **No** | `_admission.blade.php` uses `ModuleRegistry::allows(..., 'billing')`. Draft bill via `IpdDraftBillService` requiring `$visit->visit_type === 'ipd'`. |

**Still heavily guessing:** untyped or query-typed **list/create/data** URLs, and **quick-register** body type. **Bound visit routes** are mostly reliable for CheckModule once `{visit}` is a model.

Unit coverage: `tests/Unit/ModuleRegistryTest.php` (query → emergency/ipd/visits). Feature: `CheckModuleTest` emergency vs OPD list — **no dedicated IPD list CheckModule feature test** (IPD mapping only unit-tested).

---

# 4. Dependency mapping — every `visits.*` reference

## 4.1 Counts (machine-counted, excluding vendor/node_modules/storage/public)

| Pattern | Occurrences | Files |
|---|---|---|
| `route('visits.` / `route("visits.` | **187** | **57** |
| Quoted `'visits.…'` / `"visits.…"` literals | **327** | **71** |
| `redirect()->route('visits.` | **7** | 1 (`VisitController`) |
| `route('test-orders.` | **0** | 0 (names exist; no `route()` helpers found) |

### `route('visits.` by tree

| Area | Occurrences |
|---|---|
| `tests/` | **132** |
| `resources/views/` (Blade) | **47** |
| `app/` | **8** |
| `resources/js/` | **0** (`route()` helper unused; hard paths instead) |

### JS hard paths (extra blast radius)

`resources/js/visits-index.js`: `/visits/data?visit_type=…`, `/visits/${id}/workflow`.  
`resources/js/patients-index.js`: posts to `data-quick-register-url` (Blade-supplied `visits.quick-register`), not a named route string in JS.

## 4.2 Representative sample (production)

| File | Role |
|---|---|
| `app/Http/Controllers/VisitController.php` | redirects + workflow completion |
| `app/Services/SidebarService.php` | **25** quoted `visits.*` names / params |
| `app/Support/VisitWorkflowBackLink.php` | back-link target |
| `app/Http/Controllers/BillController.php` | IPD draft → `visits.workflow` |
| `resources/views/admin/dashboard.blade.php` | workflow / check-patient links |
| `resources/views/admin/patients/index.blade.php` | quick-register URL |
| `resources/views/admin/patients/history.blade.php` | visit links |
| `resources/views/admin/visits/index.blade.php` | list chrome |
| `resources/views/admin/visits/create*.blade.php` | create forms |
| `resources/views/admin/visits/workflow/**/*.blade.php` | forms posting to visits.* |
| `resources/views/admin/visits/partials/ipd-*.blade.php` | care-team / GPE / complaints |
| `resources/views/admin/bills/edit.blade.php` | back to workflow |
| `resources/views/admin/doctor/assignments.blade.php` | visit links |
| `routes/web.php` | **27** name registrations |

## 4.3 Alternate type-specific route names

**None.** Grep found no `opd.visits.*`, `emergency.visits.*`, `ipd.visits.*`, or `visits.opd.*` route names.

## 4.4 Extra coupling beyond the 187 `route()` calls

| Kind | Count / note |
|---|---|
| `SidebarService` quoted `'visits.*'` tokens | **25** (active-route matching + menu targets) |
| `ModuleRegistry` | `str_starts_with($routeName, 'visits.')` / `'test-orders.'` — **prefix-coupled**; any rename/split must update this |
| `BillController` | Dynamic ternary chooses `'visits.workflow'` vs `'bills.show'` for IPD draft save |
| `routeIs('visits.…')` | Present in sidebar active-state logic |
| JS hardcoded paths | **3** in `visits-index.js` (`/visits/data`, `/visits/{id}/workflow`) — not Laravel names |
| Dynamic `'visits.'.$x` concat | **Not found** (low risk for concat renames) |

## 4.5 Design-consequence flag

**187 `route()` + 327 string literals + Sidebar tokens + ModuleRegistry prefix + hard JS paths** means any rename of `visits.*` is a **repo-wide mechanical migration** plus live bookmark/URL risk (`/visits?visit_type=…` and `/visits/{id}/workflow`). This is the highest-cost decision surface in this investigation.

---

# 5. Bill_type and Doctor Share coupling

## 5.1 How `bill_type` is set

| Path | Source |
|---|---|
| Manual bill create/update | Request `bill_type` (`StoreBillRequest` / `UpdateBillRequest`); module abort for ipd/emergency/pharmacy |
| Visit auto-link on create | Filters `Visit` by `visit_type = $request->bill_type` when type ∈ opd/ipd/emergency |
| IPD draft bill | Hardcoded `'ipd'` in `IpdDraftBillService` |
| Pharmacy POS | Hardcoded `'pharmacy'` |
| Investigation order billing | Derived from **`$visit->visit_type`** |

## 5.2 Doctor Share rate resolution

**Does not use request-guessed visit type or `moduleForRequest`.**

`DoctorShareService::resolveDoctorId(Bill $bill)`:

- Loads `visit.primaryDoctor`
- If `$visit->visit_type === 'ipd'` → `primaryDoctor?->doctor_id` (care-team child table)
- Else → `$visit->doctor_id`

Category/rate resolution uses persisted `bill_items.item_category` / `bill.bill_type` and `doctor_share_rates` — not HTTP visit guessing.

**Implication for HTTP identity refactor:** Doctor Share / bill calculate paths are **already Visit/bill anchored**. Refactor is **additive** to that correctness for share math; it does **not** need to re-litigate Doctor Share rate resolution unless bill_type entitlement UI is redesigned. Manual bill create still trusts **form `bill_type`**, which is a separate entitlement concern (already module-gated).

---

# 6. IPD — `wards.*` / `beds.*` vs `visits.*`

## 6.1 Registry

- Module `ipd` (“IPD Management”): routes `wards.`, `beds.` only.
- Module `visits`: routes `visits.`, `test-orders.` (overridden for typed visits by `moduleForRequest`).

## 6.2 Sidebar (`SidebarService`)

Still accurate vs earlier catalog/sidebar audits:

| Sidebar item | Requires |
|---|---|
| Admitted Patients (`visits.index` + `visit_type=ipd`) | **`ipd` AND `visits`** + `view visits` |
| Wards | **`ipd`** + `view wards` |
| Beds | **`ipd`** + `view beds` |

Proven by `tests/Feature/Navigation/SidebarVisitNavigationTest.php` — `gates ipd visit links behind both ipd and visits modules`.

## 6.3 Middleware

For `visits.*` with IPD type (query or bound Visit), `moduleForRequest` → **`ipd` only**. CheckModule does **not** also require `visits`.

Wards/beds routes → `ipd` via prefix — consistent with sidebar for those items.

## 6.4 Asymmetry (still true — consequential)

| Layer | IPD visit list/workflow |
|---|---|
| Sidebar “Admitted Patients” | needs **both** `ipd` + `visits` |
| HTTP CheckModule | needs **`ipd` only** |

A tenant with `ipd` but without `visits` can still open IPD visit URLs if they have Spatie visit permissions and a URL; they simply lack the sidebar entry. A tenant with `visits` but without `ipd` sees OPD but not IPD Management / Admitted Patients, and CheckModule blocks `?visit_type=ipd`.

Emergency analogue: empty `routes[]`, sidebar link needs `emergency`, CheckModule maps emergency visit_type → `emergency` — more aligned than IPD’s dual-module sidebar rule.

---

# Consequential flags (for the next design pass — not decisions)

1. **Route-name blast radius is large (187 `route()` + 327 literals + hard JS paths).** Changing names without aliases is high risk.
2. **HTTP identity is still query/body/`visit_type` + shared controller**, not CTI child identity and not separate route trees.
3. **`moduleForRequest` default-to-OPD** still collapses missing type to `visits` — including dangerous `test-orders.*` cases.
4. **IPD sidebar dual-module vs middleware single-module** asymmetry remains; any identity redesign that splits routes must specify both layers.
5. **CTI is half-landed:** create dual-write real; legacy sync / class history ceremonial. OPD queue priority already reads the child unconditionally (Phase 10 dropped spine `priority`). Do not assume CTI children are the HTTP identity source of truth — they are not.
6. **Broken routes** (`visits.destroy`, `visits.order-test`) and **duplicate imaging route** are existing landmines adjacent to this work.
7. **Doctor Share / bill calculate** already use Visit/bill truth — good news for scoping the HTTP refactor away from share math.

---

# Method notes

- Inventory: Visit CTI exploration, full route table, ModuleRegistry callers, bill/IPD coupling; dependency counts confirmed with a local Node walk (`route_visits` = 187). CTI section refined after deeper pass on sync stubs / Phase 10 priority drift.
- Prior specs read for drift: visit-domain separation (2026-08-10), ungated UI (2026-09-01) vs current patients-index gating, sidebar navigation tests.
- No live tenant DB row counts in this pass (HTTP/registry shape does not require them for these six questions).

**End of investigation. Awaiting review before any design or plan.**
