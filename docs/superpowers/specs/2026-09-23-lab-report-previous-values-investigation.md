# Lab report previous parameter values — investigation

**Date:** 2026-09-23  
**Status:** Findings only. No design, no code, no implementation plan.  
**Method:** Superpowers brainstorming Phase 0 (context exploration). Code on current workspace after `e0ce795` / `b95dcc3` (page numbers + first-page budget cut). Inventory of parameter catalog ↔ result items, `LabReportBuilder` row budgets, `LabReportPrintSettings` defaults, report Blade row loop, and any existing patient-history / trend query precedents.

**Requirement under investigation:** For each test parameter on a lab result report, show the patient’s previous N results for that same parameter (with dates), where N is a per-tenant Settings toggle (1–5, default 3). Must account for pagination (variable height per row) and work for a brand-new tenant with no configuration.

**Related**

- `docs/superpowers/specs/2026-09-21-lab-report-header-footer-public-qr-investigation.md` — report stack, Blade zones, pagination assumptions (budget then 14; now 8).
- Recent commits: `28c83c6` (`settings.lab-report-print`), `b95dcc3` (`FIRST_PAGE_ROW_BUDGET` 14→8), `e0ce795` (page numbers / reviewer footer).

**Out of this pass:** Design of matching rules, UI for the toggle, query/SQL shape choice, Blade layout for previous values, implementation plan.

---

# Verdict (what exists today)

**Same-parameter identity is a shared catalog FK:** `lab_result_items.lab_test_parameter_id` → `lab_test_parameters.id`. Trend lookup by that ID + `lab_orders.patient_id` is structurally straightforward. There is **no** name soft-match and **no** existing prior-N lab-parameter feature.

**Pagination is a fixed estimated-row accountant**, not measured layout. Cost today = `SECTION_HEADER_ROWS(2) + count(items) + SECTION_FOOTER_ROWS(1)`. First-page budget is **8** (tight). Printed result rows are already **1:1 with `LabResultItem`** inside investigation sections. Sections are **atomic** (never split).

**Settings defaults for new tenants already work** via `LabReportPrintSettings::get()` merging `DEFAULTS` when no settings row exists. Adding an **int** field to the same JSON key is storage-compatible but **not** a drop-in: get/put/request/UI are bool-only today.

**Pagination impact flag:** **Extendable with caution, not a free tweak.** If previous-line height is modeled as a **known fixed delta per prior value** (and history is fetched before `makeSection` costing), the existing cost formula and packing algorithm can be extended. **Likely real rework** if height is content-dependent (wrap), if panels must split mid-section, or if first-page packing with budget 8 + taller rows is treated as “just change a constant” without revisiting packing behavior and tests. Design phase should treat first-page budget + atomic sections as the main risk surface.

---

# 1. Parameter data model — identity across dates/visits

## 1.1 Layers

| Layer | Table / model | Role |
|---|---|---|
| Test catalog | `lab_tests` / `LabTest` | Panel (CBC, LFT, …) |
| Analyte catalog | `lab_test_parameters` / `LabTestParameter` | Parameter definition under one test |
| Measured value | `lab_result_items` / `LabResultItem` | One value linked to a catalog parameter |
| Result header | `lab_results` / `LabResult` | Status/meta for an order (`tested_at`, `verified_at`, `reported_at`, …) |
| Order | `lab_orders` / `LabOrder` | `patient_id`, `visit_id`, … |

Early free-form `lab_tests.parameters` JSON was removed; catalog is normalized.

## 1.2 Catalog identity (`lab_test_parameters`)

Migration `2026_01_28_050902_create_lab_test_parameters_table.php`: `id`, `lab_test_id` (cascade), `parameter_name`, `unit`, `data_type`, `reference_ranges`, `critical_values`, `select_options`, `is_calculated`, `calculation_formula`, `display_order`, `is_active`, timestamps.

- **Identity of an analyte definition:** PK `lab_test_parameters.id`.
- **No** LOINC / global analyte code / synonym table.
- **No** unique on `(lab_test_id, parameter_name)`.
- Scoped to one `lab_test_id` — same clinical name on two tests = two IDs.

## 1.3 Result row (`lab_result_items`)

