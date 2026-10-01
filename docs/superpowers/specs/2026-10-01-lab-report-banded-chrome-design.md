# Design: Lab report banded chrome (header band, patient strip, footer band)

**Date:** 2026-10-01  
**Status:** Draft — findings + proposal; **awaiting confirmation** of open questions before an implementation plan is written  
**Scope:** Lab result report print surface only (`resources/views/admin/lab/results/report.blade.php`, shared by admin, public share, and the settings preview iframes) + the small settings/builder touch points listed below  
**Direction:** Locked by product (banded header, three-column patient strip, tinted comments box, footer band). This doc maps that direction onto real fields and resolves the continuation-page question; it does not reopen the direction.  
**Prior passes this builds on:**
- `2026-09-21-lab-report-header-footer-public-qr-design.md`: patient band fields, QR, reviewer roster, footer toggles
- `2026-09-24-lab-report-previous-values-design.md`: previous-value rows and row costs
- `2026-09-30-lab-report-accent-color-design.md`: `--lab-report-accent`, accent set B, dual color/grayscale preview, warn-only contrast

---

## Findings

### F1: Current chrome markup (as of `23d2950`)

All chrome lives inline in `report.blade.php`. There are no partials. Each logical page is one `<section class="report-page">` produced by `LabReportBuilder::packIntoPages()`.

| Block | Where it renders | Contents today |
|---|---|---|
| `.header` (3-col grid `100px 1fr 100px`, `border-bottom: 2px accent`, `padding-bottom 15px`, `margin-bottom 20px`) | **Page 1 only** | Logo (100×100) left · centered name (18pt bold) + address / `Phone:` / `Email:` / `Website:` lines (each toggleable) + underlined `LAB REPORT` title · QR (90px SVG) right, or `.header-no-qr` 2-col |
| `.patient-box` (1px accent border, 2-col grid, labels `min-width:128px`) | **Page 1 only**, behind `show_patient_band` | Patient Name · Age/Sex · Registration Location (hospital name + address) · Registration Date* · Referred By (`Dr. {order.doctor}`) · Patient No. (`patient.patient_no`) · Department* · Collection · Reporting · Consultant* · Note* (`clinical_notes`, ≤120 chars). *Omitted when empty. |
| `.test-panel` / `.test-panel-header` / `.results-table` | Every page | Accent-filled bar with white uppercase investigation name; neutral table |
| `.comments-box` | Last page | `LabResult.comments` (deduped), 1px accent border, no fill |
| `.reviewer-blocks` → `.reviewer-block` | Last page, behind `show_reviewers` | Roster doctors: `Dr. {name}`, qualification, specialization |
| `.signatures` → `.signature-line` | Last page, when `primaryResult->pathologist` | `Verified By` + user name above an accent rule |
| `.report-contact` | Last page, when any `show_footer_*` is on | phone · email · address · website joined with ` · ` |
| `.page-number` | Every page, behind `show_page_numbers` | `Page N of M` |

**Continuation pages have no header and no patient identification at all.** They carry only result panels and the page number.

### F2: Measured heights (real Chrome, A4 content width)

I measured the current `lab-report-a4-budget-check.html` fixture in headless Chrome at 190 mm content width (print-equivalent). I also printed it to PDF: the result is **1 A4 page** (MediaBox 595×842 pt), which confirms today's budget-12 baseline.

| Element | Height | Incl. margins |
|---|---|---|
| `.header` | 31.0 mm | ~36.3 mm |
| `.patient-box` (fixture has every optional field) | 48.9 mm | ~52.1 mm |
| One 1-param `.test-panel` (row cost 4) | 23.8 mm | — |
| `.reviewer-blocks` (1 reviewer) | 18.3 mm | ~23.6 mm |
| `.page-number` | 4.3 mm | ~8.5 mm |
| Whole page 1 | 197.2 mm of 277 mm printable | — |

Page-1 chrome therefore costs about **88 mm**. On page 1 the banding replaces two blocks that currently take about a third of the printable height. That is why the change needs real-render re-verification and can't be judged as a "restyle".

### F3: Pagination model

