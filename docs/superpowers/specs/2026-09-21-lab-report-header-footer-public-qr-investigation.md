# Lab report header/footer & public QR — investigation

**Date:** 2026-09-21  
**Status:** Findings only (complete). Design: `2026-09-21-lab-report-header-footer-public-qr-design.md`. No code / implementation plan in the investigation pass.  
**Method:** Superpowers brainstorming Phase 0 (context exploration). Code on `main` @ `f7f9823` (post HTTP-identity merge). Inventory of lab report HTML print path, public share/signed URL patterns, reviewing/pathologist fields, Doctor Share rates UI, Hospital Info settings, Prescription Print Templates, and settings catalog registration.

**Reference input:** Configurable header (logo + QR + Patient Detail band) and footer (reviewed-by consultant line + contact + page number); result body unchanged. Reference PDF described in the request (not present as a file in the workspace at investigation time — field list taken from the written requirements).

**Related**

- `docs/superpowers/specs/2026-08-29-prescription-print-templates-design.md` — Settings child + DomPDF letterhead system (different print stack from lab HTML).
- `docs/superpowers/specs/2026-09-17-doctor-share-rates-and-settings-design.md` — dynamic doctor-row rates UI.
- `docs/superpowers/specs/2026-09-01-parent-route-module-entitlement-design.md` / ungated UI — laboratory gating.
- Live public access: `PublicLabReportController`, `LabOrder::share_token`, `tests/Feature/Lab/PublicLabReportTest.php`.

**Out of this pass:** Design of QR encoding, roster UX, settings child slug, template editor, implementation plan.

---

# Verdict (what exists today)

Lab reports are **browser HTML print** (`admin.lab.results.report` + `LabReportBuilder`), **not** Prescription Print Templates / DomPDF. Header (hospital letterhead + patient box), result panels, and signature footer already sit in **distinct DOM blocks** in one Blade file — separable in structure, **not** yet extracted into partials or a settings-driven template.

**Public download already exists** for lab orders: unguessable `share_token` (40-char random) + patient-number/mobile verify gate + session unlock. A **legacy** Laravel `signed` route with **numeric** `{labResult}` still exists but only **redirects** into the token flow. **No QR code** is generated anywhere. **No other document type** (bills, prescriptions, imaging) has an equivalent unauthenticated public viewer — ImagingOrder carries a `share_token` column but has **no** public routes/controller.

**Reviewing consultant:** `LabResult.pathologist_id` → **`users`**, set automatically to `auth()->id()` on verify. Report shows that user’s **name only**. This is **not** a selectable `Doctor` with printable credentials, and **not** multi-consultant. Item-level `LabResultItem.verified_by` exists in schema but is **never written** by lab result entry code.

---

# Scope flags (items 1–2 — capability beyond “print chrome”)

| ID | Topic | Verdict for design |
|---|---|---|
| **S1** | QR / public link | **Mostly existing capability.** Public share URL + verify gate already ship. **New:** QR graphic encoding that URL (and any layout placement). Enumerating sequential IDs is **already avoided** by `share_token`; do not regress to bare numeric IDs. Legacy signed numeric route is a redirect shim only. |
| **S2** | Reviewing / authorizing consultant(s) | **New capability beyond print chrome** if the product needs a **Doctor roster**, printable qualifications, and/or **multiple** “reviewed by” lines. Today’s `pathologist_id` (User, auto-auth) is a thin, single-actor verify stamp — usable as a weak default for one name, insufficient for the reference PDF’s consultant panel. |

Items 3–4 and the original Phase 0 surfaces (template audit, settings catalog, laboratory dependency, data model) are mostly **reuse / configure / present** work once S1–S2 product choices are locked.

---

# 1. Current lab report rendering & header/footer separability

## 1.1 Stack

| Piece | Location |
|---|---|
| Builder | `app/Services/LabReportBuilder.php` — packs `LabOrder` + `LabResult`s into paginated **test sections** (row budgets 14 / 30) |
| Authenticated print | `LabResultController::report` / `orderReport` → `resources/views/admin/lab/results/report.blade.php` |
| Public print | `PublicLabReportController::view` → **same** Blade with `$isPublic = true` |
| Print JS | `resources/js/lab-report-print.js` (Vite) — window.print / close |

