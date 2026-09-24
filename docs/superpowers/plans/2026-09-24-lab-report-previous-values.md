# Lab Report Previous Parameter Values Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> Do **not** start execution until a human confirms this plan. Do **not** merge any branch into `main` until the user explicitly asks — and only after every task is implemented, tested, and free of known bugs.
>
> **New feature branch:** create `feat/lab-report-previous-values` from current HEAD **before Task 1**. Do not work on `main`.
>
> **High-risk lab surfaces.** Every task that touches `LabReportBuilder` / report Blade must prove **existing report generation is unaffected when the patient has zero prior history** — the common case — for **both** authenticated print and public/QR unlock paths. Paired regression is required, not only new cases.
>
> **Phase-boundary reports (override SDD “don’t pause” at these points only):**
> 1. After Task 1 (batched history query + attachment).
> 2. After Task 2 (pagination cost formula extension).
> 3. After Task 3 (settings schema + typed get/put + radio UI).
> 4. After Task 4 (report layout sibling rows).
> 5. After Task 5 (full regression vs reviewer footer / page numbers). **No merge.**

**Goal:** On each lab report parameter row, show the patient’s previous N results for that same `lab_test_parameter_id` (with dates), where N is a tenant setting (1–5, default 3), with pagination costs extended and L5 omit-if-empty when there is no history.

**Architecture:** Keep HTML/`window.print` and atomic-section packing. One batched history query per `LabReportBuilder::build`, group + PHP `take(N)`, attach priors to items before `makeSection`. Extend `row_cost` by actual prior-line count. Widen `lab_report_print` JSON with int `previous_values_count`. Blade emits muted sibling `<tr>`s under each current row.

**Tech Stack:** Laravel 12, Pest, Spatie multitenancy, existing `LabReportBuilder`, `LabReportPrintSettings`, `PublicLabReportController`, settings.lab-report-print screen.

**Spec:** `docs/superpowers/specs/2026-09-24-lab-report-previous-values-design.md` (confirmed; OQ1–OQ5 locked). Investigation (do not re-open): `docs/superpowers/specs/2026-09-23-lab-report-previous-values-investigation.md`.

## Global Constraints

- **L1 — Match by `lab_test_parameter_id` only** (no name soft-match).
- **L2 — One batched history load per report** — no per-parameter queries.
- **L3 — Atomic sections** — do not restructure `packIntoPages`; only change `makeSection` cost inputs.
- **L4 — New-tenant defaults via `get()` merge** — no `TenantModuleProvisioner` / seeder writes for `lab_report_print`.
- **L5 — Omit-if-empty** — 0 priors → no extra markup and no cost adder for that item.
- **L6 — N = 1–5, default 3** on key `lab_report_print` → `previous_values_count`.
- **Confirmed OQs:** OQ1 `reported`+`final` only; OQ2 `d M Y`; OQ3 empty Parameter cell on sibling rows; OQ4 muted flag styling; OQ5 `tested_at DESC`, then `lab_results.id DESC`.
- **Date field:** `lab_results.tested_at` for order + display (fallback `reported_at` then id only if null).
- **Exclude current order** entire (`lab_orders.id != current`), not a single `LabResult`.
- **Budgets stay 8 / 30** unless print QA later forces a change; retune **tests**, not constants, in this plan.
- **Zero-history regression rule (every LabReportBuilder-touching task):** existing `LabReportBuilderTest` packing numbers for reports with no prior orders must still pass; run authenticated + public report tests; phase report must state zero-history proof explicitly.
- Tests: `php artisan test --compact`. `Feature/Lab` and `Feature/Settings` already in `tests/Pest.php` tenant `in()` list. Force-add `docs/superpowers/` (`git add -f`). Do not merge to `main`.

## Confirmed decisions (baked in)