After structure fix migration `2026_02_05_101404_fix_lab_result_items_table_structure.php`:

- FK `lab_result_id` (cascade)
- FK `lab_test_parameter_id` (cascade)
- `value`, `unit`, `flag`, …
- **Unique:** `(lab_result_id, lab_test_parameter_id)`

Entry paths (`LabResultController`) always write `lab_test_parameter_id` from `parameter_id` — not a free-text name on the item. Display name comes from `parameter.parameter_name`.

`LabResultItem::parameter()` → `belongsTo(LabTestParameter::class)`.  
`LabTestParameter::resultItems()` → `hasMany(LabResultItem::class)`.

## 1.4 What makes “the same parameter” comparable?

| Mechanism | Present? |
|---|---|
| Shared `lab_test_parameter_id` | **Yes — the intended join key** |
| Name match on `parameter_name` | **No** soft-match / trend code anywhere |
| Global analyte code | **No** |
| Same `lab_test_id` alone | Insufficient (many params per test) |

**Comparable across dates/visits for one patient iff `lab_test_parameter_id` is equal**, joined through:

```
patients.id
  → lab_orders.patient_id
  → lab_results.lab_order_id
  → lab_result_items.lab_result_id
  → filter lab_test_parameter_id
```

Date candidates on `lab_results`: `tested_at`, `verified_at`, `reported_at` (all cast datetime). Order also has timing via `lab_orders` / visit.

## 1.5 Stability caveats (shared-ID trends break here)

**Catalog update deletes all parameters then recreates them** (`InvestigationController::update` ~86–100): `$labTest->parameters()->delete()` then `create(...)`. Edit form sends name/unit/range — **no parameter `id`**. Cascade on `lab_test_parameter_id` can wipe historical `lab_result_items`. Import path does the same delete+insert pattern.

**Cross-panel same name:** “Hemoglobin” on CBC vs another panel = different IDs; shared-ID trend will **not** merge them without matching logic that does not exist today.

**Verdict for Q1:** Trend lookup by shared catalog ID is **straightforward while IDs stay stable**. Matching logic is **not** required for the happy path; design must still decide whether catalog recreate / cross-test same-name are in or out of scope (finding only — no recommendation here).

---

# 2. Query pattern & historical precedents

## 2.1 Existing prior-N / trend features

**None** for lab parameters. Grep of app code finds no “previous N results per parameter” consumer of `LabResultItem` history.

Closest precedents (weaker cousins):

| Precedent | Location | Pattern | Relevance |
|---|---|---|---|
| Patient history page | `PatientController::history`; `admin/patients/history.blade.php` | Batched eager load of visits / prescriptions / `labOrders` (order-level, not items) | Batching yes; parameter trends no |
| Visit vitals (IPD) | `Visit::allVitalSigns()`; `IpdClinicalService::clinicalTimelineEvents` | Eager-load once, sort in PHP | Same-visit series, not cross-visit lab params |
| Visit print labs | `VisitController::print` | Eager-load current visit `labOrders…resultItems.parameter` | Current episode only |
| Prescription `patient_history` | `PrescriptionPrintFieldResolver` | Free-text consultation field | Not a DB trend |
| Flag recalculation | `RecalculateLabResultFlags` | Loads all `LabResultItem` with relations | Bulk maintenance, not prior-N |

**Nothing already fetches prior `LabResultItem`s for a patient for display.**

## 2.2 Current report generation load quality

`LabReportBuilder::build` (`app/Services/LabReportBuilder.php` 39–47):

- `loadMissing` on order (`patient`, `doctor`, `visit`, `items.labTest`)
- **One** `LabResult::where('lab_order_id', …)->with(['resultItems.parameter.labTest', …])->get()`

Sections built in memory. **Current-order loading is already batched — not N+1.**

Controllers (`LabResultController::report` / `orderReport`, `PublicLabReportController`) all go through `LabReportBuilder::build`.

## 2.3 Natural batched lookup shape (observation only)

Given distinct `lab_test_parameter_id`s on the current report and `patient_id` from the order:

