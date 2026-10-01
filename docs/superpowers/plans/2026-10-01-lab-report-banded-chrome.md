# Lab Report Banded Chrome Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Restyle the lab report print chrome into the locked banded direction:
- full-width accent header band and PHC registration line (page 1)
- three-column patient strip with QR and bottom divider (page 1)
- slim running header (continuation pages)
- bottom-pinned footer band (every page)
- pale-tinted comments box
- H/L/HH/LL/A flag letters on abnormal results

All of this follows `docs/superpowers/specs/2026-10-01-lab-report-banded-chrome-design.md`, with OQ-1…OQ-8 resolved as below.

**Architecture:** All markup and CSS stay in the single print Blade (`report.blade.php`), which is shared by admin, public share, and the settings preview iframes.
- Data logic is unchanged: `LabReportBuilder` patient-band fields, `orderedReviewers()`, the `Verified By` gate, and toggles.
- Settings gain one boolean toggle (`show_phc_registration`) following the `show_hospital_*` pattern, plus a contrast threshold raise to 4.5:1 (still warn-only).
- Pagination stays logical-page based. Budgets may only go **down**, and only when the real-render verification proves it necessary.
- A committed verification script (`scripts/lab-report-print-verify.mjs`) becomes the standard real-render method. It drives Chrome over DevTools `Page.printToPDF` with `printBackground: false` (= print-dialog default) and checks **physical PDF pages == logical `.report-page` count**.

**Tech Stack:** Laravel Blade, Pest, CSS (flex/grid, `color-mix`, `print-color-adjust`), browser `window.print()`, Node 25 + Chrome DevTools Protocol (verification only).

**Spec:** `docs/superpowers/specs/2026-10-01-lab-report-banded-chrome-design.md`

## Confirmed decisions (2026-10-01)

| OQ | Decision |
|----|----------|
| OQ-1 | **P7 as written**: full band page 1 only. ~9 mm running header on pages 2…M with hospital name (+ `LAB REPORT` caption, per the P7 mockup) on the left and patient name + Patient No. on the right. **No Order #.** Footer band on every page. |
| OQ-2 | Footer band = existing contact parts (`show_footer_*`) + page number only. **No disclaimer field.** |
| OQ-3 | Contrast warning threshold **3:1 → 4.5:1**, warn-only preserved. |
| OQ-4 | **Flag letter added** to abnormal results. L1 is deliberately unlocked for this one change. Requires a dedicated test that HH/LL render distinctly from H/L, plus proof of zero column / height impact. |
| OQ-5 | `show_phc_registration` toggle, default **on**, following the `show_hospital_*` pattern exactly. |
| OQ-6 | When `show_patient_band` is off, the QR moves to the header band's right edge. |
| OQ-7 | 1 px accent divider at the **bottom** of the patient strip. |
| OQ-8 | White rounded logo tile; logo shrinks from 100 px to ~64 px. |

## Global Constraints

- **Branch:** `feat/lab-report-banded-chrome` from current `main` (`2e5f3e9` or later, which includes the Track 1 `print-color-adjust` fix). **No merge** until all tasks are done, tests are green, V1–V5 pass, and no known bugs remain.
- **TDD:** a failing test comes first for every behavior change, then red → green → commit. One commit per task minimum.
- **Unchanged data logic:** patient-strip field sources and omit-when-empty rules (`buildPatientBand`, `patient_no`, hospital-based Registration Location), reviewer roster/ordering, `primaryResult->pathologist` gate, and every existing toggle's semantics. This change is a restyle of containers only.
- **Results table locked** except for OQ-4: no column, width, padding, font, or table-color change. The `.result-abnormal { font-weight: 700; }` rule text stays byte-identical. The flag letter is a new inline `<span>` inside the existing cell.
- **Clinical text never shrinks:**
  - Font sizes for patient fields (9.5pt), table cells (9.5pt), section bars (10.5pt), reviewer blocks (9.5pt), and comments (9.5pt) are frozen. Tests assert this.
  - Chrome-only text may be sized as specified below: band contact 9pt, running header 9pt, footer band 8.5pt, PHC line 8pt.
- **Background printing:** every rule that fills with the accent or a tint carries `-webkit-print-color-adjust: exact; print-color-adjust: exact`. Track 1's generic test (`forces background printing on every accent-filled element`) covers new `background: var(--lab-report-accent)` rules automatically. Task 8 extends it to `color-mix` tints.
- **Budget rule:** if V2 fails, lower `FIRST_PAGE_ROW_BUDGET`; if V3 fails, lower `PAGE_ROW_BUDGET`. Each lowering comes with a `LabReportBuilderTest` update. **Never** raise a budget in this branch. **Never** fix overflow by shrinking clinical text or content.
- **Verification standard:**
  - Use `node scripts/lab-report-print-verify.mjs`, which prints over CDP with `printBackground: false`.
  - **Never** use the `--print-to-pdf` CLI flag as proof. It prints backgrounds on, so it does not reproduce the dialog default.
  - Every task touching `report.blade.php` re-runs V1 at minimum. The phase boundaries for Phases 2, 3, and 4 re-run V1–V5.