| ID | Decision |
|---|---|
| Query | Batched fetch + group/`take(N)` in PHP (not window) |
| Date | `tested_at`; format `d M Y` |
| Status | `reported`, `final` only |
| Layout | Sibling muted `<tr>` per prior; empty Parameter cell |
| Flag | Muted abnormal styling on prior rows |
| Tie-break | `id DESC` after `tested_at DESC` |
| Cost | `item_rows = 1 + count(priors)`; section = `2 + Σ + 1` |
| Budgets | Keep `FIRST_PAGE_ROW_BUDGET = 8`, `PAGE_ROW_BUDGET = 30` |
| Settings | `previous_values_count` int 1–5 default 3; radios |

## File map

**Create:**

- `tests/Feature/Lab/LabReportPreviousValuesTest.php` (Tasks 1–2 history + cost; Task 4/5 layout assertions may live here or in chrome test)
- Optionally extend `tests/Feature/Lab/LabReportPrintChromeTest.php` for Blade sibling-row / footer coexistence (Task 4–5)

**Modify:**

- `app/Services/LabReportBuilder.php` — history load + attach; `PREVIOUS_VALUE_ROW_COST`; `makeSection` cost; expose priors on items for Blade
- `app/Support/LabReportPrintSettings.php` — DEFAULTS + typed get/put
- `app/Http/Requests/UpdateLabReportPrintSettingsRequest.php` — int rule; stop bool-coercing this key
- `resources/views/settings/lab-report-print/edit.blade.php` — radio group
- `resources/views/admin/lab/results/report.blade.php` — sibling previous rows + muted CSS
- `tests/Feature/Lab/LabReportBuilderTest.php` — keep green for zero-history packing; add cost cases in Task 2 (or keep packing cost cases in PreviousValuesTest and only ensure existing tests still pass)
- `tests/Feature/Settings/LabReportPrintSettingsTest.php` — default includes int; save radios; clamp
- `tests/Feature/Lab/PublicLabReportTest.php` / `LabReportPrintChromeTest.php` — regression + layout/footer coexistence as needed

**Do not modify:** `packIntoPages` control flow (structure); public verify challenge; QR encoding; reviewer pivot/roster tables; Prescription Print DomPDF.

**Shared types (lock now):**

```php
// Prior value DTO attached per current LabResultItem (array shape):
[
    'value' => string,           // prior item value
    'unit' => ?string,
    'flag' => ?string,           // N|H|L|HH|LL|A|null
    'tested_at' => \Carbon\CarbonInterface|string|null,
]

// On each current LabResultItem used in sections:
// $item->previous_values = list<array{value: string, unit: ?string, flag: ?string, tested_at: mixed}>
// empty list when none (never null)

// Settings JSON lab_report_print adds:
'previous_values_count' => 3,  // int 1..5

LabReportBuilder::PREVIOUS_VALUE_ROW_COST = 1;

// Cost:
// item_rows = 1 + PREVIOUS_VALUE_ROW_COST * count(previous_values)
// row_cost  = SECTION_HEADER_ROWS + sum(item_rows) + SECTION_FOOTER_ROWS

// History query filters (verbatim):
// patient_id = order.patient_id
// lab_test_parameter_id IN (distinct ids on current order items)
// lab_orders.id != current order id
// lab_results.status IN ('reported', 'final')
// ORDER BY tested_at DESC, lab_results.id DESC
// then PHP groupBy parameter_id -> take(N)
```

**Lab regression helper (use in every phase report):**

```bash
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
php artisan test --compact tests/Feature/Lab/LabReportPrintChromeTest.php
```

Zero-history proof means: Builder packing tests still expect costs **without** prior adders (e.g. two 1-param sections → `row_cost` 8 on one page), and public/chrome reports still render with no `previous-result` rows when the patient has no older orders.

---

### Task 1: Feature branch + batched history query + attachment

**Files:**

- Create: `tests/Feature/Lab/LabReportPreviousValuesTest.php`
- Modify: `app/Services/LabReportBuilder.php`
- Modify: `app/Support/LabReportPrintSettings.php` — **minimal**: add `'previous_values_count' => 3` to `DEFAULTS` and int-aware `get()` so N resolves (full `put()`/UI in Task 3)

**Interfaces:**

- Consumes: current-order `LabResult`/`LabResultItem` graph; `LabReportPrintSettings::get()['previous_values_count']`.
- Produces: each section item has `previous_values` list (≤ N, newest first); history loaded in **one** query for the report; current order excluded; preliminary/cancelled excluded.