1. Collect distinct parameter IDs from current items.
2. **One** query of `lab_result_items` joined to `lab_results` + `lab_orders`, filtered by `patient_id`, `lab_test_parameter_id IN (…)`, excluding current result/order, ordered by a chosen datetime.
3. Group in PHP by parameter ID and keep first N each — **or** SQL window `ROW_NUMBER() OVER (PARTITION BY lab_test_parameter_id …)` then `rn <= N`.

Per-parameter `limit(N)` inside the item loop would be the N+1 anti-pattern; the current builder does not force that.

**Verdict for Q2:** Most efficient path is **one batched query (or windowed query) per report**, after current items are known. No in-app lab-trend precedent to copy; patient-history / vitals only teach “eager-load then group in PHP.”

---

# 3. `LabReportBuilder` pagination / row-cost model

## 3.1 Constants (current)

| Constant | Value | Role |
|---|---|---|
| `FIRST_PAGE_ROW_BUDGET` | **8** | Max summed section `row_cost` for page 1 panels |
| `PAGE_ROW_BUDGET` | **30** | Continuation pages; also `is_large` threshold |
| `SECTION_HEADER_ROWS` | **2** | Fixed per section |
| `SECTION_FOOTER_ROWS` | **1** | Fixed per section |
| `PATIENT_BAND_NOTE_LIMIT` | **120** | Char truncate only — **not** used in pagination |

Defined at `LabReportBuilder.php` 13–26. Commit `b95dcc3` cut first-page budget **14 → 8** for taller header.

## 3.2 Cost formula

`makeSection` (201–213):

```text
row_cost = 2 + count(resultItems) + 1
is_large = row_cost > 30
```

- Every parameter contributes **exactly +1**.
- No per-type cost table beyond header/footer constants.
- No measurement of text wrap, reference-range length, flags, or previous values (none exist).
- Letterhead, patient band, comments, reviewers, signatures, QR, page numbers are **not** costed; first-page scarcity is only the smaller budget.

## 3.3 Packing algorithm (`packIntoPages`, 219–294)

**Phase A — first page:** walk sections in order; skip any with `row_cost > 8`; pack while sum ≤ 8; else defer. If first page would be empty, **force** the first remaining section onto page 1 even if over budget.

**Phase B — continuation:** `is_large` sections get their own page; otherwise pack until sum would exceed 30, then new page.

**Critical behaviors:**

- Sections are **never split** across pages.
- View CSS: `.test-panel { break-inside: avoid; }` (`report.blade.php`).
- A 16-param CBC costs `2+16+1=19`: too big for first page (8), not `is_large` (30).

## 3.4 Variable-height handling today

**None.** Fixed constants only.

## 3.5 Pagination impact flag (for design caution)

| Situation | Impact |
|---|---|
| Fixed extra cost per previous-value line, summed in `makeSection` after history is known (or worst-case N) | **Straightforward extend** of cost formula + retune budgets/tests; packing algorithm can stay |
| Height varies by wrap / optional blocks / uneven content | **Likely rework** — no measured height, no per-item cost function |
| Need to split a tall panel across pages | **Real rework** — packing + Blade assume atomic sections |
| Keep atomic panels with taller rows under budget **8** | Even with clean cost math, first page packs **far fewer** panels; overflow increases; single oversized section can still exceed physical page despite `break-inside: avoid` |

**Verdict for Q3:** Model is fully documented and recent. Extending costs is feasible; treating previous-values as “variable height without changing the accountant” is not. Design caution should focus on **first-page budget 8 + atomic sections + when cost is known relative to history fetch**.

---

# 4. `settings.lab-report-print` storage & new-tenant defaults

## 4.1 Storage

| Piece | Detail |
|---|---|
| Class | `app/Support/LabReportPrintSettings.php` |
| DB key | `lab_report_print` (`SETTING_KEY`) |
| Value | JSON object |
| Current keys | Nine **booleans** in `DEFAULTS` (`show_logo`, `show_qr`, `show_hospital_address`, `show_hospital_phone`, `show_hospital_email`, `show_hospital_website`, `show_patient_band`, `show_reviewers`, `show_page_numbers`) — all default `true` |
| Consumer | `report.blade.php` via `LabReportPrintSettings::get()` |
| Roster | Separate table `LabReportRosterDoctor` — not part of this JSON |

## 4.2 `get()` / `put()`

