# Lab Report Header/Footer, QR & Reviewing Consultants Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> Do **not** start execution until a human confirms this plan. Do **not** merge any branch into `main` until the user explicitly asks — and only after every task is implemented, tested, and free of known bugs.
>
> **New feature branch:** create `feat/lab-report-print-chrome` from current HEAD **before Task 1**. Do not work on `main`.
>
> **High-risk lab surfaces.** Every task that touches `LabResult` / `LabOrder` / verify / report Blade must prove **existing lab report generation, public share/verify flow, and WhatsApp sharing are unaffected** (paired regression), not only that new cases pass.
>
> **Phase-boundary reports (override SDD “don’t pause” at these points only):**
> 1. After Task 1 (legacy signed route removal).
> 2. After Task 2 (reviewers pivot + verify selection UI).
> 3. After Task 3 (`settings.lab-report-print` registration + toggles page).
> 4. After Task 4 (consultant roster screen).
> 5. After Task 5 (Patient Detail band + Q5 hospital location).
> 6. After Task 6 (`hospital_website`).
> 7. After Task 7 (QR generation).
> 8. After Task 8 (`FIRST_PAGE_ROW_BUDGET` → 8).
> 9. After Task 9 (page numbers).
> 10. After Task 10 (full regression). **No merge.**

**Goal:** Ship configurable lab-report print chrome (header QR + Patient Detail + reviewer footer + page numbers + Hospital website) and a Doctor reviewer roster/verify selection flow, without changing result-body rendering or the public verify gate.

**Architecture:** Keep HTML/`window.print`. Remove dead signed numeric public route. Add `lab_result_reviewers` pivot and `lab_report_roster_doctors` table; verify form selects roster Doctors. New settings child `settings.lab-report-print` (toggles + roster). Report Blade gains hospital registration location, QR SVG via `bacon/qr-code`, Blade page N of M, and `FIRST_PAGE_ROW_BUDGET = 8`.

**Tech Stack:** Laravel 12, Pest, Spatie Permission/multitenancy, `bacon/qr-code` (pure PHP SVG), existing `LabReportBuilder`, `PublicLabReportController`, Settings section pattern.

**Spec:** `docs/superpowers/specs/2026-09-21-lab-report-header-footer-public-qr-design.md` (confirmed; Q5 corrected). Investigation (do not re-open): `docs/superpowers/specs/2026-09-21-lab-report-header-footer-public-qr-investigation.md`.

## Global Constraints

- **L1 — Result body unchanged.** Do not alter test-panel / parameter table markup semantics.
- **L2 — Verify gate unchanged.** QR encodes `LabOrder::publicReportUrl()` only; patient_no + phone challenge stays.
- **L3 — No sequential public IDs.**
- **L4 — Lab stays HTML print** — no DomPDF migration for lab.
- **L5 — Omit-if-empty** for missing hospital/contact/credential/consultant lines (no `Pending` for missing reviewers).
- **Confirmed Qs:** Q1 first reviewer only in patient band; Q2 `lab_report_roster_doctors` table; Q3 no PMDC line; Q4 **horizontal wrapping** reviewer blocks; Q5 Registration Location = `hospital_name` + `hospital_address` from Hospital Info (**not** visit_type); Q6 settings child entitlement **independent** of laboratory (like prescription-print).
- **`pathologist_id` retained** as User audit stamp of who clicked Verify — not the printable consultant.
- **No “main consultant only” toggle** — roster of one is enough.
- Reviewer selection on verify is **optional**; zero reviewers → omit footer blocks and Consultant band line.
- Tests: `php artisan test --compact`. `Feature/Lab` and `Feature/Settings` already in `tests/Pest.php` tenant `in()` list. Force-add `docs/superpowers/` (`git add -f`). Do not merge to `main`.
- **Lab regression rule (every phase that touches lab):** run at least `tests/Feature/Lab/PublicLabReportTest.php` and `tests/Feature/Lab/LabReportBuilderTest.php`, plus a WhatsApp share assertion (existing test or one added in Task 1 hygiene). Phase report must list commands + PASS.
- **Dependency note:** Confirmed user sequence listed roster before catalog registration; this plan runs **registration + toggles (Task 3) before roster UI (Task 4)** so the settings screen exists before rows are managed. Both deliverables are covered.

## Confirmed decisions (baked in)