- [ ] **Step 1: Create branch**

```bash
git checkout -b feat/lab-report-previous-values
```

- [ ] **Step 2: Write failing tests (history correctness + no N+1 + zero-history baseline)**

In `tests/Feature/Lab/LabReportPreviousValuesTest.php`, reuse the same fixture style as `LabReportBuilderTest` (Patient, Doctor, Visit, LabOrder helpers). Include:

```php
<?php

use App\Models\LabOrder;
use App\Models\LabResult;
use App\Models\LabResultItem;
use App\Services\LabReportBuilder;
use App\Support\LabReportPrintSettings;
use Illuminate\Support\Facades\DB;

// helpers: createLabTestWithParams / createResultForLabTest — copy from LabReportBuilderTest
// or extract shared helpers only if needed; prefer local copies to avoid drive-by refactors.

it('defaults previous_values_count to 3 with no settings row', function () {
    expect(LabReportPrintSettings::get()['previous_values_count'])->toBe(3);
});

it('attaches empty previous_values when the patient has no prior history', function () {
    $test = createLabTestWithParams('Uric Acid', 1);
    createResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    $item = $report['pages'][0]['sections'][0]['items'][0];

    expect($item->previous_values)->toBeArray()->toBeEmpty()
        ->and($report['pages'][0]['row_cost'])->toBe(4); // unchanged 2+1+1
});

it('loads previous values for the same parameter newest-first capped at N', function () {
    $test = createLabTestWithParams('Glucose', 1);
    $paramId = $test->parameters->first()->id;

    // Older order (should appear)
    $older = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor); // helper
    $olderResult = createResultForLabTest($older, $test, $this->user);
    $olderResult->update(['status' => 'final', 'tested_at' => now()->subDays(10)]);
    LabResultItem::where('lab_result_id', $olderResult->id)->update(['value' => '90']);

    // Newer prior
    $mid = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $midResult = createResultForLabTest($mid, $test, $this->user);
    $midResult->update(['status' => 'reported', 'tested_at' => now()->subDays(3)]);
    LabResultItem::where('lab_result_id', $midResult->id)->update(['value' => '95']);

    // Preliminary prior — must NOT appear (OQ1)
    $prelimOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $prelim = createResultForLabTest($prelimOrder, $test, $this->user);
    $prelim->update(['status' => 'preliminary', 'tested_at' => now()->subDay()]);

    // Current order
    createResultForLabTest($this->order, $test, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    $priors = $report['pages'][0]['sections'][0]['items'][0]->previous_values;

    expect($priors)->toHaveCount(2)
        ->and($priors[0]['value'])->toBe('95')
        ->and($priors[1]['value'])->toBe('90');
});

it('excludes results from the current order even when older LabResult rows exist on it', function () {
    $test = createLabTestWithParams('Hb', 1);
    // First result on current order (same order id) — must not appear as "previous"
    createResultForLabTest($this->order, $test, $this->user);
    $first = LabResult::where('lab_order_id', $this->order->id)->first();
    $first->update(['tested_at' => now()->subDays(2), 'status' => 'final']);

    // Genuine prior on another order
    $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    $prior = createResultForLabTest($priorOrder, $test, $this->user);
    $prior->update(['status' => 'final', 'tested_at' => now()->subDays(5)]);
    LabResultItem::where('lab_result_id', $prior->id)->update(['value' => '11.0']);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    $priors = collect($report['pages'][0]['sections'][0]['items'][0]->previous_values);

    expect($priors)->toHaveCount(1)
        ->and($priors->pluck('value')->all())->toBe(['11.0']);
});

it('issues a single history query regardless of parameter count (no N+1)', function () {
    $t1 = createLabTestWithParams('Panel A', 3);
    $t2 = createLabTestWithParams('Panel B', 2);

    $priorOrder = makePriorOrderForPatient($this->patient, $this->visit, $this->doctor);
    createResultForLabTest($priorOrder, $t1, $this->user);
    createResultForLabTest($priorOrder, $t2, $this->user);

    createResultForLabTest($this->order, $t1, $this->user);
    createResultForLabTest($this->order, $t2, $this->user);

    DB::connection('tenant')->flushQueryLog();
    DB::connection('tenant')->enableQueryLog();

    LabReportBuilder::build($this->order->fresh(['items.labTest']));

    $historyQueries = collect(DB::connection('tenant')->getQueryLog())
        ->filter(function (array $q) {
            $sql = strtolower($q['query']);
            // The batched history lookup joins orders/results/items and filters patient + parameter IN
            return str_contains($sql, 'lab_result_items')
                && str_contains($sql, 'lab_orders')
                && (str_contains($sql, 'lab_test_parameter_id') || str_contains($sql, '"lab_test_parameter_id"'));
        });

    expect($historyQueries->count())->toBe(1);
});
```