- **`get()`:** `Setting::get('lab_report_print')` → decode → start from `DEFAULTS` → for each default key present in JSON, overwrite with `(bool)`. Missing keys keep default. Unknown JSON keys ignored.
- **`put()`:** iterates `DEFAULTS` keys only; present → `FILTER_VALIDATE_BOOLEAN`; **absent → `false` (not the default)**; writes full JSON.

Request `UpdateLabReportPrintSettingsRequest`: `sometimes|boolean` per key; `prepareForValidation()` force-merges every key via `$this->boolean($key)`. Edit UI is checkboxes only.

## 4.3 Brand-new tenant with zero settings rows

**Already guaranteed for boolean toggles:**

1. Test `defaults all lab report print toggles to true` asserts `get()` with no row returns full `DEFAULTS`.
2. `Setting::get()` returns null when missing → `get()` treats as empty decode → merge defaults.
3. **`TenantModuleProvisioner` does not write `lab_report_print`.**
4. Tenant seed default settings only copy landlord tenant settings blob — **no** hardcoded `lab_report_print` in seeders.
5. Report view uses `$printToggles['show_*'] ?? true` as belt-and-suspenders.

**Verdict for Q4 — new tenants:** Adding a key to `DEFAULTS` and reading via `get()` **already** means zero-row tenants get the default **without** provisioner/seeder work — **provided** casting is not forced through `(bool)` / checkbox pipeline incorrectly.

**Verdict for Q4 — adding `previous_values_count` (int 1–5, default 3):** Same JSON blob can hold it; merge-on-read fits default `3`. **Not trivial end-to-end:** phpdoc/`get`/`put`/FormRequest/UI are bool-only. An int needs typed cast, `min:1|max:5` validation, and a non-checkbox control. After a tenant’s first save, `put()` rewrites every `DEFAULTS` key — the new field must participate in form/request or it will be coerced wrongly (e.g. missing → `false` under current `put()`).

---

# 5. Report row granularity

## 5.1 Grouping vs rows

| Level | Unit |
|---|---|
| Section | One lab test / investigation (`parameter.lab_test_id`), ordered by order-item test name (`buildSections` 152–194) |
| Printed row | One `<tr>` per `LabResultItem` |

Blade (`report.blade.php` 400–427): panel header = investigation name; table columns Parameter / Result / Unit / Reference; `@foreach($section['items'] as $item)` → one row. **No** nested parameter groups, sub-panels, or multi-item rows. No previous-values column/block today.

## 5.2 Implication

Attaching previous values **per printed row maps cleanly to each `LabResultItem`**. Nesting in the data model is **not** the complication; section-level atomic packing and fixed +1 cost are.

**Verdict for Q5:** **1:1 row ↔ parameter** already. Previous-values attachment is structurally clean at the row level.

---

# Summary table (questions 1–5)

| # | Question | Finding |
|---|---|---|
| 1 | Parameter identity | Normalized `lab_test_parameters`; comparability = shared `lab_test_parameter_id`. Name match not used. Catalog delete+recreate and cross-test same-name are stability/scope caveats. |
| 2 | Query / precedents | No prior-N lab feature. Batched IN-list (or window) query is the natural pattern. Current report load is already batched for this order. |
| 3 | Pagination costs | Fixed: `2 + n + 1`; first page budget **8**; sections atomic; no variable height. |
| 4 | Settings / new tenants | `get()` merge defaults = new tenants OK without provisioner. Int field = same key, but bool pipeline must widen. |
| 5 | Row granularity | 1:1 `LabResultItem` ↔ printed `<tr>` inside investigation sections. |

---

# Pagination impact — design caution level

**Flag: extend with real caution — not “change one constant,” not full rewrite of packing by default.**

- **Straightforward extend** path exists: peri-item previous lines as fixed cost units, history available before `makeSection`, keep atomic sections, update `FIRST_PAGE_ROW_BUDGET` / tests.
- **Rework triggers:** content-dependent height, mid-panel page breaks, or ignoring that budget 8 + taller rows will reshape first-page packing behavior under `break-inside: avoid`.

Design phase should decide cost accounting for variable prior-count (0..N per row) and whether catalog-ID-only matching is acceptable product-wise; those are out of this investigation pass.
