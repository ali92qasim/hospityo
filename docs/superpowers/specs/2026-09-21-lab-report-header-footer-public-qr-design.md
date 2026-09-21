# Lab report header/footer, QR, and reviewing consultants — design

**Date:** 2026-09-21  
**Status:** confirmed 2026-09-21 (Q1–Q4, Q6 as proposed; **Q5 corrected** — Registration Location = Hospital Info name/address, not visit_type). Implementation plan: `docs/superpowers/plans/2026-09-21-lab-report-header-footer-public-qr.md`.  
**Method:** Superpowers brainstorming (design after Phase 0). Builds on `2026-09-21-lab-report-header-footer-public-qr-investigation.md`. Does not re-open that inventory.

**Related**

- `docs/superpowers/specs/2026-09-21-lab-report-header-footer-public-qr-investigation.md` — HTML print stack; `share_token` + verify gate; `pathologist_id` → User; Doctor Share row pattern; Hospital Info keys; Prescription Print ≠ lab; `FIRST_PAGE_ROW_BUDGET = 14`.
- `docs/superpowers/specs/2026-08-29-prescription-print-templates-design.md` — settings child registration precedent; DomPDF (out of scope for lab chrome).
- `docs/superpowers/specs/2026-09-17-doctor-share-rates-and-settings-design.md` — add/remove doctor rows UI.

---

# Locked principles (do not reopen)

| ID | Principle |
|---|---|
| L1 | **Result body unchanged** — test panels / parameter tables stay as currently rendered; only header, Patient Detail band, and footer chrome change. |
| L2 | **Public verify gate unchanged** — QR encodes `LabOrder::publicReportUrl()` only; scanning must still hit patient_no + phone challenge before view. No bypass. |
| L3 | **No sequential public IDs** — do not resurrect numeric enumeration; token URL remains the only public report URL. |
| L4 | **Lab stays HTML / `window.print`** — do not migrate lab reports onto DomPDF / Prescription Print absolute positioning. |
| L5 | **Omit-if-empty** for missing hospital/contact/credential/consultant print lines (match existing print convention). |

---

# Findings (from investigation — not re-proven here)

1. Lab report = `LabReportBuilder` + `admin.lab.results.report` Blade; header / patient box / body / signatures already distinct DOM blocks.
2. Public access = `share_token` + verify; legacy `lab-results.public-report` signed numeric route only redirects into token flow.
3. `LabResult.pathologist_id` → User, set on verify; unused item-level `verified_by`; no Doctor pivot for reviewers.
4. Doctor Share rates JS is a reusable **interaction pattern** (copy/adapt), not a shared component.
5. `Doctor` already has `qualification`, `specialization`, `pmdc_number`.
6. Hospital Info has name/address/phone/email/logo/PHC — **no website**.
7. `FIRST_PAGE_ROW_BUDGET = 14` assumes current letterhead + patient box height.
8. Prescription Print Templates = different stack; useful only as **settings registration** precedent.

**Caller check for legacy signed route (this design pass):** `rg` over `app/`, `resources/`, `routes/`, `tests/` finds **no** `route('lab-results.public-report')` / Blade / JS callers. Only: route registration, `LabResultController::publicReport` (redirect shim), and WhatsApp using `publicReportUrl()` (token). Safe to remove after a plan-step re-confirm `rg` (same discipline as L4 dead routes).

---

# Proposal

## P1. QR code — encode existing public URL

### Approach

| Option | Mechanism | Pros | Cons |
|---|---|---|---|
| **A — `bacon/qr-code` → SVG (recommended)** | Pure-PHP Composer package; render SVG (string or data URI) in Blade | No Imagick/binary; consistent with Dompdf “no external service / no OS binary” rationale; SVG prints cleanly in Chromium | Extra composer dep |
| B — `endroid/qr-code` | Higher-level wrapper (often Bacon underneath) | Convenient API | Heavier; historically more GD/Imagick surface |
| C — JS client-side QR | Browser library draws into canvas | No PHP dep | Public + authenticated views both need JS; print timing risk; worse for public verify→view path |