Implement `makePriorOrderForPatient` as a local helper creating a distinct `LabOrder` for the same patient (new visit optional).

- [ ] **Step 3: Run — expect FAIL**

```bash
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php
```

Expected: FAIL — `previous_values` missing / default key missing / query count ≠ 1.

- [ ] **Step 4: Minimal implementation**

1. `LabReportPrintSettings::DEFAULTS` add `'previous_values_count' => 3`.
2. In `get()`: for bool keys keep `(bool)`; for `previous_values_count` cast `(int)` and clamp 1–5 (missing → 3). Leave `put()` bool-only for now **except** preserve int on round-trip if already present, or skip rewriting int until Task 3 — simplest: Task 1 only changes `get()` + DEFAULTS; `put()` still iterates DEFAULTS — **must not** run `previous_values_count` through `FILTER_VALIDATE_BOOLEAN` or it becomes wrong. So Task 1 **must** split `put()` enough to leave ints alone:

```php
// Sketch — finalize in Task 3 with validation
foreach (self::DEFAULTS as $key => $default) {
    if ($key === 'previous_values_count') {
        $normalized[$key] = isset($toggles[$key])
            ? max(1, min(5, (int) $toggles[$key]))
            : (int) $default;
        continue;
    }
    $normalized[$key] = array_key_exists($key, $toggles)
        ? filter_var($toggles[$key], FILTER_VALIDATE_BOOLEAN)
        : false;
}
```

Note: changing `put()` missing-bool → false behavior for bools stays; for int, missing on put should use **default 3** (not 0/false) when Task 3 form always sends the radio. Document in Task 3.

3. `LabReportBuilder`:
   - `public const PREVIOUS_VALUE_ROW_COST = 1;` (unused for cost until Task 2 — may define now).
   - Private/static `loadPreviousValuesByParameterId(LabOrder $order, Collection $labResults): array` returning `array<int, list<priorDTO>>` keyed by parameter id.
   - After loading `$labResults` in `build()`, call attach: for each result item set `$item->previous_values = $map[$id] ?? []`.
   - Query via Eloquent/`DB` on tenant connection matching filters in Shared types.

```php
$n = max(1, min(5, (int) (LabReportPrintSettings::get()['previous_values_count'] ?? 3)));

$parameterIds = $labResults->flatMap->resultItems
    ->pluck('lab_test_parameter_id')
    ->filter()
    ->unique()
    ->values()
    ->all();

// if empty, skip query; all previous_values = []

$rows = LabResultItem::query()
    ->select('lab_result_items.*')
    ->join('lab_results', 'lab_results.id', '=', 'lab_result_items.lab_result_id')
    ->join('lab_orders', 'lab_orders.id', '=', 'lab_results.lab_order_id')
    ->where('lab_orders.patient_id', $order->patient_id)
    ->where('lab_orders.id', '!=', $order->id)
    ->whereIn('lab_result_items.lab_test_parameter_id', $parameterIds)
    ->whereIn('lab_results.status', ['reported', 'final'])
    ->orderByDesc('lab_results.tested_at')
    ->orderByDesc('lab_results.id')
    ->with([]) // avoid extra queries; pull tested_at via join select
    ->addSelect([
        'lab_results.tested_at as prior_tested_at',
        'lab_results.status as prior_status',
    ])
    ->get();

// groupBy lab_test_parameter_id, take($n), map to DTO
```