- `FIRST_PAGE_ROW_BUDGET = 12` and `PAGE_ROW_BUDGET = 30` are abstract row units. A section costs `2 + rows (+prev values) + 1`.
- Budget 12 is deliberately conservative. A 1-page report is both first and last page, so it must also fit comments, reviewers, signature, and contact.
- Pages are **logical**. Every `.report-page` is meant to be exactly one physical A4 page. Anything that makes a logical page taller than 277 mm causes silent spill: an orphaned partial page, and wrong `Page N of M`.

### F4: Registration / accreditation field (item 2): **a real field exists**

Hospital Info already stores `phc_registration_number` (Punjab Healthcare Commission). It is optional, max 100 chars, and saved via `SettingsController::update`. **It is not printed anywhere today.** It qualifies as the secondary top-right detail. No other license or accreditation field exists, and none will be invented.

### F5: Abnormal-value pattern (item 4): **does not fully match the reference**

- `.result-abnormal` is **bold only**. The flag letter is **not printed**.
- `LabResultItem.flag` values are `N`, `H`, `L`, `HH`, `LL`, `A` (`isAbnormal()` / `isCritical()` in `app/Models/LabResultItem.php`).
- The reference pattern is "bold + letter flag". We have the bold half. The letter half is missing, and critical (`HH`/`LL`) is indistinguishable from `H`/`L` on paper.
- Bold-only is B&W-safe. But the claim that "it already matches the reference" is **false**. Adding the letter touches the result body, which has been locked since 2026-09-21 (L1). → **OQ-4**.

### F6: Background printing is not forced: **latent bug today, critical for the band**

- `report.blade.php` has **no `print-color-adjust: exact`**. Other print views in the app use it (HR roster, the reports module).
- Chrome's print dialog defaults "Background graphics" to **off**. In that case background fills are dropped and text color is kept.
- So today the accent `.test-panel-header` would print **white text on white**, and the investigation name disappears.
- With a white-text header band and footer band, the same failure would erase the hospital name, contact lines, and page number.
- **Fix (pure CSS, zero height impact):** `-webkit-print-color-adjust: exact; print-color-adjust: exact` on every filled element. The fix belongs in this pass. It is also worth noting the existing bar is affected now.

### F7: Contrast threshold is too low for white text on accent

- The current warning fires below **3:1**, the WCAG threshold for non-text graphics. That threshold was chosen when accent was only borders and a bar.
- The band puts **9 pt normal-weight white text** (contact lines, footer text) on the accent. WCAG text contrast for that is **4.5:1**. Large or bold text (≥14 pt bold, i.e. the hospital name) needs 3:1.
- The ratio is symmetric, so the existing accent-vs-white calculation still applies. Only the threshold and message change.
- The default `#0F766E` (~5.9:1) passes either way. → **OQ-3**.

### F8: Logo on a colored band

Most uploaded hospital logos are designed for a white background: dark marks, or a white box baked into a JPG. On a saturated band, a dark logo loses contrast, and a JPG shows a white rectangle. In grayscale both get worse. → handled in P1 with a white logo tile.

### F9: "Full-bleed" in browser print

`@page { margin: 10mm }`, and most printers can't print to the paper edge anyway. A literal edge-to-edge band would require `@page margin: 0` plus self-managed page padding, and would still be clipped by printer hardware margins. **Recommendation:** the band spans the **full printable width (190 mm)**, edge to edge of the content box. It is a band, not a centered box. This is what "full-bleed" can reliably mean for `window.print()`.

### F10: Coupled tests and surfaces

- Markup is asserted in `LabReportPrintChromeTest` (`Phone: …`, `.patient-item`/`.signature-line` border regexes, `report-contact`), `LabReportAccentChromeTest` (5 selector assertions), `LabReportAccentPreviewTest`, and `PublicLabReportTest`. All change with the new markup.
- Settings preview iframes render this same Blade, so they pick up the new structure automatically. The contrast copy lives in `settings/lab-report-print/edit.blade.php:129` and `resources/js/lab-report-accent-settings.js:67`.

---

## Proposal

### P1: Header band (page 1)