### Recommendation

**Option A.** Add `bacon/qr-code` (or the minimal Bacon Writer stack). Server builds an SVG for `LabOrder::publicReportUrl()` and embeds it in the report header (authenticated print and public view after unlock).

**Security note (verbatim):** The QR is a **shortcut to the token URL**, not a decryptor or session mint. `PublicLabReportController` verify flow stays exactly as today.

### Layout

First-page header becomes a **3-column grid**: logo (left) | hospital title/details (center) | QR (right, ~80–100px). Toggleable via settings (P5) with default **on**.

---

## P2. Retire legacy numeric signed route

**Remove** (after plan-time `rg` re-confirm):

- Route `lab-results.public-report` (`GET lab-report/{labResult}` + `signed` + `whereNumber`)
- `LabResultController::publicReport`

Keep: `lab-report.{show,verify,view}`, `LabOrder::share_token` / `publicReportUrl()`, WhatsApp share.

Regression: `PublicLabReportTest` + WhatsApp share JSON still green; assert `Route::has('lab-results.public-report') === false`.

---

## P3. Reviewing consultants (new capability)

### P3.1 Schema — attach to `LabResult`, not `LabOrder`

**Recommendation: many-to-many `lab_result` ↔ `doctors`.**

| Rationale | |
|---|---|
| Verify/finalize already runs **per `LabResult`** | Selection belongs on the same unit |
| Order can hold **multiple** `LabResult` rows | Different batches can have different reviewers |
| Report sections are per **LabTest**, not per LabResult | Order-level attribution would blur batch verify; result-level matches the clinical action |

**Table (proposed name):** `lab_result_reviewers`

| Column | Type |
|---|---|
| `lab_result_id` | FK → `lab_results`, cascade |
| `doctor_id` | FK → `doctors`, cascade |
| `sort_order` | unsigned int, default 0 |
| timestamps | optional |

Unique `(lab_result_id, doctor_id)`. Eloquent: `LabResult::reviewers()` → `belongsToMany(Doctor::class)`.

**Retain** `pathologist_id` (User) as the **audit stamp of who clicked Verify** — do not overload it to mean printable consultant. Print footer uses **reviewer Doctors only**.

**Print aggregation:** Footer lists **unique** reviewers across all `LabResult`s on the order, ordered by `sort_order` then doctor name. Patient-band “Consultant” line: first reviewer name, or omit if none (L5) — replace today’s `Pending` User name (see open Q1).

### P3.2 Settings roster — `settings.lab-report-print`

Eligible consultants are configured on a **settings child** screen (registration in P5), not on every verify.

**UI:** Copy/adapt Doctor Share rates pattern (`doctor-share-rates-form.js` style): `<select>` of Doctors, Add row, disable already-added, Remove. Persist as ordered list of `doctor_id`s (tenant settings JSON key `lab_report_reviewer_roster` **or** small `lab_report_roster_doctors` table — prefer **table** if sort_order + FK integrity matter; JSON is acceptable for YAGNI if plan prefers one migration fewer — **open Q2**; default recommendation: **`lab_report_roster_doctors` table** `(doctor_id unique, sort_order)`).

**“Main consultant only” mode:** **Do not** add a separate toggle. A roster with **one** entry is the single-consultant case. Simpler; no dual code paths.

Empty roster: verify UI shows “No consultants configured in Lab Report Print settings”; finalize still allowed (P3.3).

### P3.3 Selection UI — on verify/finalize

**Location:** Expand the existing verify form on `admin.lab.results.show` (`#verify-result-form` → `lab-results.verify`). Before submit, show checkboxes (or multi-select) of **roster Doctors only**. Order of checked items = `sort_order` on pivot.

**Required?** **Optional** (aligned with omit-if-empty).

| Finalize with… | Behavior |
|---|---|
| ≥1 reviewer selected | Persist pivot; print footer shows credential blocks |
| 0 reviewers selected | Persist empty pivot; **omit** reviewed-by footer block and omit Patient Detail “Consultant” line — **no** `Pending` placeholder |
| Roster empty in settings | Same as zero selected |