Attach before `buildSections`.

- [ ] **Step 5: Run Task 1 tests — expect PASS**

```bash
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php
```

- [ ] **Step 6: Zero-history + path regression**

```bash
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
php artisan test --compact tests/Feature/Lab/LabReportPrintChromeTest.php
```

Expected: all PASS. Builder packing numbers unchanged (no priors in those fixtures).

- [ ] **Step 7: Commit**

```bash
git add -f docs/superpowers/specs/2026-09-24-lab-report-previous-values-design.md
git add -f docs/superpowers/plans/2026-09-24-lab-report-previous-values.md
git add app/Services/LabReportBuilder.php app/Support/LabReportPrintSettings.php tests/Feature/Lab/LabReportPreviousValuesTest.php
git commit -m "$(cat <<'EOF'
feat: batch-load previous lab parameter values for reports

Attach per-parameter prior results (reported/final only) in one query,
excluding the current order, with empty lists when there is no history.

EOF
)"
```

**(Windows agents:** if HEREDOC unavailable, use an equivalent PowerShell here-string / `git commit -m` multi `-m`.)

- [ ] **Step 8: Phase-boundary report 1** — list commands + PASS; confirm zero-history packing unchanged; confirm single history query test green. Do not start Task 2 until human acknowledges the phase report (plan standing rule).

---

### Task 2: Pagination cost formula extension

**Files:**

- Modify: `app/Services/LabReportBuilder.php` (`makeSection` only for cost math)
- Modify: `tests/Feature/Lab/LabReportPreviousValuesTest.php` (add packing cost cases)
- Confirm: `tests/Feature/Lab/LabReportBuilderTest.php` still green (zero history)

**Interfaces:**

- Consumes: items with `previous_values` from Task 1.
- Produces: `section['row_cost']` = `2 + Σ(1 + count(priors)) + 1`; `packIntoPages` unchanged structurally; budgets remain 8/30.

- [ ] **Step 1: Write failing packing/cost tests**

```php
it('costs one extra row per attached previous value', function () {
    $test = createLabTestWithParams('Sodium', 1);
    // 2 priors on older orders...
    // current order 1 param with 2 previous_values attached via real history setup

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));
    // header 2 + (1 current + 2 priors) + footer 1 = 6
    expect($report['pages'][0]['sections'][0]['row_cost'])->toBe(6)
        ->and($report['pages'][0]['sections'][0]['items'][0]->previous_values)->toHaveCount(2);
});

it('does not inflate cost when previous_values is empty', function () {
    $a = createLabTestWithParams('Alpha', 1);
    $b = createLabTestWithParams('Beta', 1);
    createResultForLabTest($this->order, $a, $this->user);
    createResultForLabTest($this->order, $b, $this->user);

    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'])->toHaveCount(1)
        ->and($report['pages'][0]['row_cost'])->toBe(8); // same as LabReportBuilderTest
});

it('packs fewer first-page sections when priors make a section exceed remaining budget', function () {
    // Two 1-param sections; give each 3 priors → cost 2+(1+3)+1 = 7 each.
    // FIRST_PAGE_ROW_BUDGET 8 fits only one section (7), second spills.
    // Setup: N default 3; three prior orders each with both tests' params...
    $report = LabReportBuilder::build($this->order->fresh(['items.labTest']));

    expect($report['pages'][0]['sections'])->toHaveCount(1)
        ->and($report['pages'][0]['sections'][0]['row_cost'])->toBe(7)
        ->and($report['pages'])->toHaveCount(2);
});
```

Fill helpers so prior counts are exact (2 vs 3 vs 0). Cap assertion: if settings N=3 but only 1 prior exists, cost uses **1** extra not 3.

- [ ] **Step 2: Run — expect FAIL** (costs still ignore priors)

```bash
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php --filter="costs one extra|does not inflate|packs fewer"
```

- [ ] **Step 3: Implement `makeSection` cost**

