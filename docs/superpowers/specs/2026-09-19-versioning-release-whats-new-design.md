# Versioning, release CLI, and What's New

**Date:** 2026-09-19  
**Status:** design proposal. **No code. No implementation plan.**  
**Method:** Superpowers brainstorming (spec first). Builds on `2026-09-19-versioning-and-release-process-investigation.md`. Does not repeat that inventory.

**Related**

- `docs/superpowers/specs/2026-09-19-versioning-and-release-process-investigation.md` — no app version, no tags, last-100 commits 90% strict Conventional Commits, shared hosting via `origin/main` then `git pull`, no changelog / What's New UI, leftover unused tenant `notifications` table.
- `docs/superpowers/specs/2026-09-01-backup-sidebar-drift-investigation.md` — Dashboard: no plan slug, CheckModule ungated, sidebar always shown.
- CheckModule: route with no `ModuleRegistry` prefix match → `$module` null → allow.

**In this pass**

- Where the current version is stored (CLI + app).
- `app:release` artisan command: bump rules, changelog filter/format, DB write, git tag + push.
- Landlord `releases` / `changelog_entries` schema.
- Tenant (and super-admin) What's New page + where it is linked.
- Entitlement: ungated vs a plan slug.
- Unobtrusive admin version indicator.

**Out**

- Implementation, migrations-as-code, or a task plan (next pass after confirmation).
- GitHub Actions, GitHub Releases UI, or a deploy script (investigation: shared hosting does not use them).
- Public marketing-site What's New (landing/documentation stay as they are).
- Dismissible modal, unread badges, email blast of notes.
- Editing changelog copy in super-admin (CLI is the writer this pass).
- Reusing or dropping the unused tenant `notifications` table.
- Pharmacy POS layout (kiosk; no admin chrome).

---

# Part A — Findings (current system, not re-litigated)

## A0. Already confirmed 2026-09-19

- No `composer.json` `version`, no `VERSION` file, no `config('app.version')`, no footer/API version string.
- Zero git tags local and on `origin`.
- Last 100 subjects: 90 strict `type:` / `type(scope):`, 4 `Fix:`, 6 free-form. All 288: 138 strict, 82 case-variant (almost all `Fix:`), 68 free-form.
- Strict types in the full history: `feat` 83, `fix` 35, `test` 10, `chore` 3, `refactor` 3, `ci` 2, `docs` 2. **`perf` 0. `BREAKING CHANGE` / `type!:` 0.**
- Production: push `origin/main`, SSH `git pull`, manual `npm run build` / artisan. No automated hook to hang a bump on.
- Admin layout has **no footer**. Header is title + language + user menu (Profile, Settings, Logout). Documentation is a **public** central route linked from the **landing** footer, not from admin.
- Dashboard is intentionally ungated (no slug, always in `SidebarService`). CheckModule allows any route whose name is not in a module `routes[]` prefix list.

## A1. Idiom gaps the investigation did not need (narrow)

**Artisan:** existing commands are domain-prefixed (`tenant:create`, `tenants:sync-permissions`, `pharmacy:alert-near-expiry`). `app:release` is acceptable as a platform-level command (not a tenant domain).

**Landlord content models:** `Page`, `ContactMessage`, `SiteSetting` use `protected $connection = 'landlord'` and `Schema::connection('landlord')->create(...)`. `Plan` uses `UsesLandlordConnection`. Product-wide notes are the same class of data as Pages, not per-hospital.

**Config:** `config/app.php` is env-backed name/debug/url. Nothing in this app is currently sourced from a committed root text file. `env('APP_VERSION')` would **not** update on `git pull` (`.env` is per-server).

**Composer:** `composer.json` `name` is still `laravel/laravel`. This is an application, not a Packagist package. Composer’s `version` field is for published packages.

**Refactor commits (all of them):**

- `refactor: CheckModule uses ModuleRegistry::planAllows`
- `refactor(pharmacy): use MedicinePricing in prescription store` (appears twice in history)
- `refactor(pharmacy): use MedicineStockConversion in stock-in`

Those are internal wiring. None is a customer-facing “what’s new.”

**CheckModule on What's New:** a `whats-new.*` route with **no** `ModuleRegistry` `routes[]` entry is ungated the same way `dashboard` is. No new slug required for “allow everyone.”