| ID | Decision |
|---|---|
| Q1 | Patient band Consultant = **first** reviewer only |
| Q2 | Roster table `lab_report_roster_doctors` |
| Q3 | No PMDC line |
| Q4 | Horizontal wrapping credential blocks |
| Q5 | Registration Location = Hospital Info **name + address** |
| Q6 | Independent settings child entitlement |
| QR | `bacon/qr-code` → SVG |
| Budget | `FIRST_PAGE_ROW_BUDGET = 8` |
| Pages | Blade `Page N of M` from builder pages |

## File map

**Create:**

- `database/migrations/tenant/2026_09_21_120000_create_lab_result_reviewers_table.php`
- `database/migrations/tenant/2026_09_21_120001_create_lab_report_roster_doctors_table.php`
- `app/Models/LabReportRosterDoctor.php`
- `app/Support/LabReportPrintSettings.php` (toggle defaults + read/write JSON setting key `lab_report_print`)
- `app/Services/LabReportQrCode.php` (SVG from public URL)
- `app/Http/Controllers/Settings/LabReportPrintController.php`
- `app/Http/Requests/UpdateLabReportPrintSettingsRequest.php`
- `app/Http/Requests/UpdateLabReportRosterRequest.php` (or combined)
- `resources/views/settings/lab-report-print/edit.blade.php`
- `resources/js/lab-report-roster-form.js`
- `tests/Feature/Lab/LabPublicRouteHygieneTest.php` (Task 1)
- `tests/Feature/Lab/LabResultReviewerTest.php` (Task 2)
- `tests/Feature/Settings/LabReportPrintSettingsTest.php` (Tasks 3–4)
- `tests/Feature/Lab/LabReportPrintChromeTest.php` (Tasks 5–9 chrome assertions)
- `tests/Unit/Services/LabReportQrCodeTest.php` (Task 7)

**Modify:**

- `routes/web.php` — remove legacy signed route; add settings.lab-report-print routes; verify already exists
- `app/Http/Controllers/LabResultController.php` — verify accepts reviewer doctor ids; remove `publicReport`
- `resources/views/admin/lab/results/show.blade.php` — verify form roster checkboxes
- `resources/views/admin/lab/results/report.blade.php` — header/QR/patient band/footer/page numbers
- `app/Models/LabResult.php` — `reviewers()` belongsToMany
- `app/Models/Doctor.php` — inverse relation optional
- `app/Services/LabReportBuilder.php` — budget constant; optionally pass aggregated reviewers in build payload
- `app/Models/ModuleRegistry.php` — child slug + children list
- `app/Support/SettingsSectionRegistry.php`
- `app/Support/PermissionRegistry.php`
- `app/Http/Controllers/SettingsController.php` / `UpdateSettingsRequest` / `hospital-info.blade.php` — `hospital_website`
- `composer.json` / `composer.lock` — `bacon/qr-code` (Task 7)
- `vite.config.js` — register `lab-report-roster-form.js`
- Unit/Feature module registry / settings section tests as needed for new slug counts

**Do not modify:** Prescription Print DomPDF stack; public verify challenge logic; `LabOrder::generateShareToken` / `publicReportUrl` semantics (aside from QR consuming the URL).

**Shared types (lock now):**

```php
// Setting JSON key lab_report_print — defaults all true:
[
  'show_logo' => true,
  'show_qr' => true,
  'show_hospital_address' => true,
  'show_hospital_phone' => true,
  'show_hospital_email' => true,
  'show_hospital_website' => true,
  'show_patient_band' => true,
  'show_reviewers' => true,
  'show_page_numbers' => true,
]

// Pivot table lab_result_reviewers: lab_result_id, doctor_id, sort_order
// Table lab_report_roster_doctors: doctor_id (unique), sort_order

// Credential lines (verbatim):
// Dr. {name}
// {qualification}   // omit line if empty
// {specialization}  // omit line if empty

LabReportQrCode::svg(string $url): string  // raw SVG markup
LabReportPrintSettings::get(): array
LabReportPrintSettings::put(array $toggles): void
```

**Lab regression helper (use in phase reports):**

```bash
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
```

Plus WhatsApp: assert `LabOrder::publicReportUrl()` still returns token route (unit or feature in Task 1 / reuse later).

---

### Task 1: Feature branch + legacy signed route removal

**Files:**

- Create: `tests/Feature/Lab/LabPublicRouteHygieneTest.php`
- Modify: `routes/web.php` (remove ~1004–1008)
- Modify: `app/Http/Controllers/LabResultController.php` (remove `publicReport`)

**Interfaces:**

- Consumes: current `lab-results.public-report` registration.
- Produces: `Route::has('lab-results.public-report') === false`; token routes and `publicReportUrl()` unchanged.

- [ ] **Step 1: Create branch + re-confirm zero callers**