```php
public static function makeSection(LabTest $investigation, array $resultItems): array
{
    $itemRows = 0;
    foreach ($resultItems as $item) {
        $priorCount = is_array($item->previous_values ?? null)
            ? count($item->previous_values)
            : 0;
        $itemRows += 1 + (static::PREVIOUS_VALUE_ROW_COST * $priorCount);
    }

    $rowCost = static::SECTION_HEADER_ROWS + $itemRows + static::SECTION_FOOTER_ROWS;

    return [
        'investigation' => $investigation,
        'items' => $resultItems,
        'row_cost' => $rowCost,
        'is_large' => $rowCost > static::PAGE_ROW_BUDGET,
    ];
}
```

Do **not** change `FIRST_PAGE_ROW_BUDGET` / `PAGE_ROW_BUDGET` values. Do **not** rewrite `packIntoPages`.

- [ ] **Step 4: Run PreviousValues + Builder tests — PASS**

```bash
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Services/LabReportBuilder.php tests/Feature/Lab/LabReportPreviousValuesTest.php
git commit -m "$(cat <<'EOF'
feat: include previous-value lines in lab report row costs

Extend makeSection so each prior line adds one estimated row while
zero-history reports keep the original cost and packing behavior.

EOF
)"
```

- [ ] **Step 6: Phase-boundary report 2** — zero-history costs unchanged; full-N spill case documented; budgets still 8/30.

---

### Task 3: Settings schema + typed get/put + radio UI

**Files:**

- Modify: `app/Support/LabReportPrintSettings.php` (complete typed get/put)
- Modify: `app/Http/Requests/UpdateLabReportPrintSettingsRequest.php`
- Modify: `resources/views/settings/lab-report-print/edit.blade.php`
- Modify: `tests/Feature/Settings/LabReportPrintSettingsTest.php`

**Interfaces:**

- Consumes: existing `lab_report_print` JSON + checkbox form.
- Produces: `previous_values_count` validated 1–5; radios on edit; `get()` default 3 with zero rows; `put()` never bool-casts the int.

- [ ] **Step 1: Write failing settings tests**

Update existing default assertion to include `'previous_values_count' => 3`.

```php
it('defaults previous_values_count to 3 for new tenants', function () {
    expect(LabReportPrintSettings::get())->toMatchArray([
        'previous_values_count' => 3,
        'show_logo' => true,
    ]);
});

it('saves previous_values_count from radio selection', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->get(route('settings.lab-report-print.edit'))
        ->assertOk()
        ->assertSee('name="previous_values_count"', false)
        ->assertSee('Previous results per parameter', false);

    $payload = [
        'show_logo' => '1',
        'show_qr' => '1',
        'show_hospital_address' => '1',
        'show_hospital_phone' => '1',
        'show_hospital_email' => '1',
        'show_hospital_website' => '1',
        'show_patient_band' => '1',
        'show_reviewers' => '1',
        'show_page_numbers' => '1',
        'previous_values_count' => '5',
    ];

    $this->put(route('settings.lab-report-print.update'), $payload)
        ->assertRedirect();

    expect(LabReportPrintSettings::get()['previous_values_count'])->toBe(5);
});

it('rejects previous_values_count outside 1-5', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->from(route('settings.lab-report-print.edit'))
        ->put(route('settings.lab-report-print.update'), [
            // all show_* as needed...
            'previous_values_count' => '9',
        ])
        ->assertSessionHasErrors('previous_values_count');
});
```

Also assert Builder respects saved N (optional here or in PreviousValuesTest): put count=1 → only one prior attached.

- [ ] **Step 2: Run — expect FAIL** on radio see / save / validation

```bash
php artisan test --compact tests/Feature/Settings/LabReportPrintSettingsTest.php
```

- [ ] **Step 3: Implement**

`UpdateLabReportPrintSettingsRequest`:

```php
public function rules(): array
{
    $rules = [];
    foreach (array_keys(LabReportPrintSettings::DEFAULTS) as $key) {
        if ($key === 'previous_values_count') {
            $rules[$key] = ['required', 'integer', 'min:1', 'max:5'];
            continue;
        }
        $rules[$key] = ['sometimes', 'boolean'];
    }
    return $rules;
}

protected function prepareForValidation(): void
{
    $payload = [];
    foreach (array_keys(LabReportPrintSettings::DEFAULTS) as $key) {
        if ($key === 'previous_values_count') {
            continue; // do not boolean-cast
        }
        $payload[$key] = $this->boolean($key);
    }
    $this->merge($payload);
}
```

