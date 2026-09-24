# Lab report previous parameter values — design

**Date:** 2026-09-24  
**Status:** confirmed 2026-09-24 (P1–P6 as proposed; OQ1–OQ5 as below). Implementation plan: `docs/superpowers/plans/2026-09-24-lab-report-previous-values.md`.  
**Method:** Superpowers brainstorming (design after Phase 0). Builds on `2026-09-23-lab-report-previous-values-investigation.md`. Does not re-open that inventory.

**Related**

- `docs/superpowers/specs/2026-09-23-lab-report-previous-values-investigation.md` — shared `lab_test_parameter_id`; batched lookup; fixed row costs; `FIRST_PAGE_ROW_BUDGET = 8`; `lab_report_print` merge-on-read defaults; 1:1 item↔row.
- `docs/superpowers/specs/2026-09-21-lab-report-header-footer-public-qr-design.md` — L5 omit-if-empty; reviewer footer; page numbers; print toggles JSON.

---

# Locked principles (do not reopen)

| ID | Principle | Source |
|---|---|---|
| L1 | Same-parameter match = shared `lab_test_parameter_id` only (no name soft-match in v1). | Investigation §1 |
| L2 | One batched history load per report generation — no per-parameter queries. | Investigation §2 |
| L3 | Keep atomic-section packing (`packIntoPages` unchanged in structure). | Investigation §3 |
| L4 | New tenants get defaults via `LabReportPrintSettings::get()` merge — no provisioner/seeder. | Investigation §4 |
| L5 | **Omit-if-empty** — extend existing print convention: no empty previous-value chrome. Applies **per prior value and per parameter** (0 priors → nothing extra on that row). | Header/footer design L5 + this requirement |
| L6 | N is tenant-configurable **1–5**, default **3**, stored on existing `lab_report_print` JSON key. | This requirement |

---

# Findings (from investigation — not re-proven here)

1. Comparability = `lab_result_items.lab_test_parameter_id`; join path patient → orders → results → items.
2. No existing prior-N feature; current `LabReportBuilder::build` already batches this order’s items.
3. Cost today = `2 + count(items) + 1`; budgets 8 / 30; sections never split.
4. `LabReportPrintSettings` bool-only pipeline; `get()` merge defaults already covers zero-row tenants.
5. Blade: one `<tr>` per `LabResultItem` inside investigation panels.
6. Reviewers (last page) + page numbers (every page) live **outside** the results `<tbody>` loop.
7. **DB:** Pest/PHPUnit tenant tests force **SQLite** (`TenantTestCase` / `phpunit.xml`). Production provisioning and backups treat **MySQL/MariaDB** as the primary live tenant driver. Dual-driver discipline already exists elsewhere in the app.

---

# Approaches considered (key forks)

## Query shape

| Option | Mechanism | Pros | Cons |
|---|---|---|---|
| **A — `ROW_NUMBER()` window, filter `rn <= N`** | One SQL statement; DB caps per partition | Minimal over-fetch; correct under large histories | No prior window usage in app; raw SQL must stay SQLite+MySQL compatible; slightly harder to assert in tests |
| **B — One Eloquent/Query builder fetch, group + `take(N)` in PHP (recommended)** | `whereIn(parameter_id)` + patient scope + exclude current order; order newest-first; PHP caps | Matches app precedents (patient history / vitals); same code path on SQLite tests and MySQL prod; easy unit/feature tests | Can over-fetch if a patient has very long repeat history for those analytes |
| C — Per-parameter `limit(N)` loops | Simple mentally | **N+1** — rejected by L2 | |

**Recommendation: B.** Volume for a single patient’s distinct parameters on one report is expected to be modest; PHP capping matches conventions. Revisit A only if profiling shows heavy history tables.

Both SQLite (3.25+) and MySQL 8+ support `ROW_NUMBER()` — A is viable, not blocked by the test driver. Rejected for convention/simplicity, not capability.

## Display date

| Option | Field | Pros | Cons |
|---|---|---|---|
| **A — `tested_at` (recommended)** | Set on result create/update | Always present when items exist; closest available “when this value was produced”; best for reading intervals between successive measurements | Not the patient-band “Reporting” label |
| B — `reported_at` | Set on verify (with `verified_at`) | Matches patient-band “Reporting” | Null before finalize; verify can lag days after the measurement |
| C — `verified_at` | Set on verify | Authorization stamp | Same null/lag issues; less meaningful as a trend interval |

**Recommendation: A (`tested_at`)** for both **ordering** and **display**. Clinical trend readers care when successive values were obtained, not when someone clicked verify. Format like the rest of the report (`d M Y` or `d M Y, h:i A` — see Open Questions).

## Row layout