```bash
git checkout -b feat/lab-report-print-chrome
rg -n "lab-results\.public-report|publicReport\(|lab-report/\{labResult\}" app resources routes tests --glob "!vendor/**"
```

Expected: on feature branch; no production callers beyond route + `publicReport` method.

- [ ] **Step 2: Write failing hygiene tests**

```php
<?php

use App\Models\LabOrder;
use App\Models\Patient;
use Illuminate\Support\Facades\Route;

it('does not register lab-results.public-report', function () {
    expect(Route::has('lab-results.public-report'))->toBeFalse();
});

it('still registers token public lab report routes', function () {
    expect(Route::has('lab-report.show'))->toBeTrue()
        ->and(Route::has('lab-report.verify'))->toBeTrue()
        ->and(Route::has('lab-report.view'))->toBeTrue();
});

it('publicReportUrl still points at the token show route', function () {
    $patient = Patient::create([
        'name' => 'Share Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03001112233',
    ]);
    $order = LabOrder::create([
        'patient_id' => $patient->id,
        'priority' => 'routine',
        'status' => 'completed',
        'ordered_at' => now(),
    ]);

    expect($order->publicReportUrl())->toContain($order->ensureShareToken())
        ->and($order->publicReportUrl())->toContain('/lab-report/');
});
```

- [ ] **Step 3: Run — expect FAIL on public-report still registered**

Run: `php artisan test --compact tests/Feature/Lab/LabPublicRouteHygieneTest.php`

- [ ] **Step 4: Remove route + `publicReport` method**

- [ ] **Step 5: GREEN + lab regression**

```bash
php artisan test --compact tests/Feature/Lab/LabPublicRouteHygieneTest.php
php artisan test --compact tests/Feature/Lab/PublicLabReportTest.php
php artisan test --compact tests/Feature/Lab/LabReportBuilderTest.php
```

- [ ] **Step 6: Commit + Phase 1 report**

```bash
git add routes/web.php app/Http/Controllers/LabResultController.php tests/Feature/Lab/LabPublicRouteHygieneTest.php
git add -f docs/superpowers/plans/2026-09-21-lab-report-header-footer-public-qr.md docs/superpowers/specs/2026-09-21-lab-report-header-footer-public-qr-design.md
git commit -m "fix: remove legacy signed numeric lab public report route"
```

**Stop for phase report.**

---

### Task 2: Reviewers pivot + verify selection UI

**Files:**

- Create: migration `lab_result_reviewers`
- Modify: `LabResult` — `reviewers()` belongsToMany
- Modify: `LabResultController::verify` — sync reviewers from request
- Modify: `show.blade.php` verify form — checkboxes of roster doctors (roster may be empty until Task 4; when empty, no checkboxes)
- Create: `tests/Feature/Lab/LabResultReviewerTest.php`

**Interfaces:**

- Consumes: `Doctor` ids; optional until roster exists (empty list OK).
- Produces: `$labResult->reviewers()`; verify POST `reviewer_doctor_ids` array; `pathologist_id` still `auth()->id()`.

- [ ] **Step 1: Failing tests**

Cover:

1. Verify with two doctor ids → pivot rows with sort_order 0,1; `pathologist_id` = acting user.
2. Verify with zero ids → no pivot rows; status final; pathologist still set.
3. Report/print omits consultant footer when no reviewers (may land fully in Task 5/9 — assert model state here).
4. Invalid doctor id not on roster (after Task 4) — for now accept any existing Doctor id; **tighten to roster-only in Task 4** (document in test as `// Task 4 will restrict to roster`).

```php
it('stores selected reviewer doctors on verify and keeps pathologist as acting user', function () {
    // arrange lab result + two doctors + acting user with edit lab results
    $this->post(route('lab-results.verify', $labResult), [
        'reviewer_doctor_ids' => [$doctorA->id, $doctorB->id],
    ])->assertRedirect();

    expect($labResult->fresh()->pathologist_id)->toBe($this->user->id)
        ->and($labResult->reviewers()->pluck('doctors.id')->all())
        ->toBe([$doctorA->id, $doctorB->id]);
});

it('allows verify with zero reviewers', function () {
    $this->post(route('lab-results.verify', $labResult), [])
        ->assertRedirect();
    expect($labResult->reviewers)->toHaveCount(0)
        ->and($labResult->fresh()->status)->toBe('final');
});
```

- [ ] **Step 2: RED**

- [ ] **Step 3: Migration + model + controller sync + Blade checkboxes**

Verify method sketch:

```php
public function verify(Request $request, LabResult $labResult)
{
    $ids = collect($request->input('reviewer_doctor_ids', []))
        ->map(fn ($id) => (int) $id)
        ->filter()
        ->unique()
        ->values();

    // Task 4: intersect with LabReportRosterDoctor::query()->pluck('doctor_id')

    $sync = [];
    foreach ($ids as $i => $doctorId) {
        $sync[$doctorId] = ['sort_order' => $i];
    }

    $labResult->update([
        'status' => 'final',
        'pathologist_id' => auth()->id(),
        'verified_at' => now(),
        'reported_at' => now(),
    ]);
    $labResult->reviewers()->sync($sync);

    return back()->with('success', 'Results verified and finalized.');
}
```

- [ ] **Step 4: GREEN + lab regression (PublicLabReport + LabReportBuilder + verify happy path)**

- [ ] **Step 5: Commit + Phase 2 report**

```bash
git commit -m "feat: attach reviewing doctors to lab results on verify"
```

**Stop.**

---

### Task 3: `settings.lab-report-print` registration + toggles page

**Files:**

- Modify: `ModuleRegistry`, `SettingsSectionRegistry`, `PermissionRegistry`
- Create: `LabReportPrintController`, `LabReportPrintSettings`, `UpdateLabReportPrintSettingsRequest`, `settings/lab-report-print/edit.blade.php`
- Routes under `settings.section:settings.lab-report-print`
- Tests: `LabReportPrintSettingsTest` — module mapping, 403 without child, form saves toggles
- Update module count assertions in `ModuleRegistryTest` if they hard-code `toHaveCount(52)` → bump

**Interfaces:**

- Produces: slug `settings.lab-report-print`; permission `access settings.lab-report-print`; `LabReportPrintSettings::get()/put()`; settings UI with checkboxes for all toggles in Shared types.

- [ ] **Step 1: Failing module/settings tests** (mirror hospital-info / prescription-print patterns in `SettingsSectionRegistryTest`, `CheckModuleTest` style, `ModuleRegistryTest`)

- [ ] **Step 2: Implement registration + controller + Blade toggles**

- [ ] **Step 3: GREEN + regression** (settings section tests; lab Public/Builder untouched)

- [ ] **Step 4: Commit + Phase 3 report**

```bash
git commit -m "feat: add settings.lab-report-print module and toggle screen"
```

**Stop.**

---

### Task 4: Consultant roster screen

**Files:**

- Create: migration `lab_report_roster_doctors`, model `LabReportRosterDoctor`
- Modify: settings Blade + `lab-report-roster-form.js` (copy Doctor Share add/remove pattern)
- Tighten Task 2 verify to **roster-only** doctor ids
- Tests: roster add/remove persistence; verify rejects non-roster doctor id (422 or silent filter — **prefer ignore unknown ids** with sync of intersection only, document in test)

**Interfaces:**

- Produces: ordered roster; verify intersects request ids with roster.

- [ ] **Step 1: Failing roster + verify-roster-only tests**

- [ ] **Step 2: Implement table + UI + verify intersect**

- [ ] **Step 3: GREEN + lab regression**

- [ ] **Step 4: Commit + Phase 4 report**

```bash
git commit -m "feat: configure lab report consultant roster in settings"
```

**Stop.**

---

### Task 5: Patient Detail band (incl. corrected Q5)

**Files:**

- Modify: `report.blade.php` patient-box fields
- Optionally enrich `LabReportBuilder::build` payload with `departments`, `registration_*` helpers
- Create/extend: `tests/Feature/Lab/LabReportPrintChromeTest.php`

**Field mapping (verbatim):**

| Label | Value |
|---|---|
| Registration Location | `setting('hospital_name')` + omit-empty `setting('hospital_address')` |
| Registration Date | visit `visit_datetime` else `ordered_at` |
| Case # | `order_number` (remove separate Order # line) |
| Note | truncated `clinical_notes` |
| Department | unique title-cased LabTest categories on report |
| Consultant | first reviewer name or omit |
| Existing | name, age/sex, referred by, patient #, collection, reporting |

- [ ] **Step 1: Failing feature tests** — authenticated report HTML contains Registration Location with hospital name; does **not** use visit_type string as location; Case # present; clinical note present when set.

- [ ] **Step 2: Implement Blade (+ builder helpers if needed)**

- [ ] **Step 3: GREEN + PublicLabReportTest** (public view uses same Blade — assert location still hospital name)

- [ ] **Step 4: Commit + Phase 5 report**

```bash
git commit -m "feat: expand lab report patient detail band"
```

**Stop.**

---

### Task 6: `hospital_website`