Blade: after checkbox list, radio group values 1–5 labeled “Previous results per parameter” with short help text; `@checked` / `old('previous_values_count', $toggles['previous_values_count'] ?? 3)`.

Ensure existing save test payloads include `previous_values_count` so they keep passing.

- [ ] **Step 4: Run settings + lab regression**

```bash
php artisan test --compact tests/Feature/Settings/LabReportPrintSettingsTest.php
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
```

- [ ] **Step 5: Commit**

```bash
git add app/Support/LabReportPrintSettings.php app/Http/Requests/UpdateLabReportPrintSettingsRequest.php resources/views/settings/lab-report-print/edit.blade.php tests/Feature/Settings/LabReportPrintSettingsTest.php
git commit -m "$(cat <<'EOF'
feat: add previous_values_count to lab report print settings

Typed int 1-5 (default 3) on the lab_report_print JSON key with radio
UI, without breaking existing boolean toggles or new-tenant defaults.

EOF
)"
```

- [ ] **Step 6: Phase-boundary report 3**

---

### Task 4: Report layout (sibling rows + muted styling)

**Files:**

- Modify: `resources/views/admin/lab/results/report.blade.php`
- Modify: `tests/Feature/Lab/LabReportPreviousValuesTest.php` and/or `tests/Feature/Lab/LabReportPrintChromeTest.php` (HTTP assertSee for values/dates/CSS class)

**Interfaces:**

- Consumes: `$item->previous_values` on section items; print toggles unchanged.
- Produces: sibling `<tr class="previous-result">` per prior; L5 omit when empty; OQ2–OQ4 formatting.

- [ ] **Step 1: Write failing HTTP/layout tests**

```php
it('renders sibling previous-result rows with date-only tested_at and omits them when empty', function () {
    // actingAs + permission like LabReportPrintChromeTest
    // Create prior with value 88, tested_at = Carbon::parse('2026-01-15'), flag H
    // Current order report GET lab-results.report or order report route

    // Authenticated paths (both must stay green for zero-history):
    // - lab-results.report → LabResultController::report
    // - investigation-orders.report → LabResultController::orderReport
    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertSee('previous-result', false)
        ->assertSee('88')
        ->assertSee('15 Jan 2026') // d M Y
        ->assertDontSee('No previous'); // L5 — no placeholder copy
});

it('omits previous-result markup when the patient has no prior history', function () {
    // current-only fixture
    $this->get(route('investigation-orders.report', $this->order))
        ->assertOk()
        ->assertDontSee('previous-result', false);
});
```

Also cover **public** view after unlock: reuse PublicLabReportTest session unlock pattern; assert sibling rows appear there too when history exists, and are absent for zero history.

- [ ] **Step 2: Run — expect FAIL** (class/date not in HTML)

- [ ] **Step 3: Blade + CSS**

Inside results `<tbody>` loop, after the current `<tr>`:

```blade
@foreach(($item->previous_values ?? []) as $prior)
    <tr class="previous-result">
        <td></td>
        <td class="@if(!empty($prior['flag']) && $prior['flag'] !== 'N') result-abnormal-muted @endif">
            {{ $prior['value'] }}
        </td>
        <td>{{ $prior['unit'] ?? '—' }}</td>
        <td>{{ !empty($prior['tested_at']) ? \Illuminate\Support\Carbon::parse($prior['tested_at'])->format('d M Y') : '' }}</td>
    </tr>
@endforeach
```

CSS (print-friendly):

```css
tr.previous-result td { color: #6b7280; font-size: 0.85em; }
.result-abnormal-muted { color: #c2410c; font-weight: 600; } /* muted vs .result-abnormal */
```

Reuse existing abnormal color at reduced emphasis (OQ4). Empty Parameter cell (OQ3). No nested `<table>`.