| Option | Mechanism | Pros | Cons |
|---|---|---|---|
| **A — Sibling muted `<tr>` per prior (recommended)** | After each current-value row, 0..N previous rows | Height units map 1:1 to cost; columns stay aligned; L5 = emit zero extra `<tr>` | Slightly more DOM rows |
| B — Stack priors inside the Result `<td>` | Single `<tr>`, nested lines | Fewer `<tr>`s | Cost vs painted height harder to reason; denser cell |
| C — Nested `<table>` per parameter | Mini-table in cell or under row | Structured | Heavy for print CSS; overkill |

**Recommendation: A.**

---

# Proposal

## P1. Query design (batched previous N)

### P1.1 Inputs

From `LabReportBuilder::build` after current-order results are loaded:

- `$patientId` = `$order->patient_id`
- `$parameterIds` = distinct non-null `lab_test_parameter_id` from current order’s `resultItems`
- `$excludeOrderId` = `$order->id` (exclude **entire current order**, not only one `LabResult` — order reports can span multiple results)
- `$n` = `(int) LabReportPrintSettings::get()['previous_values_count']` clamped 1–5

If `$parameterIds` is empty → skip history query.

### P1.2 Single query (conceptual)

```text
lab_result_items
  JOIN lab_results ON lab_results.id = lab_result_items.lab_result_id
  JOIN lab_orders  ON lab_orders.id  = lab_results.lab_order_id
WHERE lab_orders.patient_id = :patientId
  AND lab_result_items.lab_test_parameter_id IN (:parameterIds)
  AND lab_orders.id != :excludeOrderId
  -- status filter: see Open Question OQ1
ORDER BY lab_result_items.lab_test_parameter_id ASC,
         lab_results.tested_at DESC,
         lab_results.id DESC
```

Select at least: `lab_result_items.id`, `lab_test_parameter_id`, `value`, `unit`, `flag`, `lab_results.tested_at`, `lab_results.id` (as prior result id).

### P1.3 PHP cap

Group collection by `lab_test_parameter_id`; for each group `take($n)`. Attach to each current `LabResultItem` as e.g. `previous_values` (list of `{ value, unit?, tested_at, flag? }` — exact DTO left to plan).

### P1.4 Where it plugs in

Inside `LabReportBuilder::build`, **after** current `$labResults` load and **before** `buildSections` / `makeSection`:

1. Resolve `$n` from settings.
2. Run P1.2–P1.3 once.
3. Pass history map into section building so each item carries its priors for costing + Blade.

No change to controller entry points beyond what the builder already centralizes (authenticated + public report both call `build`).

---

## P2. Date field

**Use `lab_results.tested_at`** for sort (newest-first) and for the printed date beside each previous value.

**Reasoning:** It is written when results are entered (`LabResultController` create/update paths set `tested_at = now()`), so it is available for reported and final results alike. `reported_at` / `verified_at` are only set on finalize and can lag the actual measurement interval a clinician is trying to read. Patient-band “Reporting” remains the **current** report’s finalize stamp; previous-value dates answer a different question (“when were these earlier values obtained?”).

Fallback if `tested_at` is null (legacy/edge): `reported_at` then `lab_results.id` order only — defensive, not the primary path.

---

## P3. Pagination extension (cost formula only)

### P3.1 Formula

Keep header/footer constants. Extend item contribution:

```text
PREVIOUS_VALUE_ROW_COST = 1   # new constant; one estimated row per shown prior

item_rows(item) =
    1 + (PREVIOUS_VALUE_ROW_COST * count(item.previous_values))
    # count is 0..N actual shown priors (L5); not always N

section.row_cost =
    SECTION_HEADER_ROWS
  + sum(item_rows(item) for item in section.items)
  + SECTION_FOOTER_ROWS

is_large = section.row_cost > PAGE_ROW_BUDGET   # unchanged meaning
```

Equivalent: `2 + count(items) + total_previous_lines_in_section + 1`.

### P3.2 When computed

Compute **once** in `makeSection` (or immediately before it) using **already-attached** previous values — never re-query inside packing. `packIntoPages` stays structurally identical; it only sees larger `row_cost` integers.

### P3.3 Budget retuning scope

| Constant | Proposal |
|---|---|
| `FIRST_PAGE_ROW_BUDGET` (8) | **Keep 8** for v1. Taller rows correctly defer more panels to page 2 via existing logic. Raising it would fight the taller-header fix (`b95dcc3`). Lowering only if print QA shows first-page clipping. |
| `PAGE_ROW_BUDGET` (30) | **Keep 30** unless QA shows continuation overflow. |
| Tests | **Re-tune expectations**, not necessarily constants: packing tests that assume cost-4 single-param sections must account for +priors; add cases for 0 priors (cost unchanged) vs full N; assert `is_large` still uses 30. |

Cost uses **actual** prior count (0..N), not worst-case N — aligned with L5 and avoids over-paging empty-history patients.

---

## P4. Settings field

### P4.1 Schema (same JSON key `lab_report_print`)

Add:

```text
previous_values_count: integer, min 1, max 5, default 3
```