- **Phase boundaries:** report to the human after every phase (format in each phase's last step). Execution runs continuously within a phase.
- **Standing practice:** remove any verification worktree immediately after use (`git worktree remove`).
- **Do not** touch Prescription Print Templates / Dompdf, or the user's uncommitted `.gitignore` edit.

## File Structure

| File | Responsibility |
|------|----------------|
| `scripts/lab-report-print-verify.mjs` | **Create.** CDP print (`printBackground:false`), logical vs physical page count, print-media element measurements, footer pin check, optional grayscale |
| `tests/Feature/Lab/LabReportA4FixturesTest.php` | **Create.** Writes V1–V5 fixture HTML to `storage/app/lab-report-a4/*.html` |
| `app/Support/LabReportPrintSettings.php` | `show_phc_registration` in DEFAULTS (after `show_hospital_website`) |
| `resources/views/settings/lab-report-print/edit.blade.php` | PHC toggle label in `$headerLabels`; 4.5:1 copy |
| `app/Support/LabReportAccentContrast.php` | `MIN_RATIO = 4.5` constant; `failsMinimum()` default uses it |
| `resources/js/lab-report-accent-settings.js` | `MIN_RATIO = 4.5`; OK/warn copy |
| `resources/views/admin/lab/results/report.blade.php` | Band, PHC line, patient strip, running header, footer band, sign-off row, tint, flag letter |
| `app/Services/LabReportBuilder.php` | **Only if** V2/V3 force a budget decrease |
| `tests/Feature/Lab/LabReportBandedChromeTest.php` | **Create.** Structure/visibility/toggle tests for the new chrome |
| `tests/Feature/Lab/LabReportAbnormalFlagTest.php` | **Create.** Flag-letter tests (OQ-4) |
| `tests/Feature/Lab/LabReportAccentChromeTest.php` | Rewrite pure-color structural lock to the new structure (deliberate, listed per task) |
| `tests/Feature/Lab/LabReportPrintChromeTest.php` | Update `Phone: `/`Email: `/`Website: ` label asserts → icon markup; keep V1 write |
| `tests/Feature/Settings/LabReportPrintSettingsTest.php` | DEFAULTS equality + PHC toggle persistence; 4.5 warn |
| `tests/Unit/Support/LabReportAccentContrastTest.php` | 4.5 threshold cases |
| `tests/Feature/Lab/LabReportBuilderTest.php` | **Only if** budgets lowered |

---

## Phase 0: Branch + verification harness + baseline

### Task 0: Create feature branch

**Files:** none (git only)

- [ ] **Step 1: Branch from updated main**

```bash
cd C:/Users/Qasim/Herd/saasy
git checkout main
git pull --ff-only origin main
git checkout -b feat/lab-report-banded-chrome
git add -f docs/superpowers/plans/2026-10-01-lab-report-banded-chrome.md
git commit -m "docs: add lab report banded chrome implementation plan"
```

Expected: on `feat/lab-report-banded-chrome`. `git log` shows `e50b579` (Track 1) in history. Only ` M .gitignore` remains in `git status` (the user's, untouched).

### Task 1: Committed real-render verification script

**Files:**
- Create: `scripts/lab-report-print-verify.mjs`

**Interfaces:**
- Consumes: any report HTML file path(s)
- Produces: per file, one JSON line `{file, logical, physical, ok, footerPinned, metrics}` plus a PDF next to the HTML (`*.pdf`, or `*.gray.pdf` with `--grayscale`). Exit code 1 if any `ok:false`.

- [ ] **Step 1: Write the script**

```js
// Real-render A4 verification for the lab report print Blade.
// Prints via CDP Page.printToPDF with printBackground:false — Chrome's print-dialog default
// ("Background graphics" off). The --print-to-pdf CLI flag prints backgrounds ON and must not
// be used as proof.
//
// Usage: node scripts/lab-report-print-verify.mjs [--grayscale] [--measure] <file.html>...
import { spawn } from 'node:child_process';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { pathToFileURL } from 'node:url';

const args = process.argv.slice(2);
const grayscale = args.includes('--grayscale');
const measure = args.includes('--measure');
const files = args.filter((a) => !a.startsWith('--'));
const chrome = process.env.CHROME_PATH ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';

const profile = mkdtempSync(join(tmpdir(), 'lab-print-'));
const port = 9300 + Math.floor(Math.random() * 500);
const proc = spawn(chrome, ['--headless=new', '--disable-gpu', `--remote-debugging-port=${port}`, `--user-data-dir=${profile}`, 'about:blank']);

let targets;
for (let i = 0; i < 50 && !targets; i++) {
    try { targets = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); }
    catch { await new Promise((r) => setTimeout(r, 200)); }
}
const ws = new WebSocket(targets.find((t) => t.type === 'page').webSocketDebuggerUrl);
await new Promise((r) => ws.addEventListener('open', r));
let seq = 0; const pending = new Map(); const events = [];
ws.addEventListener('message', ({ data }) => {
    const msg = JSON.parse(data);
    if (msg.id && pending.has(msg.id)) { pending.get(msg.id)(msg); pending.delete(msg.id); } else if (msg.method) events.push(msg.method);
});
const send = (method, params = {}) => new Promise((r) => { const id = ++seq; pending.set(id, r); ws.send(JSON.stringify({ id, method, params })); });
const evaluate = async (expression) => (await send('Runtime.evaluate', { expression, returnByValue: true })).result.result.value;

await send('Page.enable');
let failed = false;
for (const file of files) {
    events.length = 0;
    await send('Page.navigate', { url: pathToFileURL(resolve(file)).href });
    for (let i = 0; i < 100 && !events.includes('Page.loadEventFired'); i++) await new Promise((r) => setTimeout(r, 100));
    if (grayscale) await evaluate(`document.documentElement.style.filter = 'grayscale(100%)'`);

    await send('Emulation.setEmulatedMedia', { media: 'print' });
    const logical = await evaluate(`document.querySelectorAll('.report-page').length`);
    const footerPinned = await evaluate(`[...document.querySelectorAll('.report-page')].every((p) => {
        const f = p.querySelector('.report-footer-band'); if (!f) return true;
        return Math.abs(p.getBoundingClientRect().bottom - f.getBoundingClientRect().bottom) < 2;
    })`);
    const metrics = measure ? await evaluate(`(() => {
        const mm = (px) => +(px / 96 * 25.4).toFixed(1);
        const out = {};
        for (const sel of ['.report-reg-line', '.report-band', '.patient-strip', '.running-header', '.test-panel', '.comments-box', '.report-signoff', '.report-footer-band', '.report-page']) {
            out[sel] = [...document.querySelectorAll(sel)].map((e) => mm(e.getBoundingClientRect().height));
        }
        out['td.result-abnormal'] = [...document.querySelectorAll('td.result-abnormal')].map((e) => mm(e.parentElement.getBoundingClientRect().height));
        return out;
    })()`) : undefined;
    await send('Emulation.setEmulatedMedia', { media: '' });

    const pdf = await send('Page.printToPDF', { printBackground: false, preferCSSPageSize: true });
    const buffer = Buffer.from(pdf.result.data, 'base64');
    writeFileSync(file.replace(/\.html$/, grayscale ? '.gray.pdf' : '.pdf'), buffer);
    const physical = (buffer.toString('latin1').match(/\/Type\s*\/Page(?![s\w])/g) ?? []).length;
    const ok = physical === logical && footerPinned;
    failed ||= !ok;
    console.log(JSON.stringify({ file, logical, physical, footerPinned, ok, metrics }));
}
ws.close(); proc.kill();
try { rmSync(profile, { recursive: true, force: true }); } catch {}
process.exit(failed ? 1 : 0);
```

- [ ] **Step 2: Prove the script detects the Track 1 bug class (self-test)**
  - `git stash` is not allowed (the user's `.gitignore` edit is present). Instead, copy `storage/app/lab-report-a4-budget-check.html` to the session scratchpad.
  - Delete the two `print-color-adjust` lines from the copy, run the script on it, and open the PDF. Expected: section bar names faint gray (bug visible).
  - Run on the unmodified copy. Expected: bars filled, names white.
  - This proves the harness reproduces dialog defaults.

- [ ] **Step 3: Run on V1 as-is**

```bash
vendor/bin/pest tests/Feature/Lab/LabReportPrintChromeTest.php --no-coverage
node scripts/lab-report-print-verify.mjs --measure storage/app/lab-report-a4-budget-check.html
```

Expected: `{"logical":1,"physical":1,"ok":true,...}`. `footerPinned` is vacuously true (no footer band yet).

- [ ] **Step 4: Commit**

```bash
git add scripts/lab-report-print-verify.mjs
git commit -m "chore: add CDP backgrounds-off lab report A4 verification script"
```

### Task 2: V1–V5 fixture writers + baseline measurement

**Files:**
- Create: `tests/Feature/Lab/LabReportA4FixturesTest.php`

**Interfaces:**
- Consumes: the same model-creation pattern as `LabReportPrintChromeTest` (local helpers; no drive-by refactor of the existing file)
- Produces:
  - `storage/app/lab-report-a4/v1-baseline.html`
  - `v2-page1-worst.html`
  - `v3-multipage.html`
  - `v4-toggles-off.html`
  - `v5-default-accent.html` and `v5-borderline-accent.html`

The directory is created with `mkdir(..., recursive: true)` if missing. The existing `storage/app/lab-report-a4-budget-check.html` write in `LabReportPrintChromeTest` stays as is (it is V1's legacy path). V1 here writes the same scenario to the new directory.

- [ ] **Step 1: Write the fixture tests**

Each fixture is an `it(...)` that builds data, GETs `route('investigation-orders.report', $order)`, asserts `->assertOk()`, asserts the **logical page count** it expects (`substr_count($html, 'class="report-page"')`), and writes the file. The scenarios:

| Fixture | Data | Logical pages asserted |
|---|---|---|
| **V1** | Existing CBC + Alpha + Beta (three cost-4 sections = 12) | 1 |
| **V2** | Everything on at once: `phc_registration_number = 'PHC-R-0123456789'`; address of 140 chars; phone/email/website set; all header toggles on; all 4 `show_footer_*` on; `clinical_notes` > 120 chars (band truncates); visit present (Registration Date); 2 roster reviewers synced; `comments` of ~300 chars; `previous_values_count = 3` with priors; sections totalling exactly 12 units, including one abnormal `HH` value of 8 chars (`'1234.567'`) and one `A`-flag text value `'Positive (1:320)'` (16 chars) | 1 |
| **V3** | Page 1: three cost-4 sections (12). Page 2: one section with 27 params (2+27+1 = 30). Page 3: one 27-param section (30) + comments (~300 chars) + 2 reviewers + `Verified By`. All footer contact toggles on. | 3 |
| **V4** | V2 data, but every boolean toggle in `LabReportPrintSettings::DEFAULTS` set false (`show_patient_band` off, `show_qr` **on**, so OQ-6 relocation is exercised); a second variant `v4-all-off.html` also with `show_qr` off | 1 each |
| **V5** | V2 data with `accent_color` `#0F766E` (5.47:1), and again with `#30827C` (4.56:1, just above 4.5). The test asserts `LabReportAccentContrast::ratioAgainstWhite('#30827C')` is between 4.5 and 4.7. | 1 each |

- [ ] **Step 2: Run tests**

```bash
vendor/bin/pest tests/Feature/Lab/LabReportA4FixturesTest.php --no-coverage
```

Expected: PASS (they assert today's logical packing; V2's sections are chosen to equal 12 under today's budget).

- [ ] **Step 3: Baseline real render (pre-change, recorded, not fixed)**

```bash
node scripts/lab-report-print-verify.mjs --measure storage/app/lab-report-a4/*.html
node scripts/lab-report-print-verify.mjs --grayscale storage/app/lab-report-a4/v5-*.html
```

Record every JSON line in the Phase 0 report as the **before** table.
- If any baseline fixture already shows `physical > logical` on current main (V2 and V3 are harsher than anything verified before), record it as a **pre-existing** finding. Do not fix it in Phase 0.
- It then becomes an explicit input to Task 10's budget decision.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Lab/LabReportA4FixturesTest.php
git commit -m "test: add V1-V5 lab report A4 fixture writers"
```

- [ ] **Step 5: Phase 0 report**

Report: branch + HEAD SHA, self-test result (bug reproduced / fixed), and the baseline JSON table for V1–V5 (logical vs physical pages + element heights), flagging any pre-existing overflow.

---

## Phase 1: Settings (PHC toggle, 4.5:1 contrast)

### Task 3: `show_phc_registration` toggle

**Files:**
- Modify: `app/Support/LabReportPrintSettings.php`
- Modify: `resources/views/settings/lab-report-print/edit.blade.php`
- Modify: `tests/Feature/Settings/LabReportPrintSettingsTest.php`

**Interfaces:**
- Produces: `LabReportPrintSettings::get()['show_phc_registration']` (bool, default `true`). Saved JSON lacking the key → `true`, so **existing tenants get it on**. Saving the form unchecked → `false`. The FormRequest is generic over DEFAULTS, so it needs no change.

- [ ] **Step 1: Failing tests**

```php
it('defaults show_phc_registration on, including for tenants whose saved JSON predates it', function () {
    expect(LabReportPrintSettings::get()['show_phc_registration'])->toBeTrue();

    Setting::set('lab_report_print', json_encode(['show_logo' => true]));
    expect(LabReportPrintSettings::get()['show_phc_registration'])->toBeTrue();
});

it('persists show_phc_registration off from the settings form', function () {
    $this->withoutMiddleware([\App\Http\Middleware\CheckModule::class]);
    $this->actingAs(labReportPrintUser(['access settings.lab-report-print']));

    $this->put(route('settings.lab-report-print.update'), [
        ...labReportPrintFormPayload(),          // existing helper or inline payload used by sibling tests
        'show_phc_registration' => '0',
    ])->assertRedirect();

    expect(LabReportPrintSettings::get()['show_phc_registration'])->toBeFalse();
});

it('lists the PHC toggle among header toggles on the settings page', function () {
    // GET settings.lab-report-print.edit → assertSee('name="show_phc_registration"', false)
    //   ->assertSee('Show PHC registration number (header)')
});
```

Also update the existing exact-DEFAULTS equality test to include `'show_phc_registration' => true` immediately after `show_hospital_website`. If the sibling tests inline the payload rather than using a helper, inline it the same way (no new helper).

- [ ] **Step 2: Run (red)**: `vendor/bin/pest tests/Feature/Settings/LabReportPrintSettingsTest.php --no-coverage`

- [ ] **Step 3: Implement.** Add `'show_phc_registration' => true,` after `'show_hospital_website' => true,` in `DEFAULTS`. Add `'show_phc_registration' => 'Show PHC registration number (header)',` after the website entry in `$headerLabels`.

- [ ] **Step 4: Green + commit**: `feat: add show_phc_registration lab report print toggle`

### Task 4: Contrast warning 3:1 → 4.5:1 (warn-only)

**Files:**
- Modify: `app/Support/LabReportAccentContrast.php`
- Modify: `resources/js/lab-report-accent-settings.js`
- Modify: `resources/views/settings/lab-report-print/edit.blade.php`
- Modify: `tests/Unit/Support/LabReportAccentContrastTest.php`
- Modify: `tests/Feature/Settings/LabReportPrintSettingsTest.php`

**Interfaces:**
- Produces: `LabReportAccentContrast::MIN_RATIO = 4.5`; `failsMinimum(string $hex, float $min = self::MIN_RATIO)`

- [ ] **Step 1: Failing tests**

```php
it('uses 4.5:1 as the minimum because white text sits on the accent', function () {
    expect(LabReportAccentContrast::MIN_RATIO)->toBe(4.5)
        ->and(LabReportAccentContrast::failsMinimum('#33847E'))->toBeTrue()   // 4.43
        ->and(LabReportAccentContrast::failsMinimum('#30827C'))->toBeFalse()  // 4.56
        ->and(LabReportAccentContrast::failsMinimum('#0F766E'))->toBeFalse(); // 5.47
});
```

Feature tests:
- The settings page with saved `#33847E` (between 3 and 4.5) shows the amber warn banner. That accent previously showed OK.
- Save with `#33847E` still succeeds (warn-only).
- Rename the existing `still saves when accent_color contrast is below 3:1` test to `…below 4.5:1`, with an unchanged assertion that Save is allowed.
- The OK copy asserts the new text.

- [ ] **Step 2: Red**

- [ ] **Step 3: Implement.**
  - PHP constant and default.
  - JS `const MIN_RATIO = 4.5;`.
  - OK copy (Blade + JS): `Contrast vs white: {ratio}:1 — OK for white header/footer text (needs 4.5:1).`
  - Warn copy (Blade + JS): `Contrast vs white: {ratio}:1 — below 4.5:1, so white header, footer and section-bar text may be hard to read, especially in black & white. Consider a darker color. You can still save.`
  - Run `npm run build` so `scripts/check-vite-build-freshness.mjs` stays green.

- [ ] **Step 4: Green + commit**: `feat: raise lab report accent contrast warning to 4.5:1`

- [ ] **Step 5: Phase 1 report**: test counts and the two commit SHAs. Screenshot or description of the warn banner for `#33847E`.

---

## Phase 2: Page-1 chrome (band, PHC line, patient strip)

### Task 5: Header band + PHC line + logo tile + contact icons

**Files:**
- Modify: `resources/views/admin/lab/results/report.blade.php`
- Create: `tests/Feature/Lab/LabReportBandedChromeTest.php` (fixtures copied from `LabReportAccentChromeTest` `beforeEach`)
- Modify: `tests/Feature/Lab/LabReportAccentChromeTest.php` (deliberate structural-lock rewrite, below)
- Modify: `tests/Feature/Lab/LabReportPrintChromeTest.php` (`Phone: `/`Email: `/`Website: ` asserts)

**Interfaces:**
- Consumes: `$settings`, `$printToggles`, `setting('phc_registration_number')`, `$qrSvg`
- Produces (page 1 only, `$pageIndex === 0`):
  - `.report-reg-line` (when PHC filled AND toggle on)
  - `.report-band` › `.report-band-brand` (`.report-logo-tile` + name/address/caption) + `.report-band-contact` (icon lines) + optional `.report-band-qr`
  - `.header` and `.report-title` are removed

- [ ] **Step 1: Failing tests** (in `LabReportBandedChromeTest`)

```php
it('renders a full-width accent header band with white text on page 1 only', function () {
    // add 2 extra panels to force page 2 (reuse chromeLabTestWithResult-style local helper)
    $html = /* GET report */;
    expect(substr_count($html, 'class="report-band"'))->toBe(1)
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*background:\s*var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*color:\s*#fff/s')
        ->and($html)->toMatch('/\.report-band\s*\{[^}]*width:\s*100%/s')
        ->and($html)->not->toContain('class="header')
        ->and($html)->toContain('LAB REPORT');
});

it('puts the logo on a white rounded tile no larger than 64px', function () {
    // Setting::set('hospital_logo', 'logos/x.png')
    expect($html)->toContain('class="report-logo-tile"')
        ->and($html)->toMatch('/\.report-logo-tile\s*\{[^}]*background:\s*#fff/s')
        ->and($html)->toMatch('/\.report-logo-tile\s*\{[^}]*border-radius:/s')
        ->and($html)->toMatch('/\.report-logo-tile img\s*\{[^}]*width:\s*64px[^}]*height:\s*64px/s');
});

it('shows phone, email and website as icon lines in the band, each behind its header toggle', function () {
    // set all three; then toggle show_hospital_email off
    expect($html)->toContain('class="report-band-contact"')
        ->and($html)->toContain('555-0199')
        ->and($html)->toContain('<svg class="band-icon"')
        ->and($html)->not->toContain('header@chrome.test'); // email toggled off
});

it('prints the PHC registration number above the band only when filled and toggled on', function () {
    Setting::set('phc_registration_number', 'PHC-R-42');
    $on = /* GET */;
    expect($on)->toContain('class="report-reg-line"')->and($on)->toContain('PHC Reg. No. PHC-R-42');
    // the reg line precedes the band in source order
    expect(strpos($on, 'report-reg-line'))->toBeLessThan(strpos($on, 'class="report-band"'));

    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_phc_registration' => false]);
    expect(/* GET */)->not->toContain('report-reg-line');

    LabReportPrintSettings::put(LabReportPrintSettings::DEFAULTS);
    Setting::set('phc_registration_number', null);
    expect(/* GET */)->not->toContain('report-reg-line');
});

it('keeps the address in the band behind show_hospital_address', function () { /* on: sees 11 Accent Avenue inside band; off: absent */ });
```

**Deliberate test rewrites** (do these in this task, not silently):
- `LabReportAccentChromeTest` › `emits --lab-report-accent…`: replace the `.header` border-bottom assert with the `.report-band` background assert.
- `LabReportAccentChromeTest` › `keeps structural size metrics identical…`: rename it to `locks clinical text sizes and table metrics`. Drop the `.header`/`.patient-box` metric asserts. Keep the table, section-bar, comments, reviewer, and signature asserts. Add `.patient-strip .patient-item` 9.5pt (Task 6 makes this pass; mark that line in Task 6).
- `LabReportPrintChromeTest`: `assertSee('Phone: 555-0199')` → `assertSee('555-0199')` inside `report-band-contact`; same for Email and Website.

- [ ] **Step 2: Red**

- [ ] **Step 3: Implement**

CSS (replaces `.header`, `.header-no-qr`, `.logo`, `.logo img`, `.hospital-header`, `.hospital-name`, `.hospital-address`, `.report-title`; `.report-qr` is kept for reuse):

```css
.report-reg-line { text-align: right; font-size: 8pt; color: #555; margin-bottom: 1.5mm; }

.report-band {
    display: flex; justify-content: space-between; align-items: center; gap: 16px;
    width: 100%;
    background: var(--lab-report-accent);
    color: #fff;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    padding: 4mm 5mm;
}
.report-band-brand { display: flex; align-items: center; gap: 12px; min-width: 0; }
.report-logo-tile {
    background: #fff; border-radius: 6px; padding: 4px; flex: none;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
}
.report-logo-tile img { width: 64px; height: 64px; object-fit: contain; display: block; }
.band-hospital-name { font-size: 18pt; font-weight: bold; line-height: 1.15; }
.band-hospital-address { font-size: 9pt; margin-top: 2px; }
.band-report-caption { font-size: 9pt; font-weight: 700; letter-spacing: 0.12em; margin-top: 3px; }
.report-band-contact { font-size: 9pt; text-align: right; flex: none; }
.report-band-contact div { display: flex; align-items: center; justify-content: flex-end; gap: 5px; }
.band-icon { width: 3.5mm; height: 3.5mm; fill: currentColor; flex: none; }
.report-band-qr { background: #fff; padding: 3px; border-radius: 4px; flex: none;
    -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.report-band-qr svg { width: 72px; height: 72px; display: block; }
```

Markup (page 1):

```blade
@php $phcNumber = trim((string) setting('phc_registration_number', '')); @endphp
@if(($printToggles['show_phc_registration'] ?? true) && $phcNumber !== '')
    <div class="report-reg-line">PHC Reg. No. {{ $phcNumber }}</div>
@endif
<header class="report-band">
    <div class="report-band-brand">
        @if(($printToggles['show_logo'] ?? true) && $settings['hospital_logo'])
            <div class="report-logo-tile"><img src="{{ asset('storage/' . $settings['hospital_logo']) }}" alt="Hospital Logo"></div>
        @endif
        <div>
            <div class="band-hospital-name">{{ $settings['hospital_name'] }}</div>
            @if(($printToggles['show_hospital_address'] ?? true) && $settings['hospital_address'])
                <div class="band-hospital-address">{{ $settings['hospital_address'] }}</div>
            @endif
            <div class="band-report-caption">LAB REPORT</div>
        </div>
    </div>
    {{-- contact lines: phone / email / website, each `@if(toggle && filled)` with an inline <svg class="band-icon" viewBox="0 0 24 24" aria-hidden="true"> path --}}
    {{-- OQ-6: QR here only when the patient strip is hidden (Task 6 wires the condition; this task renders it when show_patient_band is off) --}}
    @if($qrSvg && ! ($printToggles['show_patient_band'] ?? true))
        <div class="report-band-qr report-qr" data-qr-url="{{ $shareUrl }}">{!! $qrSvg !!}</div>
    @endif
</header>
```

The three icons are simple single-path 24×24 SVGs: phone handset, envelope, and globe. Inline them, with no icon font and no external asset. The `.report-contact` footer class is **not** touched here.

- [ ] **Step 4: Green.** Run `LabReportBandedChromeTest`, `LabReportAccentChromeTest` (incl. the Track 1 `forces background printing…` test, which must now also match `.report-band`), `LabReportPrintChromeTest`, `PublicLabReportTest`, `LabReportAccentPreviewTest`.

- [ ] **Step 5: Real render V1.** Run `LabReportA4FixturesTest` then `node scripts/lab-report-print-verify.mjs --measure storage/app/lab-report-a4/v1-baseline.html`. Expect `ok:true`. Open the PDF: band filled, white text legible with backgrounds off.

- [ ] **Step 6: Commit**: `feat: lab report accent header band with PHC line and logo tile`

### Task 6: Three-column patient strip + QR relocation + bottom divider

**Files:**
- Modify: `resources/views/admin/lab/results/report.blade.php`
- Modify: `tests/Feature/Lab/LabReportBandedChromeTest.php`
- Re-run: `tests/Feature/Lab/LabReportVisitTypeIdLineTest.php`, `LabReportPrintChromeTest`, `PublicLabReportTest`

**Interfaces:**
- Consumes: `$patientBand` (unchanged keys), `$order`, `$primaryResult`, `$qrSvg`
- Produces: `.patient-strip` (grid `1fr 1fr 26mm`, or `1fr 1fr` without QR) › `.patient-strip-col` ×2 + `.patient-strip-qr`, `.patient-strip-note` spanning cols 1–2, and `border-bottom: 1px solid var(--lab-report-accent)`. `.patient-box`/`.patient-grid` are removed.

Column mapping (field sources byte-identical to today):

| Left | Middle | Right |
|---|---|---|
| Patient Name · Age / Sex · Referred By · Patient No. · Department* · Consultant* | Registration Location · Registration Date* · Collection · Reporting | QR |

Note\* sits on a full-width row under the left and middle columns. \* = omit when empty, as today.

- [ ] **Step 1: Failing tests**

```php
it('renders the patient strip in three columns with every confirmed field', function () {
    $html = /* GET (fixture has visit, notes, reviewer → all optional fields present) */;
    $left = between($html, 'patient-strip-col patient-strip-left', '</div><!-- /left -->');
    $middle = between($html, 'patient-strip-col patient-strip-middle', '</div><!-- /middle -->');

    foreach (['Patient Name:', 'Age / Sex:', 'Referred By:', 'Patient No.:', 'Department:', 'Consultant:'] as $label) {
        expect($left)->toContain($label);
    }
    foreach (['Registration Location:', 'Registration Date:', 'Collection:', 'Reporting:'] as $label) {
        expect($middle)->toContain($label);
    }
    expect($html)->toContain('class="patient-strip-note"')
        ->and($html)->toContain($this->patient->fresh()->patient_no)
        ->and($html)->toContain('Accent City Hospital, 11 Accent Avenue') // hospital-based Registration Location unchanged
        ->and($html)->not->toContain('class="patient-box"');
});

it('places the QR in the strip right column and not in the band when the strip is shown', function () { /* .patient-strip-qr present, .report-band-qr absent, exactly one data-qr-url */ });

it('relocates the QR to the band right edge when the patient band is off (OQ-6)', function () {
    LabReportPrintSettings::put([...LabReportPrintSettings::DEFAULTS, 'show_patient_band' => false]);
    // .patient-strip absent; .report-band-qr present inside the band; exactly one data-qr-url
});

it('omits the QR everywhere when show_qr is off', function () { /* no data-qr-url; strip grid has 2 columns class */ });

it('draws a 1px accent divider at the bottom of the patient strip (OQ-7)', function () {
    expect($html)->toMatch('/\.patient-strip\s*\{[^}]*border-bottom:\s*1px solid var\(--lab-report-accent\)/s');
});

it('omits empty optional fields exactly as the old band did', function () { /* no visit, no notes, no reviewers → no Registration Date / Note / Consultant labels */ });
```

`between()` is a local test helper using `strpos` slicing. HTML comments `<!-- /left -->` and `<!-- /middle -->` are added to the markup for test anchoring.

- [ ] **Step 2: Red**

- [ ] **Step 3: Implement**

```css
.patient-strip {
    display: grid; grid-template-columns: 1fr 1fr 26mm; gap: 2px 14px;
    padding: 3mm 0 2.5mm; margin-bottom: 4mm;
    border-bottom: 1px solid var(--lab-report-accent);
}
.patient-strip.no-qr { grid-template-columns: 1fr 1fr; }
.patient-strip-col { display: flex; flex-direction: column; gap: 3px; min-width: 0; }
.patient-strip-qr { grid-row: span 2; display: flex; justify-content: flex-end; }
.patient-strip-qr svg { width: 90px; height: 90px; display: block; }
.patient-strip-note { grid-column: 1 / 3; }
.patient-item { font-size: 9.5pt; }
.patient-label { font-weight: 700; display: inline-block; min-width: 27mm; }
```

The markup moves the existing `@if` blocks verbatim into the two columns (no expression changes). Wrap it in `@if($printToggles['show_patient_band'] ?? true)` as today.

- [ ] **Step 4: Green** (+ the Task 5 marked `.patient-strip .patient-item` 9.5pt lock assert now passes)

- [ ] **Step 5: Phase 2 real render: V1–V5**

```bash
vendor/bin/pest tests/Feature/Lab/LabReportA4FixturesTest.php --no-coverage
node scripts/lab-report-print-verify.mjs --measure storage/app/lab-report-a4/*.html
node scripts/lab-report-print-verify.mjs --grayscale storage/app/lab-report-a4/v5-*.html
```

Expected: all `ok:true`. Compare page-1 chrome (reg line + band + strip) with Phase 0's header + patient box. Expect it to be shorter, per spec P9 (~81–87 mm vs ~97 mm incl. footer).
- If V2 now overflows, **stop**. Go to the Task 10 budget procedure early, and report it.

- [ ] **Step 6: Commit**: `feat: three-column lab report patient strip with QR and divider`

- [ ] **Step 7: Phase 2 report.** Include:
  - SHAs
  - the before/after per-element height table (Phase 0 vs now)
  - V1–V5 logical/physical table
  - backgrounds-off PDF observations (band, white text, logo tile, icons)
  - grayscale observations for both V5 accents

---

## Phase 3: Continuation + footer chrome

### Task 7: Slim running header on continuation pages (OQ-1)

**Files:**
- Modify: `resources/views/admin/lab/results/report.blade.php`
- Modify: `tests/Feature/Lab/LabReportBandedChromeTest.php`

**Interfaces:**
- Produces: on every `.report-page` with `$pageIndex > 0`, a `.running-header` holding:
  - left: hospital name (bold) · `LAB REPORT`
  - right: patient name · `Patient No. {patient_no}`
  - **No Order #**, no logo/QR/contact.
- The running header is unconditional on continuation pages: patient identification is not toggleable.

- [ ] **Step 1: Failing tests**

```php
it('shows a slim running header with patient identification on every continuation page only', function () {
    // build 3 logical pages (reuse V3-style data in a local helper)
    $html = /* GET */;
    expect(substr_count($html, 'class="running-header"'))->toBe(2)  // pages 2 and 3
        ->and(substr_count($html, 'class="report-band"'))->toBe(1);
    $running = between($html, 'class="running-header"', '</div><!-- /running-header -->');
    expect($running)->toContain('Chrome City Hospital')
        ->and($running)->toContain('Chrome Patient')
        ->and($running)->toContain('Patient No. '.$this->patient->fresh()->patient_no)
        ->and($running)->not->toContain($this->order->order_number);
});

it('does not render a running header on a single-page report', function () { /* count 0 */ });

it('styles the running header as an accent fill with forced background printing', function () {
    expect($html)->toMatch('/\.running-header\s*\{[^}]*background:\s*var\(--lab-report-accent\)/s');
    // Track 1 generic test also enforces print-color-adjust on it
});
```

- [ ] **Step 2: Red**

- [ ] **Step 3: Implement**

```css
.running-header {
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    background: var(--lab-report-accent); color: #fff;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
    padding: 2mm 4mm; margin-bottom: 4mm; font-size: 9pt;
}
.running-header strong { font-weight: 700; }
```

```blade
@if($pageIndex > 0)
    <div class="running-header">
        <div><strong>{{ $settings['hospital_name'] }}</strong> · LAB REPORT</div>
        <div>{{ $order->patient->name }} · Patient No. {{ $order->patient->patient_no }}</div>
    </div><!-- /running-header -->
@endif
```

- [ ] **Step 4: Green + V3 real render.** Expect `ok:true`. Measure `.running-header` and confirm it is ≤ 10 mm.

- [ ] **Step 5: Commit**: `feat: slim accent running header on lab report continuation pages`

### Task 8: Footer band (every page, bottom-pinned) + sign-off row + comments tint

**Files:**
- Modify: `resources/views/admin/lab/results/report.blade.php`
- Modify: `tests/Feature/Lab/LabReportBandedChromeTest.php`
- Modify: `tests/Feature/Lab/LabReportAccentChromeTest.php` (extend Track 1 generic test to tints)
- Re-run: `LabReportPrintChromeTest` (page N of M counts, reviewer blocks, contact toggles), `LabReportPreviousValuesTest`, `LabResultReviewerTest`

**Interfaces:**
- Produces:
  - `.report-page` becomes a flex column with print `min-height: 276mm`.
  - `.report-footer-band` on **every** page (`margin-top: auto`) holds `.report-contact` (left, same parts/toggles/separator) and `.page-number` (right, same text/toggle).
  - It always renders, as a thin bookend (~3 mm) when both sides are empty.
  - On the last page, `.report-signoff` wraps `.reviewer-blocks` (left) + `.signatures` (right), directly above the band.
  - `.comments-box` gets a 7% accent tint.

- [ ] **Step 1: Failing tests**

```php
it('renders the footer band on every page with page numbers inside it', function () {
    // 2-page report
    expect(substr_count($html, 'class="report-footer-band"'))->toBe(2)
        ->and(substr_count($html, 'Page 1 of 2'))->toBe(1)
        ->and(substr_count($html, 'Page 2 of 2'))->toBe(1);
    // each page-number sits inside a footer band
    expect($html)->toMatch('/class="report-footer-band".*?Page 1 of 2/s');
});

it('puts enabled footer contact parts in every page band and nothing when all are off', function () {
    // on: substr_count('class="report-contact"') === page count; joined with ' · '
    // off (defaults): no report-contact, band still present
});

it('still renders a thin bookend band when page numbers and contact are both off', function () {
    // show_page_numbers false + defaults → report-footer-band present, no page-number, no report-contact
});

it('bottom-pins the footer band with a flex column page and margin-top auto', function () {
    expect($html)->toMatch('/\.report-footer-band\s*\{[^}]*margin-top:\s*auto/s')
        ->and($html)->toMatch('/@media print\s*\{.*?\.report-page\s*\{[^}]*min-height:\s*276mm/s')
        ->and($html)->toMatch('/\.report-page\s*\{[^}]*display:\s*flex[^}]*flex-direction:\s*column/s');
});

it('keeps reviewers and Verified By in one sign-off row above the band on the last page only', function () {
    // 2-page report with reviewers + pathologist
    expect(substr_count($html, 'class="report-signoff"'))->toBe(1)
        ->and(strpos($html, 'class="report-signoff"'))->toBeLessThan(strrpos($html, 'class="report-footer-band"'))
        ->and(strpos($html, 'class="report-signoff"'))->toBeGreaterThan(strpos($html, 'Page 1 of 2'));
    // data unchanged: same 'Dr. Dr Review Chrome', 'FCPS', 'Verified By', pathologist name
});

it('tints the comments box with a pale accent mix and keeps its accent border', function () {
    expect($html)->toMatch('/\.comments-box\s*\{[^}]*background:\s*color-mix\(in srgb, var\(--lab-report-accent\) 7%, #fff\)/s')
        ->and($html)->toMatch('/\.comments-box\s*\{[^}]*border:\s*1px solid var\(--lab-report-accent\)/s')
        ->and($html)->toMatch('/\.comments-box\s*\{[^}]*padding:\s*8px 10px/s')
        ->and($html)->toMatch('/\.comments-box\s*\{[^}]*font-size:\s*9\.5pt/s');
});
```

Extend Track 1's generic test regex in `LabReportAccentChromeTest` so it matches any rule whose `background` contains `var(--lab-report-accent)` (covers `color-mix(...)`). Every such rule must carry both `print-color-adjust` declarations.

- [ ] **Step 2: Red**

- [ ] **Step 3: Implement**

```css
.report-page { display: flex; flex-direction: column; }            /* screen keeps min-height 277mm + padding */
.report-footer-band {
    margin-top: auto;
    display: flex; justify-content: space-between; align-items: center; gap: 12px;
    min-height: 3mm; padding: 1.5mm 4mm;
    background: var(--lab-report-accent); color: #fff; font-size: 8.5pt;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
}
.report-footer-band .report-contact { margin: 0; text-align: left; color: inherit; font-size: inherit; }
.report-footer-band .page-number { margin: 0; color: inherit; font-size: inherit; white-space: nowrap; }
.report-signoff { display: flex; justify-content: space-between; align-items: flex-end; gap: 20px;
    margin-top: 14px; margin-bottom: 4mm; break-inside: avoid; page-break-inside: avoid; }
.report-signoff .reviewer-blocks { margin-top: 0; }
.report-signoff .signatures { margin: 0 0 0 auto; }

.comments-box {
    background: #f7f9f9;
    background: color-mix(in srgb, var(--lab-report-accent) 7%, #fff);
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
    /* existing border / padding / font-size unchanged */
}

@media print {
    .report-page { min-height: 276mm; /* existing: width auto, height auto, margin 0, padding 0, break-after */ }
}
```

Markup:
- At the end of every `.report-page`, **replace** the separate `.report-contact` (last page) and `.page-number` blocks with `<footer class="report-footer-band">` containing them.
- The contact parts render on every page. The `$contactParts` computation is unchanged.
- The `@if($loop->last)` block keeps comments, then wraps the existing reviewer `@if` and signature `@if` blocks **verbatim** in `<div class="report-signoff">`. The wrapper renders only if at least one of them renders, to avoid an empty margin.

- [ ] **Step 4: Green.** Full `tests/Feature/Lab` + `tests/Feature/Settings/LabReportAccentPreviewTest.php`.

- [ ] **Step 5: Phase 3 real render: V1–V5** (same commands as Task 6 Step 5).
  - Expected: `logical == physical` **and** `footerPinned:true` for every fixture, and no trailing blank page.
  - Backgrounds-off PDFs: footer band filled, page number and contact legible.
  - Grayscale: comments tint visible but quiet, text unaffected.
  - Any `ok:false` → Task 10 budget procedure. Do not touch CSS sizes to make it fit.

- [ ] **Step 6: Commit**: `feat: bottom-pinned lab report footer band, sign-off row, tinted comments`

- [ ] **Step 7: Phase 3 report.** Include SHAs, the V1–V5 table including `footerPinned`, the running header and footer band measured heights, the last-page sign-off layout description, and grayscale tint observations.

---

## Phase 4: Abnormal flag letter (OQ-4, deliberate L1 unlock)

### Task 9: Print H / L / HH / LL / A beside abnormal values

**Files:**
- Modify: `resources/views/admin/lab/results/report.blade.php`
- Create: `tests/Feature/Lab/LabReportAbnormalFlagTest.php`
- Modify: `tests/Feature/Lab/LabReportAccentChromeTest.php` (lock: `.result-abnormal { font-weight: 700; }` unchanged; th widths unchanged)

**Interfaces:**
- Consumes: `LabResultItem.flag` (`N`, `H`, `L`, `HH`, `LL`, `A`, null) and the existing `$isAbnormal` (`flag && flag !== 'N'`)
- Produces: inside the existing result `<td class="result-abnormal">`, `{{ value }}&nbsp;<span class="result-flag">{{ flag }}</span>`. The printed letter is the stored flag, so **HH/LL print as `HH`/`LL`, distinct from `H`/`L`**.
- `A` prints as `A`. `isAbnormal()` treats it as abnormal, and the report already bolds it.
- Previous-value rows (`tr.previous-result`) are **unchanged**: OQ-4 scoped the change to `.result-abnormal`, and prior rows keep `.result-abnormal-muted`.

- [ ] **Step 1: Failing tests**

```php
dataset('abnormal flags', ['H', 'L', 'HH', 'LL', 'A']);

it('prints the flag letter after an abnormal value', function (string $flag) {
    $html = /* report with one item value '7.9', flag $flag */;
    expect($html)->toContain('<td class="result-abnormal">7.9&nbsp;<span class="result-flag">'.$flag.'</span></td>');
})->with('abnormal flags');

it('renders critical HH/LL distinctly from H/L', function () {
    // one report with four parameters: H, HH, L, LL
    preg_match_all('/<span class="result-flag">([A-Z]+)<\/span>/', $html, $m);
    expect($m[1])->toEqualCanonicalizing(['H', 'HH', 'L', 'LL'])
        ->and(array_unique($m[1]))->toHaveCount(4);
    // and the critical cells are not byte-identical to their non-critical counterparts
    expect($html)->toContain('<span class="result-flag">HH</span>')
        ->and($html)->toContain('<span class="result-flag">H</span>');
});

it('prints no flag for normal or unflagged values', function () {
    // flags 'N' and null → no result-flag span, no result-abnormal class
});

it('leaves previous-value rows without flag letters', function () { /* prior with flag H → no result-flag inside tr.previous-result */ });

it('does not change result table columns, widths, padding or the abnormal rule', function () {
    expect($html)->toContain('.result-abnormal { font-weight: 700; }')
        ->and($html)->toContain('<th style="width: 36%;">Parameter</th>')
        ->and($html)->toContain('<th style="width: 18%;">Result</th>')
        ->and($html)->toContain('<th style="width: 14%;">Unit</th>')
        ->and($html)->toContain('<th style="width: 32%;">Reference Range</th>')
        ->and(substr_count(between($html, '<thead>', '</thead>'), '<th'))->toBe(4)
        ->and($html)->toMatch('/\.result-flag\s*\{[^}]*white-space:\s*nowrap/s')
        ->and($html)->not->toMatch('/\.result-flag\s*\{[^}]*(font-size|padding|margin|display)/s');
});

it('does not change section row costs', function () {
    // LabReportBuilder::makeSection row_cost for a section with abnormal items equals the same section with all 'N'
});
```

- [ ] **Step 2: Red**

- [ ] **Step 3: Implement**

```css
.result-flag { white-space: nowrap; }
```

```blade
<td class="{{ $isAbnormal ? 'result-abnormal' : '' }}">{{ $item->value }}@if($isAbnormal)&nbsp;<span class="result-flag">{{ $item->flag }}</span>@endif</td>
```

The span inherits bold from `.result-abnormal`. It has no colour (stays B&W-safe) and no size, padding, or display change.

- [ ] **Step 4: Green**

- [ ] **Step 5: Zero-height proof (real render).**
  - Run `--measure` on V2, which contains `1234.567` + `HH` and the 16-char `A` text value `Positive (1:320)`.
  - Then re-generate V2 with all flags forced to `N` (add a `v2-no-flags.html` variant to `LabReportA4FixturesTest` in this task).
  - Compare the `td.result-abnormal` row heights (flagged run) with the same rows' heights in the no-flags run.
  - **Required:** identical to 0.1 mm, and identical V2 page count.
  - If the text value wraps only because of the flag, report it as a found issue. Do **not** widen columns; that's a human decision.

- [ ] **Step 6: Commit**: `feat: print abnormal flag letters on lab report results`

- [ ] **Step 7: Phase 4 report**: SHAs, flag test results, and the flagged vs unflagged row-height table.

---

## Phase 5: Budget gate + full real-render sign-off

### Task 10: Budget decision (only-downward)

**Files (only if required):**
- Modify: `app/Services/LabReportBuilder.php`
- Modify: `tests/Feature/Lab/LabReportBuilderTest.php`
- Modify: `tests/Feature/Lab/LabReportPrintChromeTest.php` (V1 "three on page 1 under budget 12" + "Page 1 of 2" scenario counts), `LabReportA4FixturesTest.php` (V2 data sized to the new budget)

- [ ] **Step 1: Run V1–V5 on the final template**

```bash
vendor/bin/pest tests/Feature/Lab/LabReportA4FixturesTest.php tests/Feature/Lab/LabReportPrintChromeTest.php --no-coverage
node scripts/lab-report-print-verify.mjs --measure storage/app/lab-report-a4/*.html storage/app/lab-report-a4-budget-check.html
```

- [ ] **Step 2: Decide**
  - **All `ok:true`:** no budget change. Record the slack: page-1 used vs 277 mm in V2, and last-page used vs 277 mm in V3. **Do not raise budgets**, even with slack.
  - **V2 fails:** lower `FIRST_PAGE_ROW_BUDGET` by the smallest whole-unit step that makes V2 pass. One unit ≈ the measured 1-param row height. Compute it from the measurement, then confirm by re-render.
  - **V3 fails:** same for `PAGE_ROW_BUDGET`. Rebuild V3 to fill the new budget exactly, and re-render.

- [ ] **Step 3 (only if lowered): TDD the constant change.**
  - Update `LabReportBuilderTest` expectations first (red), including every `toBe(12)` assertion and the 3-vs-4-section packing tests rebuilt for the new number.
  - Then change the constant (green). Re-run Step 1 until all `ok:true`.

- [ ] **Step 4: Commit (only if lowered)**: `fix: lower lab report row budget to fit banded chrome on real A4`

### Task 11: Full regression + manual checklist + known-bugs gate (no merge)

- [ ] **Step 1: Focused regression**

```bash
vendor/bin/pest tests/Feature/Lab tests/Feature/Settings/LabReportPrintSettingsTest.php \
  tests/Feature/Settings/LabReportAccentPreviewTest.php tests/Unit/Support/LabReportAccentContrastTest.php \
  tests/Feature/Settings/HospitalInfoRelocationTest.php --no-coverage
npm run build && node scripts/check-vite-build-freshness.mjs
```

Expected: all PASS.

- [ ] **Step 2: Final V1–V5 real render** (backgrounds off + `--grayscale` for V5). Every line `ok:true`. Open each PDF and visually confirm:
  - band, running header, and footer band filled, with white text legible
  - logo tile visible
  - QR present in the right place for V1/V2/V4 variants
  - flags readable
  - comments tint pale
  - grayscale V5 at `#30827C` still legible

- [ ] **Step 3: Manual browser checklist (local tenant)**
  1. Hospital Info: set the PHC number → the report shows `PHC Reg. No.` top-right; turn the toggle off → it disappears
  2. Settings → Lab Report Print: dual preview iframes show the new chrome; grayscale pane legible; `#33847E` shows the 4.5 warning, Save works
  3. Real Chrome print dialog with **default settings** (Background graphics unchecked): band, running header, footer band, and section bars all print filled
  4. Public share link renders the same chrome (no Print/Close regression)
  5. Multi-page order: page 2+ shows running header with patient name + Patient No.; footer band at page bottom on every page; no blank trailing page

- [ ] **Step 4: Known bugs.** Any bug → failing test → fix → commit → re-run Steps 1–2. **Do not merge with known bugs.**

- [ ] **Step 5: Final report.** Include:
  - final HEAD SHA and test counts
  - final V1–V5 table (logical/physical/footerPinned + key heights) vs the Phase 0 baseline
  - budget decision and its evidence
  - flag zero-height proof
  - checklist results
  - **explicit statement: not merged, awaiting human merge instruction**

---

## Spec coverage self-check

| Spec / decision | Task |
|---|---|
| P1 header band, white text, full printable width, logo tile (OQ-8), icons, address kept, `LAB REPORT` caption | 5 |
| P2 PHC line + `show_phc_registration` default on (OQ-5) | 3, 5 |
| P3 three-column strip, all confirmed fields, note row, divider at strip bottom (OQ-7), QR relocation (OQ-6) | 6 |
| P4 table unchanged + F6 background forcing | Global, Track 1 test extended in 5/8 |
| P5 comments tint 7%, B&W-safe | 8 |
| P6 footer band every page, contact + page number only (OQ-2), bottom-pinned, sign-off row, data logic unchanged | 8 |
| P7 slim running header, no Order # (OQ-1) | 7 |
| P8 contrast 4.5:1 warn-only (OQ-3) | 4 |
| OQ-4 flag letters, HH/LL distinct, zero height/column proof | 9 |
| V1–V5 via CDP backgrounds-off + physical == logical pages | 1, 2, 6, 8, 10, 11 |
| Budgets only downward with `LabReportBuilderTest` update | 10 |
| No merge until clean | 11 + Global |

## Placeholder scan

Test bodies marked `/* GET … */` or `/* … */` follow the file's established fixture pattern (`$this->get(route('investigation-orders.report', $order))->assertOk()->getContent()` and local data helpers). Each lists its exact assertions in the comment. No step defers a decision. The only conditional work is Task 10's budget change, which has a defined trigger and procedure.

## Execution Handoff

Plan saved to `docs/superpowers/plans/2026-10-01-lab-report-banded-chrome.md` (force-added in Task 0).

**Do not begin execution until the human confirms this plan.**

When confirmed, two execution options:

1. **Subagent-Driven (recommended)**: a fresh subagent per task, with review between tasks (`superpowers:subagent-driven-development`), and human phase-boundary reports after Phases 0–5 as mandated above.
2. **Inline Execution**: same session via `superpowers:executing-plans`, with checkpoints.

Which approach?