- [ ] **Step 4: Run layout + zero-history + public**

```bash
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php
php artisan test --compact tests/Feature/Lab/LabReportPrintChromeTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/lab/results/report.blade.php tests/Feature/Lab/
git commit -m "$(cat <<'EOF'
feat: print previous lab values as muted sibling rows

Show prior results under each parameter with date-only tested_at and
muted flags, omitting markup entirely when there is no history.

EOF
)"
```

- [ ] **Step 6: Phase-boundary report 4**

---

### Task 5: Full regression (reviewer footer / page numbers / QR paths)

**Files:**

- Test-only adjustments if any flake surfaces; no intentional product changes.
- Modify tests only if needed to assert coexistence explicitly.

**Interfaces:**

- Consumes: full feature from Tasks 1–4.
- Produces: green suite for lab print chrome + previous values; explicit proof reviewers + page numbers still render; **no merge**.

- [ ] **Step 1: Write/extend coexistence assertions** (failing if footer broken)

In `LabReportPrintChromeTest` or PreviousValuesTest:

```php
it('still shows reviewer blocks and page numbers when previous values are present', function () {
    // Fixture: roster reviewer on result + prior history + multi-page if easy
    // GET report
    // assertSee reviewer name / Page 1 of
    // assertSee previous-result
});
```

If existing chrome tests already cover reviewers/pages on zero-history reports, add **one** combined case with priors so both appear on the same response.

- [ ] **Step 2: Run full lab + settings regression**

```bash
php artisan test --compact tests/Feature/Lab/LabReportPreviousValuesTest.php
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
php artisan test --compact tests/Feature/Lab/LabReportPrintChromeTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
php artisan test --compact tests/Feature/Lab/LabResultReviewerTest.php
php artisan test --compact tests/Feature/Settings/LabReportPrintSettingsTest.php
```

Expected: all PASS. Zero-history packing unchanged. Public unlock still works. Reviewers + page numbers present when toggles on.

- [ ] **Step 3: Manual sanity checklist (record in phase report)**

- [ ] Authenticated order report, new patient (0 priors): no `previous-result` rows; page numbers OK.
- [ ] Authenticated report with ≥1 prior: sibling rows + dates `d M Y`.
- [ ] Public QR unlock view: same body behavior.
- [ ] Settings radios save 1 and 5; report respects N.
- [ ] Last page still shows horizontal reviewer blocks when configured.

- [ ] **Step 4: Commit** (if test-only changes)

```bash
git add tests/Feature/Lab/
git commit -m "$(cat <<'EOF'
test: cover previous values with reviewer footer and page numbers

EOF
)"
```

- [ ] **Step 5: Phase-boundary report 5 — STOP.** Branch ready for human confirmation. **Do not merge. Do not open PR unless asked.**

---

## Spec coverage self-check

| Spec item | Task |
|---|---|
| P1 batched query + PHP take(N) + exclude current order | Task 1 |
| OQ1 status filter | Task 1 |
| OQ5 tie-break | Task 1 |
| P2 `tested_at` | Task 1 (+ display Task 4) |
| P3 cost formula + keep budgets | Task 2 |
| L5 cost/actual count | Task 2 |
| P4 settings int + radios | Task 3 (DEFAULTS/get start in Task 1) |
| L4 new-tenant default 3 | Tasks 1 + 3 |
| P5 sibling rows, OQ2–OQ4 | Task 4 |
| P6 footer/page-number non-conflict | Task 5 |
| Zero-history auth + public unaffected | Tasks 1, 2, 4, 5 regression steps |
| No merge until confirmed | Header + Task 5 |

## Placeholder scan

No TBD steps. Report routes locked: `investigation-orders.report` (order) and `lab-results.report` (single result). Windows commit HEREDOC note included.

## Type consistency

- `previous_values` list of arrays with `value`, `unit`, `flag`, `tested_at` — same shape Tasks 1–4.
- Settings key `previous_values_count` int — Tasks 1 and 3.
- `PREVIOUS_VALUE_ROW_COST = 1` — Task 2.
- CSS class `previous-result` — Tasks 4–5.