---

# Part B — Proposed design

Everything in this part is **proposed**. Part C can change it.

## B1. Version storage

### Approaches

**A — `composer.json` `"version"`**  
CLI and the app both `json_decode` the file. One field.  
Trade-off: Composer documents this field for **packages**. This repo is `type: project` named `laravel/laravel`. A bump rewrites the root manifest for a number the package manager never publishes. Not how this app stores app config today.

**B — Root `VERSION` file + `config('app.version')` reading it** (recommended)  
Single-line file at repo root, contents `1.2.0` (no `v`). CLI reads/writes that file. `config/app.php` adds `'version' => …` by reading `base_path('VERSION')` (trim; if missing, `'0.0.0'`). The app, Blade, and the CLI all go through `config('app.version')` / the file.  
Trade-off: new file (investigation confirmed none exists). `config:cache` freezes the value at cache time — already true of every `config/app.php` key; they already `config:clear` after deploy.

**C — `config/app.php` string only, or `env('APP_VERSION')`**  
CLI regex-edits `config/app.php`, or operators edit `.env` on the server.  
Trade-off: env is invisible to git and does not ride `git pull`. Editing `config/app.php` mixes a generated number into a hand-maintained file.

**Rejected:** git tag as the only source (`git describe` from PHP). Shared-hosting checkout may not be a full repo at request time; tagging would still need a file/DB for the UI.

### Locked

- Source of truth in git: **`VERSION`** (semver `MAJOR.MINOR.PATCH`, no `v`).
- App read path: **`config('app.version')`**, backed by that file, **not** `env()`.
- DB `releases.version` stores the same `1.2.0` string (B3). Footer and What's New headings prefix **`v`** for display (`v1.2.0`).
- Do not add `version` to `composer.json` or `package.json`.

---

## B2. Release CLI (`app:release`)

Manual at release time (investigation: no deploy hook). Run on the operator machine that already pushes `origin` and already runs artisan against landlord (same pattern as live plan backfills).

### Approaches for “what git does”

**A — Tag current HEAD only, do not commit `VERSION`**  
Tag `v1.2.0` on whatever commit is HEAD. File/DB can disagree with the tagged tree. Footer after `git pull` still shows the old file.

**B — Commit `VERSION`, tag that commit, push branch + tag** (recommended)  
The tagged object contains the version string the app will display after pull.

### Command behavior (recommended)

Signature: `php artisan app:release {--dry-run} {--initial : first tag; see below}`.

Preflight (abort, no writes):

- Working tree clean (except `--dry-run`).
- `origin` reachable for the push step (skip reachability on `--dry-run`).

Pipeline:

1. **Last tag.** If any `v*` semver tags exist, use the highest `vMAJOR.MINOR.PATCH`. If **none** and `--initial` was not passed: **abort** (`--initial` is required for the first tag). Do not treat “no tags” as “changelog since the beginning of the repo.”
2. **First release (`--initial`).** No tags today. **Do not** dump 83 `feat` + 35 `fix` from the whole history into What's New. `--initial` writes `VERSION` = `1.0.0`, one `releases` row, **zero** `changelog_entries` (the page shows `v1.0.0` and the date only). Then tags `v1.0.0`. Later runs are mechanical. (Part C if a seeded first blurb is wanted.)
3. **Subsequent runs.** `git log <last-tag>..HEAD` with subject **and** body so `BREAKING CHANGE:` footers are visible. If that range is empty: abort “nothing to release.”
4. **Bump** (locked):
   - `BREAKING CHANGE:` in the body **or** `type!:` / `type(scope)!:` in the subject → **major**
   - else any strict `feat:` / `feat(scope):` → **minor**
   - else → **patch** (`fix`, `chore`, `test`, `docs`, `ci`, `refactor`, `style`, `build`, revert, `Fix:`, free-form, everything)
5. **Changelog membership** (locked, with the perf/refactor call):  
   Include **only** strict lowercase `feat:` / `feat(scope):` and `fix:` / `fix(scope):`.  
   **Do not include** `perf:` or `refactor:`. This repo has **zero** `perf:` commits. The three `refactor:` commits are internal indirection (`CheckModule` helper, pharmacy pricing/stock helpers) — not customer copy.  
   Also exclude: `chore`, `test`, `ci`, `docs`, `Fix:` (capital F), `ui(roles):`, free-form. Those still **affect the bump** when they are the only commits (patch), but they do not become What's New lines.
   Map: `feat` → category `added`; `fix` → category `fixed`.