**Files:**

- Modify: `hospital-info.blade.php`, `SettingsController`, `UpdateSettingsRequest`
- Modify: report footer/letterhead `@if` website
- Tests: settings save; report shows website when set, omits when empty

- [ ] **Step 1–4:** TDD, implement, lab+settings regression, commit

```bash
git commit -m "feat: add hospital_website to hospital info and lab report"
```

**Stop.**

---

### Task 7: QR generation (`bacon/qr-code`)

**Files:**

- `composer require bacon/qr-code`
- Create: `LabReportQrCode::svg`
- Modify: report header 3-column grid; respect `show_qr` toggle
- Unit test: SVG contains `svg` and is deterministic for fixed URL
- Feature: report HTML contains SVG when toggle on; public view still requires verify before report (gate unchanged)

- [ ] **Step 1: Failing unit + feature**

- [ ] **Step 2: Install package + service + Blade**

- [ ] **Step 3: GREEN + PublicLabReportTest** (scan still hits verify first — QR URL equals `publicReportUrl()`)

- [ ] **Step 4: Commit + Phase 7 report**

```bash
git commit -m "feat: embed public lab report URL as QR in report header"
```

**Stop.**

---

### Task 8: `FIRST_PAGE_ROW_BUDGET` → 8

**Files:**

- Modify: `LabReportBuilder::FIRST_PAGE_ROW_BUDGET = 8`
- Update/add packing tests in `LabReportBuilderTest`

- [ ] **Step 1: Failing test asserting first-page budget constant is 8 / packing behavior with known section costs**

- [ ] **Step 2: Change constant + fix tests**

- [ ] **Step 3: GREEN + PublicLabReportTest**

- [ ] **Step 4: Commit + Phase 8 report**

```bash
git commit -m "fix: reduce lab report first-page row budget for taller header"
```

**Stop.**

---

### Task 9: Page numbers + reviewer footer chrome

**Files:**

- Modify: `report.blade.php` — `Page N of M` on each `.report-page`; horizontal reviewer credential blocks; contact line with website; respect toggles
- Feature tests in `LabReportPrintChromeTest`

- [ ] **Step 1: Failing tests** for `Page 1 of 2` style text when builder returns 2 pages; two reviewers render two `Dr.` blocks; zero reviewers omit block

- [ ] **Step 2: Implement footer**

- [ ] **Step 3: GREEN + full lab chrome + PublicLabReport + Builder**

- [ ] **Step 4: Commit + Phase 9 report**

```bash
git commit -m "feat: add lab report page numbers and reviewer footer blocks"
```

**Stop.**

---

### Task 10: Full regression — no merge

- [ ] **Step 1: Identity/lab-focused**

```bash
php artisan test --compact tests/Feature/Lab
php artisan test --compact tests/Feature/Settings/LabReportPrintSettingsTest.php
php artisan test --compact tests/Unit/Services/LabReportQrCodeTest.php
php artisan test --compact tests/Unit/ModuleRegistryTest.php
php artisan test --compact tests/Unit/Services/SettingsSectionRegistryTest.php
```

- [ ] **Step 2: Broader**

```bash
php artisan test --compact tests/Feature/Settings
php artisan test --compact tests/Feature/Module/CheckModuleTest.php
```

- [ ] **Step 3: Fresh Vite if any Blade/JS touched** (`npm run build`) then re-run any Vite-sensitive lab UI test if present

- [ ] **Step 4: Closing report** — branch, commits Task 1–9, Q5 note, regression table, **known bugs: none**, **not merged**

Do **not** merge/push to main until user asks.

---

## Spec coverage self-review

| Spec item | Task |
|---|---|
| Legacy signed route removal | 1 |
| Reviewers pivot + verify UI | 2 (+ roster restrict in 4) |
| settings.lab-report-print + toggles | 3 |
| Roster table + Doctor Share pattern UI | 4 |
| Patient band + Q5 hospital location | 5 |
| hospital_website | 6 |
| QR bacon SVG + gate preserved | 7 |
| FIRST_PAGE_ROW_BUDGET = 8 | 8 |
| Page N of M + horizontal reviewers | 9 |
| Full regression / no merge | 10 |
| L1–L5 | Global |

## Placeholder scan

No TBD steps. Concrete constants, table names, toggle keys, and credential format locked.

## Type consistency

- `LabReportPrintSettings`, `LabReportQrCode::svg`, pivot `lab_result_reviewers`, roster `lab_report_roster_doctors` used consistently across tasks.
- Q5 hospital name/address only in Task 5 (+ website Task 6).