`pathologist_id` / `verified_at` / `status=final` behavior unchanged for the acting User.

### P3.4 Printable credential block (three lines)

For each reviewer Doctor, footer block:

```text
Dr. {name}
{qualification}
{specialization}
```

- Prefix `Dr. ` then `name` (trim).
- Line 2: `qualification` only if non-empty (omit line if empty).
- Line 3: `specialization` only if non-empty (omit line if empty).
- Do **not** auto-append PMDC on these three lines (keeps PDF pattern tight). PMDC remains available later via open Q3 if clinics need a fourth line.

Multiple reviewers: **horizontal wrapping row** of credential blocks (matching reference PDF), not a vertical stack.

---

## P4. Patient Detail band — close §1.3 gaps

| Band field | Source | New field? |
|---|---|---|
| Patient name | `Patient.name` | No |
| Age / sex | `Patient.age`, `Patient.gender` | No |
| **Registration location** | Hospital Info: `setting('hospital_name')` and `setting('hospital_address')` (omit empty address per L5) — mirrors letterhead; single-location product, **not** visit_type | **No** new column |
| **Registration date** | `$order->visit->visit_datetime` if visit; else `$order->ordered_at` | No |
| Reference / referring source | Keep ordering `LabOrder.doctor` as “Referred By” (existing) | No |
| Consultant | First reviewing Doctor name from P3 aggregation; omit if none | No (uses P3) |
| Patient number | `Patient.patient_no` | No |
| **Case number** | `LabOrder.order_number` | **No** — single line **Case #** = `order_number` (retire redundant separate “Order #” line) |
| **Note** | `LabOrder.clinical_notes` (truncate ~120 chars in band; full notes remain in comments area if already shown) | No |
| **Department name** | Unique title-cased `LabTest.category` values from tests present on the report (e.g. `Biochemistry, Hematology`) | No — category is the lab’s department taxonomy today (no `department_id` on `LabTest`) |
| Collection / reporting | Existing | No |

**Q5 (confirmed):** Registration Location is the hospital’s own name/address from Hospital Info settings — **not** visit_type and **not** a new multi-branch location field.

---

## P5. Settings child `settings.lab-report-print` — registration + config surface

### Registration (copy §6.2 mechanics)

| Layer | Value |
|---|---|
| ModuleRegistry slug | `settings.lab-report-print` (child of `settings`) |
| SettingsSectionRegistry | label **Lab Report Print**, icon e.g. `fa-flask`, order after prescription-print (~25), route `settings.lab-report-print.edit` (or `.index`) |
| PermissionRegistry | `access settings.lab-report-print` |
| Routes | Under `settings.section:settings.lab-report-print` middleware group |
| Sidebar | Settings shell child via existing section registry |
| Plan entitle | Explicit grant like prescription-print / doctor-share — **not** auto-locked by `normalize()` (unlike hospital-info) |

**Module dependency:** Configuring the screen requires the settings child on the plan. **Printing** lab reports remains gated by `laboratory` (+ Spatie lab permissions) as today. Public token view stays ungated by CheckModule.

### What is configurable (simple toggles — not pixel placement)

Given L4 (HTML print), configuration is **boolean toggles + roster**, not DomPDF field coordinates.

| Control | Default | Effect |
|---|---|---|
| Show logo | on | Header left |
| Show QR | on | Header right → `publicReportUrl()` SVG |
| Show hospital address / phone / email / website in letterhead and/or footer | on each | Omit when setting empty (L5) |
| Show Patient Detail band | on | First page only |
| Show reviewed-by consultant blocks | on | Footer; still omit when no reviewers on order |
| Show page numbers | on | Per P8 |
| **Consultant roster** | empty | P3.2 add/remove Doctors |

**Fixed (not configurable):** CSS fonts/margins; 3-column header structure; result table columns; LabReportBuilder packing algorithm (constants may change in code, not via UI); verify-gate behavior.

Persist toggles as a single JSON settings key `lab_report_print` (booleans) + roster table (P3.2).

---

## P6. `hospital_website`