Alongside existing `show_*` booleans. No new settings row type; no migration of `settings` table.

### P4.2 Typed `LabReportPrintSettings`

Widen beyond bool-only:

- `DEFAULTS` includes `'previous_values_count' => 3`.
- Split handling:
  - **Bool keys:** existing `(bool)` / `FILTER_VALIDATE_BOOLEAN` / missing-on-`put` → `false`.
  - **Int key(s):** `previous_values_count` — on `get()`, if present cast with `(int)` and clamp to 1–5; if missing, use default `3`. On `put()`, require validated int; clamp 1–5; **do not** run through `FILTER_VALIDATE_BOOLEAN`.
- Update phpdoc away from `@var array<string, bool>` to a mixed map (or document bool vs int keys explicitly).

`UpdateLabReportPrintSettingsRequest`:

- Keep bool rules for `show_*`.
- Add `previous_values_count` => `required|integer|min:1|max:5` (or `sometimes` with prepare defaulting to 3).
- Stop force-merging this key through `$this->boolean()`.

### P4.3 UI

On `settings/lab-report-print/edit`:

- Keep existing checkbox list for `show_*`.
- Add a separate control group: **“Previous results per parameter”** with **radio buttons** for `1`, `2`, `3`, `4`, `5` (default selected from `$toggles['previous_values_count']`).
- Short help text: e.g. how many prior values (with dates) appear under each parameter on the printed report.
- Same form POST as the checkboxes (one Save). Roster form unchanged.

New tenants / never-saved tenants: `get()` → `3` with no DB row (L4).

---

## P5. Report layout

### P5.1 Rendering

Inside each investigation results `<tbody>`, for each current `$item`:

1. Emit the existing current-value `<tr>` (Parameter / Result / Unit / Reference) unchanged in columns.
2. For each entry in `$item->previous_values` (already ≤ N, newest first): emit a **sibling** `<tr class="previous-result">` (name indicative):
   - Parameter cell: empty or a light affordance (e.g. muted en-dash / “Prev”) — prefer **empty** to avoid looking like a new analyte (Open Question OQ3).
   - Result cell: prior `value` (+ flag styling if desired, muted).
   - Unit cell: prior unit or `—`.
   - Reference cell: **date only** (`tested_at` formatted), not the reference range (range is for the current row).

### P5.2 L5 behavior

- 0 priors → **no** sibling rows, **no** placeholder text, **no** “No previous results”.
- Do not render empty previous blocks for the section as a whole either.

### P5.3 CSS

Muted typography (smaller / gray); keep `break-inside: avoid` on `.test-panel` as today. No change to letterhead, patient band, reviewer blocks, or page-number positioning rules beyond natural page growth from higher section costs.

---

## P6. Interaction with reviewer footer / page numbers

**Confirmed: no expected conflict.**

| Surface | Interaction |
|---|---|
| Result body `<tbody>` | Only place previous-value rows are added |
| `LabReportBuilder` section `row_cost` | Increases → more/earlier page breaks; packing algorithm unchanged |
| Reviewer blocks | Still last-page-only, after comments; gated by `show_reviewers` + non-empty roster result; independent of parameter history |
| Page numbers | Still every page via `$pageIndex` / `$pageCount`; more pages if sections grow — numbering remains correct |
| Print toggles | New int key coexists with `show_reviewers` / `show_page_numbers`; no rename or behavior change to those flags |

Explicit non-goals for this design: moving reviewers, changing page-number CSS, or retuning first-page budget for footer chrome (already handled by budget 8).

---

# Out of scope (v1)

- Name-based or cross-test analyte matching.
- Healing trends after catalog delete+recreate (known ID instability from investigation).
- Mid-section page splits / measured HTML height.
- DomPDF / prescription-print migration.
- Showing previous values on non-print lab result screens (entry UI, patient history page).

---

# Confirmed open questions (2026-09-24)

| ID | Decision |
|---|---|
| **OQ1** | Prior status filter = **`reported` and `final` only** (exclude `preliminary` / `cancelled`) |
| **OQ2** | Date format = **`d M Y`** (date-only) |
| **OQ3** | Sibling rows: **empty** Parameter cell |
| **OQ4** | Prior **flag styling yes, muted** |
| **OQ5** | Tie-break = **`lab_results.id DESC`** after `tested_at DESC` |

---

# Spec self-review

- No TBD placeholders left in proposal body; open items are explicit OQ1–OQ5.
- L5 applied per prior line and per parameter; cost uses actual counts — consistent.
- Query exclusion is **current order**, not single result — consistent with multi-result orders.
- Bool/int settings split called out so `put()` cannot coerce `previous_values_count` to false.
- Pagination: extend `makeSection` only; no `packIntoPages` restructure — matches L3.
- Footer/page-number interaction stated explicitly (P6).
- Scope: single feature; implementation plan deferred until confirmation.