```
                                                     PHC Reg. No. 12345   ← P2, light, outside band
┌──────────────────────────────────────────────────────────────────────────┐
│ [logo tile]  HOSPITAL NAME (white, 18pt bold)       ☎ 042-1234567        │ ← accent fill,
│              address line (white, 9pt)              ✉ lab@hospital.pk    │   190mm wide
│              LAB REPORT (white, 9pt, letter-spaced) 🌐 www.hospital.pk   │
└──────────────────────────────────────────────────────────────────────────┘
```

- `.report-band`: `background: var(--lab-report-accent)`, `color:#fff`, `print-color-adjust: exact`, flex row with `space-between`, about 4 mm vertical padding.
- **Left:**
  - Logo inside a **white rounded tile** (fixes F8), shrunk from 100 px to about 64 px so the band stays shallow.
  - Hospital name, white, 18pt bold (unchanged size).
  - Address, behind the `show_hospital_address` toggle. It moves here from the centered stack and is not dropped.
  - `LAB REPORT` title as a small letter-spaced caption. It stays on the report but no longer costs its own 16pt underlined line.
- **Right:** phone / email / website, one per line, each behind its existing `show_hospital_*` toggle.
  - Icons are **inline SVG** (`fill: currentColor`, ~3.5 mm). They print as vector and inherit white. Emoji/icon fonts are not used because they are unreliable in print.
  - The `Phone:` / `Email:` / `Website:` text labels are dropped in favor of icons. Tests update accordingly.
- **QR moves out of the header** into the patient strip (P3).
- Address keeps its current toggle and placement semantics. If all right-side toggles are off, the right column collapses.

### P2: PHC registration number (top-right, outside band)

- Render `PHC Reg. No. {value}` above the band, right-aligned, 8pt, `#555`, about 4 mm tall, **only when `phc_registration_number` is filled**.
- Optional new toggle `show_phc_registration` (default **on**), in line with every other header toggle. → **OQ-5**.
- Page 1 only.

### P3: Patient strip (page 1, replaces `.patient-box`)

White, no box border, three columns: `1fr 1fr 26mm`. Font sizes unchanged (9.5pt). Label `min-width` drops from 128 px to about 26 mm to fit three columns.

| Left: who | Middle: when / where | Right |
|---|---|---|
| Patient Name | Registration Location (hospital-based, unchanged source) | QR (90 px SVG, unchanged, behind `show_qr`) |
| Age / Sex | Registration Date* | |
| Referred By | Collection | |
| Patient No. (`patient_no`, unchanged) | Reporting | |
| Department* | | |
| Consultant* | | |

- **Note\*** (`clinical_notes`, ≤120 chars) spans left + middle as a full-width row under both columns. It is too long for a column cell.
- **Every field confirmed in prior passes is kept**, with the same sources and the same omit-when-empty rules. Nothing is added or dropped. The redundant OPD/IPD/Lab # removed in `23d2950` stays removed.
- `show_patient_band` off: the strip hides, as the box does today. The QR still needs a home, so it falls back to the band's right edge. → **OQ-6**.
- **Divider:** taken literally, a rule between a colored band and the white strip is invisible against the band. Proposal: a 1 px accent rule along the **bottom** of the strip, separating patient info from results. → **OQ-7**.

### P4: Results table: no change

Panels, bar, and table are untouched structurally, except that `print-color-adjust: exact` is added to `.test-panel-header` (F6). The flag-letter gap is OQ-4.

### P5: Comments box: pale tint

```css
.comments-box {
  background: #f7f9f9;                                                   /* fallback */
  background: color-mix(in srgb, var(--lab-report-accent) 7%, #fff);
  border: 1px solid var(--lab-report-accent);                            /* unchanged */
  print-color-adjust: exact;
}
```

- At 7%, the default teal gives about `#EEF5F4`, roughly 96% gray luminance in B&W. Body text `#111` stays near 18:1. The tint can never compete with the text, even for the darkest legal accent.
- `color-mix` is supported in Chrome 111+, Firefox 113+, and Safari 16.2+. Older engines fall back to the near-white.
- Padding and font are unchanged, so there is **no height impact**.

### P6: Footer band (bookend)