## 1.2 Blade structure (one file, three zones)

`report.blade.php` layout per page:

1. **First page only — letterhead `.header`:** logo (if set) left; hospital name/address/phone/email centered; hardcoded title `LAB REPORT`. **No QR.**
2. **First page only — `.patient-box`:** Patient Detail band (partial overlap with the required band — see §1.3).
3. **Every page — body:** `@foreach ($page['sections'] …)` → `.test-panel` + results table (parameter / result / unit / reference). **This is the result body.**
4. **Last page only — `.comments-box` + `.signatures`:** technician + “Pathologist / Consultant” lines from `$primaryResult` (last LabResult on the order). **No page number. No website. No multi-consultant roster.**

**Separability verdict:** Result body is **genuinely separable** from header/footer: it is a separate `@foreach` of sections and does not interleave patient/hospital markup. Header/footer are **not** shared Blade partials today — they are inline blocks in the same view. Redesigning header/footer without rewriting test-panel markup is structurally feasible; pagination (`LabReportBuilder` row budgets) currently assumes letterhead+patient box consume first-page budget — any taller configurable header must revisit `FIRST_PAGE_ROW_BUDGET`.

## 1.3 Patient Detail band — present vs required

| Required (request) | Present on report today |
|---|---|
| Patient name | Yes |
| Age / sex | Yes |
| Registration location | **No** |
| Registration date | **No** (order/collection/reporting times only) |
| Reference / referring source | Partial — “Referred By” = order’s `doctor` (ordering doctor), not a free-text referrer |
| Consultant | Shows `$primaryResult->pathologist->name` or `Pending` (User, not Doctor) |
| Patient number | Yes (`patient_no`, labeled `{visitType} #`) |
| Case number | **No** (shows Order # instead) |
| Note | **No** (order clinical notes not in band) |
| Department name | **No** |
| Collection / reporting datetimes | Yes |
| QR top-right | **No** |

---

# 2. QR code / public download link (security)

## 2.1 Existing public / unauthenticated document access

| Surface | Pattern | Enumerability |
|---|---|---|
| **Lab report (current)** | `LabOrder.share_token` = `Str::random(40)`, unique; routes `lab-report/{shareToken}` show/verify/view; verify requires matching patient_no + phone; session unlock 120 minutes | **Not sequential** — token space is large; route regex `[A-Za-z0-9]{20,64}` |
| **Lab report (legacy)** | `GET lab-report/{labResult}` + middleware `signed` + `whereNumber('labResult')` → `LabResultController::publicReport` **redirects** to token URL | Numeric ID alone is **not** sufficient (signature required); still a footgun if signature were ever stripped |
| **Email verification** | `URL::temporarySignedRoute` in `EmailVerificationTest` / auth flow | Laravel signed URLs (app-wide) |
| **Bills / prescriptions / visit print** | Auth + Spatie permissions only; HTML print views | No public share |
| **Imaging** | `ImagingOrder.share_token` column + generator exist | **No** public routes/controller found |

WhatsApp share (`lab-results.share-whatsapp`) builds message with `$order->publicReportUrl()` → token URL.

## 2.2 Laravel signed / temporary signed URLs (this app)

- Framework: **Laravel 12.48.1** (`composer.lock`).
- Production use of `signed` middleware: **lab legacy route only** (`routes/web.php` ~1004–1008).
- `URL::temporarySignedRoute`: used in **auth email verification tests**; not used for lab share links today.
- Lab’s **preferred** public mechanism is **opaque token + knowledge factor (patient_no + phone)**, not a bare signed URL that reveals the report to anyone holding the link. Design should treat that as the established product security model unless product deliberately loosens it for “QR opens report with no second factor.”

## 2.3 Enumeration risk (explicit)

A URL of the form `/lab-report/{sequentialId}` **without** a signature or unguessable token would allow guessing other patients’ reports. **Current production share path does not do that.** Any QR that embeds a public URL should use `LabOrder::publicReportUrl()` (token) or a new equally unguessable secret — **not** the legacy numeric signed route as the primary QR target (even though signed, it still teaches numeric IDs).

## 2.4 QR generation

**No** QR library usage or QR markup found in app Blade/JS/composer deps for lab (or elsewhere). Encoding the existing public URL into a QR image is **new presentation work**, not a new auth system.

---

# 3. Reviewing / authorizing consultant per lab result

## 3.1 Schema & models

| Field | Table / model | Relation | Written by production code? |
|---|---|---|---|
| `pathologist_id` | `lab_results` / `LabResult` | `belongsTo(User::class)` | **Yes** — `LabResultController::verify` sets `pathologist_id = auth()->id()`, `verified_at`, `reported_at`, `status = final` |
| `technician_id` | `lab_results` | `User` | **Yes** — set to `auth()->id()` on result create/store paths |
| `verified_by` / `verified_at` | `lab_result_items` / `LabResultItem` | `User` | **Schema + fillable only** — **no writes** in `LabResultController` (unused / ceremonial, same class of gap as other “exists but unused” fields in this program) |

There is **no** `doctor_id` on `LabResult` for the verifier. Ordering doctor lives on `LabOrder.doctor_id` → `Doctor`.

## 3.2 Shape today

- **Single** verifier actor per `LabResult` row (`pathologist_id`).
- Actor type: **User**, not **Doctor**.
- Not free text; not a multi-doctor collection.
- Report footer / patient-band “Consultant” use `$primaryResult` = **last** `LabResult` on the order — multi-result orders collapse to one displayed pathologist.

## 3.3 Natural insertion point (if product needs Doctor roster)

| Step | Today | Candidate for “select reviewing consultant(s)” |
|---|---|---|
| Enter results | Sets `technician_id` | Optional |
| **Verify / finalize** (`LabResultController::verify`) | Auto-stamps current User as pathologist | **Natural gate** — replace or augment with Doctor picker(s) before finalize |
| Print-time only | N/A | Would allow wrong/missing clinical attribution; weaker than verify-time |

**Scope note (S2):** Selecting consultants from a **settings roster of Doctors**, printing `qualification` / specialization lines, and supporting **multiple** reviewers are **not** print-template-only — they need product rules + likely schema/UI at verify (and possibly a settings roster table). Reusing `pathologist_id` alone cannot express Doctor credentials without resolving User→Doctor (not guaranteed 1:1 for pathologists).

---

# 4. Settings-configured consultant roster & Doctor credentials

## 4.1 Doctor Share dynamic-row pattern

| Asset | Role |
|---|---|
| `resources/js/doctor-share-rates-form.js` | Add doctor from `<select>`; disable already-added options; remove row; reindex `doctors[i][…]` names |
| `resources/views/admin/doctor-share/rates/index.blade.php` | Template row + `data-added-ids` |
| Module | `settings.doctor-share` |

**Reuse verdict:** Interaction pattern (dropdown excludes already-added, add/remove rows) is **directly reusable** as a UX pattern for “lab report consultant roster.” It is **not** a drop-in component — rates form is category % inputs, not credential display. Expect a **copy/adapt** of the JS + Blade pattern, new persistence (setting JSON or dedicated table), not shared code with rates.

## 4.2 Doctor printable credentials

`Doctor` fillable already includes:

- `qualification` (e.g. `MBBS, FCPS` — used on visit print letterhead and prescription field catalog)
- `specialization`
- `pmdc_number`
- `name`, `department_id`, etc.

**No** dedicated “lab report signature line” field. Reference-PDF-style “MBBS, FCPS, Consultant Chemical Pathologist” can often be composed from `qualification` + `specialization` (and optional free-text later). **New field only if** composition is insufficient for clinics that need a distinct print-only title.

---

# 5. Hospital Info fields & empty-field convention

## 5.1 Fields that exist (Hospital Info settings)

Persisted via `Setting::set` / `setting()` keys (form: `resources/views/settings/hospital-info.blade.php`, `SettingsController`):

| Key | In Hospital Info form | Used on lab report today |
|---|---|---|
| `hospital_name` | Yes (required) | Yes |
| `hospital_address` | Yes (required) | Yes, `@if` |
| `hospital_phone` | Yes (required) | Yes, `@if` |
| `hospital_email` | Yes | Yes, `@if` |
| `hospital_logo` | Yes (file) | Yes, `@if` |
| `phc_registration_number` | Yes (optional) | **No** on lab report |
| `currency`, `timezone` | Same settings update | N/A to letterhead |
| **`hospital_website`** | **Does not exist** | Footer website for PDF would be **new setting** (or reuse another source) |

## 5.2 Empty / unset degradation convention

Across lab report, bills print, payslip, visit print, public verify page:

- **Omit the element** when empty (`@if($settings['hospital_phone'])`, `@if(setting('hospital_logo'))`, etc.).
- Defaults when reading: often `setting('hospital_name', config('app.name'))`; address/phone/email default to `''` in controllers so blank means omit.
- Hospital Info **form** itself uses placeholder-ish **default values inside the input** for required fields (e.g. sample address/phone) so empty DB is rare after save — but print paths still guard with `@if`.

**Convention to follow:** omit empty contact lines; do not invent “N/A” placeholders for missing phone/email/website on print chrome (except where clinical fields already use explicit strings like `Pending` / `Not recorded` / `N/A` for referrer).

---

# 6. Original Phase 0 surfaces (still in scope)

## 6.1 Prescription Print Templates (audit)

| Aspect | Finding |
|---|---|
| Purpose | Configurable **prescription** letterhead PDF (DomPDF), field positions on background image |
| Settings child | `settings.prescription-print` — `SettingsSectionRegistry`, `ModuleRegistry`, `PermissionRegistry` (`access settings.prescription-print`) |
| Lab reuse | **Not reused.** Lab is HTML/`window.print`, not DomPDF templates. Building lab chrome as another PrescriptionPrintTemplate would be a **different product decision** and a large coupling; structurally lab already has its own Blade. |
| Relevance | Pattern for **settings child registration** (slug, routes, Spatie access permission, sidebar under Settings) — copy that mechanics, not the PDF field editor, unless design explicitly chooses DomPDF for lab |

## 6.2 Settings catalog registration mechanics

To add a new settings surface (e.g. “Lab report consultants” or “Lab report letterhead”):

1. `ModuleRegistry` child under `settings` (or decide laboratory-owned child — none like that today).
2. `SettingsSectionRegistry::$sections` entry (key, label, route, order).
3. `PermissionRegistry` Spatie name `access settings.…`.
4. Routes + `settings.section:…` middleware (see hospital-info / prescription-print groups in `routes/web.php`).
5. Sidebar Settings children gated by `ModuleRegistry::allows` / section access (existing Settings shell).

`normalize()` **locks** `settings.hospital-info` onto any plan with `settings`; prescription-print and doctor-share are **not** auto-added.

## 6.3 Laboratory module dependency

- Lab result CRUD / report routes live under module **`laboratory`** (`lab.*`, `lab-results.*`, …).
- Public `lab-report.*` routes sit **outside** the auth middleware group but inside tenant middleware — **not** CheckModule-gated (by design for patients).
- A **settings** child for roster/letterhead would typically require `settings` (+ explicit child), **not** automatically the laboratory module — unless design places config under Laboratory UI instead of Settings (no precedent for “laboratory.*” settings children today).

## 6.4 Lab result data model (summary)

```
LabOrder (patient, visit, doctor/orderer, share_token, sample_collected_at, …)
  └─ LabOrderItem → LabTest
  └─ LabResult[] (technician_id User, pathologist_id User, verified_at, reported_at, comments, …)
        └─ LabResultItem[] (value, flag, verified_by User UNUSED, …)
```

Legacy `TestOrder` (visit workflow) is a **separate** path from modern `LabOrder` / `LabResult` reporting.

---

# 7. Gap map vs reference header/footer

| Zone | Gap |
|---|---|
| Header logo | Exists; placement is left (not necessarily “top-left + QR top-right” grid) |
| Header QR | Missing entirely — encode existing public URL (S1 presentation) |
| Patient Detail band | Partial fields; several required labels missing (§1.3) |
| Result body | Keep as-is (sections/tables) — separable |
| Footer reviewed-by | Single User name via pathologist; no Doctor credentials; no multi-consultant; no settings roster (S2) |
| Footer contacts | Phone/email/address from Hospital Info with omit-if-empty; **website missing** |
| Page number | Missing |

---

# Self-review

- Placeholders: none beyond “design must choose” notes for S1/S2 product options.
- PDF binary not in repo — requirements taken from the written request.
- Scope flags S1/S2 called out before design.
- No code or design proposals in this document.

---

**End of investigation. Ready for design pass when confirmed.**