6. **Format (mechanical):** strip `^(feat|fix)(\([^)]+\))?: `, then uppercase the first character of the remainder. No grammar fix, no scope in the visible line. Example: `feat(doctor-share): add rates matrix HTTP with empty-versus-zero cells` → `Add rates matrix HTTP with empty-versus-zero cells`. If the commit also tripped the major rule, prefix `Breaking: ` after that capitalize step.
7. **Write:** `VERSION` file; landlord `releases` row (`version`, `released_at` = now); `changelog_entries` rows in log order. `--dry-run` prints bump + formatted lines and writes nothing.
8. **Git:** `git add VERSION`; commit subject `chore: release vX.Y.Z` (strict conventional, excluded from future changelogs by type); annotated tag **`vX.Y.Z`**; `git push origin HEAD` and `git push origin vX.Y.Z`.

### Tag format

**`v1.0.0`**, not `1.0.0`. Common convention (GitHub, Composer tags for Laravel itself). File and DB stay unprefixed `1.0.0` so semver comparison is trivial.

### Empty changelog after a patch bump

A window of only `chore:` / `test:` still creates `vX.Y.Z` and a `releases` row with **no** entries. What's New shows the version and date; categories with zero rows are omitted. No placeholder sentence.

### Ordering vs `git pull`

CLI writes **live landlord DB** (this environment already does that) **and** commits `VERSION` for the pull. What's New rows can appear a few minutes before the server pull. Footer still shows the **old** `VERSION` until pull + `config:clear`. Accept that skew; do not add a second “sync on deploy” command this pass (C2).

### Not in the command

- `npm run build`, tenant migrate, `tenants:sync-permissions` (already manual after pull).
- GitHub Release notes API.
- Rewriting old changelog rows.

---

## B3. Schema

Landlord, not tenant. Same class of data as `pages` / `site_settings`: one product, every hospital.

Do **not** use the tenant `notifications` table (wrong shape, unused, name collision with Laravel’s database notifications).

### Approaches

**A — Two tables as specified** (recommended)

`releases`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `version` | string, unique | `1.2.0`, no `v` |
| `released_at` | timestamp | CLI sets now |
| timestamps | | existing convention |

`changelog_entries`

| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `release_id` | FK `releases.id` cascade delete | |
| `category` | string | `added` \| `fixed` only (check constraint or app validation) |
| `description` | text | formatted subject |
| `sort_order` | unsignedInt | preserve git log order within the release |
| timestamps | | |

**B — JSON column on `releases` (`entries: [{category, description}]`)**  
Fewer joins. This app prefers real columns for list data (`contact_messages`, `pages`). Skip.

**C — Tenant copies of the tables**  
Would duplicate the same notes per hospital. Skip.

### Conventions to match

- Path: `database/migrations/landlord/YYYY_MM_DD_HHMMSS_create_releases_tables.php`
- `Schema::connection('landlord')->create(...)` like `pages` / `contact_messages` (not the older unscoped `plans` migration).
- Models `App\Models\Release` and `App\Models\ChangelogEntry` with `protected $connection = 'landlord'` (same as `Page` / `ContactMessage`, not `UsesLandlordConnection` — those are tenancy machinery).
- `Release hasMany ChangelogEntry`; `ChangelogEntry belongsTo Release`.
- `$fillable` + `released_at` / `sort_order` casts. No `Auditable` (platform content, not tenant PHI).
- Unique `version`. No slug; URL is a single index page, not per-release permalinks this pass.

Fits existing landlord create-table style. No Spatie, no tenant migrate.

---

## B4. What's New page

Authenticated **read** of landlord `Release::query()->orderByDesc('released_at')->with(['changelogEntries' => ordered by sort_order])`.

Per release: heading `v{version}` + `released_at` in the tenant timezone (already `SetTenantTimezone`). Then grouped lists: **Added** (`added`) then **Fixed** (`fixed`). Omit an empty group. Newest release first.

### Route