**Every page** ends with `.report-footer-band`:
- accent fill, white 8.5–9pt text, `print-color-adjust: exact`
- left: existing `report-contact` parts (same `show_footer_*` toggles)
- right: `Page N of M` (`show_page_numbers`)
- If both are empty, the band still renders as a thin (~3 mm) accent bookend.

**Last page only:** comments → reviewer credential blocks → `Verified By` signature sit **above** the band, unchanged in data logic:
- `orderedReviewers()`, the roster, the `show_reviewers` toggle, and the `primaryResult->pathologist` gate are all untouched.
- Only container styling changes: reviewer blocks and the signature share a row (reviewers left, signature right) so the band sits directly beneath them, as in the reference.

**Bottom pinning:**
- In print, `.report-page` becomes `display:flex; flex-direction:column; min-height: 276mm`, and the footer band gets `margin-top:auto`. The 276 mm leaves 1 mm of slack against the 277 mm printable area to avoid rounding-induced blank pages.
- `min-height`, not `height`: an `is_large` section that already overflows today keeps flowing. Nothing is clipped, and clinical data is never hidden.

**No disclaimer text:** no disclaimer field exists, and boilerplate like "computer-generated report…" would be invented copy. → **OQ-2**.

### P7: Continuation pages: **recommendation: slim running header, not the full band**

**Recommendation:** page 1 gets the full band, PHC line, and patient strip. Pages 2…M get a **single-line running header**:

```
┌──────────────────────────────────────────────────────────────────────────┐
│ HOSPITAL NAME · LAB REPORT          Chrome Patient · Patient No. P-00042 │ ← accent fill, ~8–9mm
└──────────────────────────────────────────────────────────────────────────┘
```