- Add optional `hospital_website` to Hospital Info form + `SettingsController` allowed keys + `UpdateSettingsRequest` (nullable URL/string, max length).
- Lab footer / letterhead: `@if(setting('hospital_website'))` link or plain text — omit if empty (L5).
- No migration of `settings` table structure (key/value store already).

---

## P7. Pagination — concrete `FIRST_PAGE_ROW_BUDGET`

Taller first page (QR row + denser Patient Detail) reduces room for test panels on page 1.

| Constant | Today | Proposed |
|---|---|---|
| `FIRST_PAGE_ROW_BUDGET` | **14** | **8** |
| `PAGE_ROW_BUDGET` | **30** | **30** (unchanged — continuation pages have no letterhead/patient band) |
| `SECTION_HEADER_ROWS` / `SECTION_FOOTER_ROWS` | 2 / 1 | unchanged |

**Rationale:** Net ~6 “row units” reclaimed for chrome (QR ≈ 2–3 visual rows; ~3–4 extra patient-band lines in a 2-column grid). Keeping 14 would systematically overflow first physical page and fight browser print.

Update `LabReportBuilderTest` packing expectations accordingly. No UI control for this constant in v1 (P5 fixed).

---

## P8. Page numbers — Blade logical pages (not CSS `@page` counters)

**Limitation:** Chromium/`window.print` does **not** reliably support CSS Paged Media `counter(page)` / `counter(pages)` in `@page` margin boxes. Relying on native browser page counters is rejected for this stack.

**Solution:** LabReportBuilder already emits discrete `$pages`. Each `.report-page` footer prints:

`Page {{ $pageIndex + 1 }} of {{ count($pages) }}`

This matches the app’s intentional page packing. Toggle via P5 (`show_page_numbers`, default on).

**Accepted residual risk:** If a single packed section still overflows one sheet of paper, the browser may split that section across sheets while the Blade label stays “Page N of M”. Mitigated by P7’s tighter first-page budget and existing `PAGE_ROW_BUDGET`; not solved by Dompdf in this design (L4).

---

## P9. Footer composition (summary)

On **last** logical page (and optionally a slim repeating strip on every page for page # only):

1. Reviewed-by consultant blocks (P3) — if any and toggle on  
2. Hospital contact line: phone · email · address · website — each segment omit-if-empty  
3. Page N of M (P8)

Technician signature line may remain as today (User technician name) — out of roster scope.

---

# Out of scope

- DomPDF / Prescription Print field editor for lab  
- Imaging public share / QR  
- Changing verify-gate credentials  
- Physical multi-branch registration-location schema (Q5 uses Hospital Info)  
- Implementation plan / migrations in this pass  

---

# Open questions

| ID | Question | Default if unanswered |
|---|---|---|
| **Q1** | Patient Detail “Consultant” when reviewers exist: show **first** reviewer only, or comma-separated names? | **First** reviewer only — **confirmed** |
| **Q2** | Roster persistence: dedicated `lab_report_roster_doctors` table vs settings JSON? | **Table** — **confirmed** |
| **Q3** | Include PMDC as optional 4th credential line? | **No** — **confirmed** |
| **Q4** | Multiple reviewers layout: vertical stack vs horizontal? | **Horizontal wrapping** (reference PDF row) — **confirmed** (supersedes earlier vertical default) |
| **Q5** | Registration location source? | **Hospital Info name + address** (not visit_type) — **confirmed correction** |
| **Q6** | Should `settings.lab-report-print` be entitled only when plan also has `laboratory`, or independent? | **Independent** — **confirmed** |

---

# Self-review

- Placeholders: none beyond numbered open questions with defaults.  
- Consistency: L2 verify gate preserved; L4 HTML print; P7 gives numeric budget; P8 avoids CSS counter fantasy.  
- Scope: S2 consultants are explicit new capability; S1 QR is presentation over existing URL.  
- Legacy route removal gated on zero callers (confirmed in this pass; re-`rg` at implement).

---

**End of design. Please review and confirm (including Q1–Q6 or accept defaults) before the implementation-plan pass.**