- Tenant: inside the existing `tenant` + `auth` group in `routes/web.php`, `GET /whats-new`, name `whats-new.index`. **No** `permission:`, **no** `module:`, **no** `ModuleRegistry` entry (A1: that is how Dashboard stays ungated).
- Super-admin: `GET` under the existing super-admin prefix, name `super-admin.whats-new.index`, same listing (operators see the same notes). Shared view partial or one controller depending on layout extend; both layouts get the footer in B6.

Not a public central route. Documentation stays the public marketing doc; What's New is for people already in the product.

### Where it is linked (admin)

### Approaches

**A — Sidebar item**  
Would sit next to Dashboard/modules. Sidebar is module + Spatie gated (`SidebarService`) plus a hardcoded Subscription exception. Adding a third special-case Blade block repeats the Subscription drift. Do not.

**B — Header user dropdown** (Profile / Settings / Logout)  
Settings is **account/config**, permission-gated. What's New is not a personal setting. Do not mix it there.

**C — Thin admin footer, version is the link** (recommended)  
Investigation: admin has no footer, so this **adds** chrome, but it is the slot the request asked for and it does not invent a nav type. Sticky/bottom of `admin.layout` (and `super-admin.layout`): small muted `v{config('app.version')}` linking to What's New. Always visible, no extra “What's New” sidebar label required. On the What's New page the same footer can be plain text (already here) or still a link — either is fine; keep it a link for consistency.

Do **not** add a landing-footer “What's New” this pass (C3). Documentation remains the only product-info link on the public site.

---

## B5. Entitlement

### Approaches

**A — New plan slug `whats-new` / catalog child**  
Sellable. Wrong product: this is not a billable module. Starter hospitals would 403. CheckModule would need a registry entry.

**B — Spatie `view whats-new`**  
Receptionist/Doctor would hide unless seeded. Transparency would depend on role backfill. Repeats Backup’s Layer-2 trap.

**C — Ungated like Dashboard** (recommended)  
No slug, no Spatie, CheckModule allow-by-no-match. `SidebarService` does not list it (footer is the affordance). Every authenticated tenant user on every plan, and every super-admin, can open it.

Starter vs enterprise: same page, same notes. Notes that mention a paid module are still readable (the feature itself stays gated elsewhere).

---

## B6. Version display

Locked to B4-C: **admin + super-admin footer**, unobtrusive (`text-xs` muted), `v` + `config('app.version')`, link `route('whats-new.index')` / `route('super-admin.whats-new.index')`.

Not in print layouts, not in POS, not in public marketing footers this pass. Public copyright line stays year-only.

If `VERSION` is missing (fresh clone before first release), show `v0.0.0` and still link (empty list until `--initial`).

---

# Part C — Open questions

These can change B. None of them re-open the investigation facts.

**C1. First-release copy.** Proposed: `--initial` → `v1.0.0`, date, **no** generated lines (history is too mixed to dump). Want a single manual sentence instead (“Initial numbered release of UseClinicSync.”)? Or `--include-history` to generate from all strict `feat`/`fix`?

**C2. DB vs pull skew.** Proposed: accept What's New rows existing on landlord a few minutes before `git pull`. Alternative: CLI writes only git (`VERSION` + a committed JSON) and production upserts DB on first What's New request. Heavier; only needed if the skew is unacceptable.

**C3. Public landing link.** Proposed: not this pass. Add a landing-footer What's New later (unauthenticated, same landlord query)?

**C4. Breaking category.** Proposed: no third group; `Breaking: ` prefix on an Added/Fixed line. Want a separate “Breaking” group?

**C5. Branch guard.** Proposed: no hard `main`-only check (operator sometimes releases from a fast-forwarded local main). Require `main`?

---

# Self-review

- Version storage rejects `composer.json` for an unpublished Laravel app; `env()` rejected because pull does not update `.env`.
- Tag `v1.0.0` vs file/DB `1.0.0` is explicit.
- Changelog filter is feat/fix only; perf/refactor excluded with the actual commit list, not a guess.
- Schema is landlord + `Schema::connection('landlord')` like pages, not tenant `notifications`.
- What's New is Dashboard-shaped ungated, not a catalog slug; link is a new footer because no admin footer/nav convention already points at product notes.
- No implementation plan, no code, no “TBD” except numbered C1–C5.