Contents: hospital name (bold), and on the right the patient name plus Patient No. (and Order # if OQ-1 confirms). It uses the same `--lab-report-accent` fill and white text, with no logo, no QR, and no contact lines. Combined with P6, every continuation page is bookended top and bottom by the same accent.

**Reasoning:**

1. **It fixes a real gap, not just a style choice.** Today continuation pages carry **no patient identification** (F1). A loose page 2 can't be matched to its patient. Lab-report practice, e.g. ISO 15189 report content, expects patient identification and "page x of y" on every page. A slim header provides that. Copying the reference's full band would provide it too, but much more expensively.
2. **Cost is proportional to value.** The full band is ~30–36 mm per page, about 5–6 row units. Repeating it would push `PAGE_ROW_BUDGET` from 30 to about 24. That means more pages for long panels, more toner (a full-width fill in B&W), and repeated contact info nobody needs on page 3. The slim header is about 9 mm, roughly 1.5 units.
3. **It matches the existing model.** The builder already treats page 1 as uniquely expensive (12 vs 30). A distinct, cheap continuation chrome is the same idea expressed in the markup. Logical pages are rendered per `@foreach`, so this is a plain `@if($pageIndex > 0)` block. No `position: fixed` running-element tricks are needed, and those are flaky across browsers.
4. **Brand continuity is kept.** Accent top and bottom on every page reads as one designed document without re-printing the letterhead.

**Budget consequence:** continuation chrome grows from ~8.5 mm (page number only) to about 9 mm (running header) + ~10 mm (footer band with page number) ≈ 19 mm. Current 30-unit continuation pages use ~187 mm, so there is headroom on paper. But the **last** continuation page also carries comments, reviewers, and signature. `PAGE_ROW_BUDGET` may need to drop (estimate 30 → 28). **This must be decided by measurement, not estimate.**

### P8: Contrast warning update

- Keep the warn-only behavior and the accent-vs-white computation.
- Raise the warning threshold **from 3:1 to 4.5:1**, with copy explaining that white header/footer text needs 4.5:1.
- Same change in the Blade initial render and in `lab-report-accent-settings.js`. → **OQ-3**.

### P9: Page-1 height estimate (to be replaced by measurement)

| Block | Today | Proposed (est.) |
|---|---|---|
| PHC line | — | ~4 mm (only when filled) |
| Header | ~36 mm | ~28–30 mm band + 3 mm gap |
| Patient area | ~52 mm | ~36–40 mm strip (6 lines + note row; QR 24 mm fits inside) + rule |
| Footer | ~8.5 mm page number | ~10 mm band (bottom-pinned) |
| **Total chrome** | **~97 mm** | **~81–87 mm** |

Page 1 likely gets **shorter**. Even so, this pass does **not** raise `FIRST_PAGE_ROW_BUDGET`. Budgets only move **down**, and only when measurement forces it. Raising them would be a separate, measured decision.

---

## Mandatory verification (definition of done)

This uses the established `LabReportPrintChromeTest` method: the fixture writes HTML to `storage/app/…`, which is then rendered at real A4. I strengthen it with a check I ran during this investigation: **headless Chrome `--print-to-pdf` page count == logical `.report-page` count**. This catches silent spill that eyeballing can miss. **Correction (verified 2026-10-01):** the `--print-to-pdf` CLI flag prints backgrounds **on**, so it does *not* reproduce the dialog default. Background-off verification must use DevTools `Page.printToPDF` with `printBackground: false`, which is how the Track 1 fix for F6 was proven.

| Fixture | Must prove |
|---|---|
| **V1** existing 3×1-param fixture (budget 12) | Still **1 physical A4 page**, with footer band pinned at the bottom |
| **V2** page-1 worst case: PHC filled, long address, all header toggles, all patient-strip optional fields incl. 120-char note, comments, 2+ reviewers, signature, all 4 footer toggles, previous values on, 12 units of panels | **1 physical page**; nothing pushed onto a page 2 |
| **V3** multi-page: page 1 at 12, a continuation page filled to exactly 30 units, and a final page with comments + reviewers + signature | Physical pages == logical pages; no trailing blank page; running header + footer band on every continuation page; correct `Page N of M` |
| **V4** `show_patient_band` off, `show_qr` off, all toggles off | Layout collapses cleanly; still page-exact |
| **V5** grayscale | Settings grayscale pane (existing) + PDF review: band text legible on default accent and on a borderline 4.5:1 accent; comments tint visible but quiet |

If V2 or V3 fail, the only allowed fix is lowering the relevant budget constant (with a `LabReportBuilderTest` update), never shrinking clinical text. Report before/after measurements per element, as in F2.

---

## Open questions (please confirm)

1. **OQ-1, continuation pages:** accept **P7** (full band page 1 only; ~9 mm running header with hospital name + patient name + Patient No. on pages 2…M; footer band on every page)? Include Order # in the running header too? *Recommended: yes to P7; include Order # only if you want it. Patient No. alone is the confirmed identifier.*
2. **OQ-2, footer band text:** no disclaimer field exists. Options: (a) band carries only the existing contact parts + page number (*recommended for this pass*); (b) add a configurable `footer_disclaimer` text setting. That is new scope and a new settings field.
3. **OQ-3, contrast threshold:** raise the soft warning from 3:1 to **4.5:1** because of white text on the band? Still warn-only? *Recommended: 4.5:1, warn-only.*
4. **OQ-4, flag letter:** the report is **bold-only**; the reference is **bold + letter**. Options: (a) leave the result body locked as-is (*default*); (b) append the flag letter in the same cell (`16.8 H`, critical as `HH`/`LL`). This adds no column and no height, but it does unlock L1.
5. **OQ-5, PHC number:** print whenever filled, with a new `show_phc_registration` toggle defaulting **on**? Or print-when-filled with no toggle?
6. **OQ-6, QR with patient band off:** when `show_patient_band` is off, move the QR into the band's right edge (*recommended*), or drop it?
7. **OQ-7, divider placement:** a 1 px accent rule at the **bottom** of the patient strip (between patient info and results), rather than literally between the band and strip where it would be invisible?
8. **OQ-8, logo tile:** white rounded tile behind the logo inside the band, with the logo shrunk from 100 px to ~64 px? *Recommended: yes. Without it, dark or JPG logos degrade badly on accent and in grayscale.*

---

## Out of scope

- Results table structure, columns, and table colors (locked)
- Changing reviewer / Verified By data logic, roster, or sources of any patient-strip field
- Raising row budgets
- Prescription / Dompdf print surfaces
- Implementation plan (next pass, after confirmation)
