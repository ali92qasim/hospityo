# Versioning and release-process investigation

**Date:** 2026-09-19  
**Status:** Findings only. No design, no code, no implementation plan.  
**Method:** Superpowers brainstorming context exploration (Phase 0). `composer.json`, `package.json`, `config/app.php`, `.env.example`, public/admin/super-admin Blade layouts and footers, `routes/api.php`, git tags (local + `origin`), last 100 and all 288 commit subjects, README Deployment, `.github/workflows`, artisan commands, notifications schema, and prior session evidence that production is shared hosting.

**Related**

- User request 2026-09-19: versioning / tagging / changelog / What's New — investigation before design.
- `docs/superpowers/follow-ups/2026-09-08-vite-ci-build-freshness.md` — GitHub Actions Vite check was added then dropped because shared hosting does not use Actions.
- README Deployment section (queue, scheduler, post-permission artisan commands). No pull/FTP/script.

**Out of this pass:** semver policy, tag command, changelog format, What's New UI, or tying a bump to deploy. No confirmation questions.

Live git snapshot: branch `main`, remote `origin` = `https://github.com/ali92qasim/hospityo.git`, 288 commits, 0 tags.

---

# Verdict

There is **no application version** anywhere the product currently uses. `composer.json` has no `version` field. There is no `VERSION` file, no `APP_VERSION` / `config('app.version')`, and no `v1.0.0`-style string in footers, admin, super-admin, or API JSON.

There are **zero git tags** locally and on `origin`. Tagging would be introduced from scratch.

Commit messages in the last 100 are **mostly** Conventional Commits (`feat:` / `fix:` / …, sometimes scoped). That is recent practice, not the whole history: 90/100 last commits are strict lowercase type-prefix; the full 288-commit history is 138/288 strict.

Production reach is **not automated**. There is no deploy script and no GitHub Actions workflow on `main`. Shared hosting was stated as the reason Actions were removed. Code reaches production by landing on `origin/main`, then an operator `git pull` on the server (SSH), with `npm run build` / artisan migrate / cache commands as manual after-steps.

There is **no changelog, release-notes mechanism, or What's New UI**. The tenant `notifications` table is leftover schema with no model and no writers. Near-expiry mail + dashboard banner and session flash/toasts are operational alerts, not release notes.

---

# 1. Current versioning state

## 1.1 Package / config sources

| Location | App version present? | What is there |
|---|---|---|
| `composer.json` | **No** `version` field | Laravel skeleton metadata (`name`: `laravel/laravel`). Dependency constraints only (`laravel/framework`: `^12.0`, etc.) |
| `package.json` | **No** `version` field | `"private": true`, `"type": "module"`, scripts `build` / `check-build` / `dev` |
| `VERSION` file (repo root or elsewhere in app tree) | **No** | None. Recurse found vendor/node_modules changelogs only |
| `config/app.php` | **No** | `name`, `env`, `debug`, `url`, locale, key, maintenance. No `version` key |
| `.env.example` | **No** `APP_VERSION` | — |
| Artisan / helpers / `window.appConfig` | **No** | Admin layout exposes `currency`, `csrf`, `timezone`, `dateFormat`, `timeFormat`, `timezoneAutoSet`, `detectTimezoneUrl` |

`docker-compose.yml` has Compose file `version: "3.8"` — that is the Compose schema version, not the product.

## 1.2 UI surfaces that could show a version

| Surface | Version text? | What it actually shows |
|---|---|---|
| Marketing / auth footers (`landing`, `contact`, `page`, `documentation`, `central-login`, `tenant/register`) | No | `© {{ date('Y') }} UseClinicSync. All rights reserved.` |
| Tenant admin (`resources/views/admin/layout.blade.php` + `partials/header` + `partials/sidebar`) | No | No footer strip. Header is page title + language + user menu |
| Super-admin layout / header / sidebar | No | Sidebar subtitle is the word “Super Admin”, not a version |
| Print layouts (bills, IPD, payslip) | No | Hospital/letterhead footers, not product version |
| Public documentation page | No | Module how-to copy. No version badge |
| `php artisan about` | N/A (Laravel framework about) | Not wired into the product UI |

Grep of Blade/PHP/JS for `v1.`, `v0.`, `UseClinicSync v`, `Hospityo v`, `APP_VERSION`, `app.version`: **no product matches**.

## 1.3 API

`routes/api.php` has two endpoints:

- `GET /user` (Sanctum) — returns the user model
- `GET /patients/search?phone=` (session auth) — `{ found, patient: { id, name, patient_no, phone } }`

Neither includes a version field. No `Accept-Version` / URL `/v1/` prefix in `routes/`.

---

# 2. Git tag history

| Check | Result |
|---|---|
| `git tag -l` (local) | **empty** — count 0 |
| `git show-ref --tags` | empty |
| `git ls-remote --tags origin` | **empty** — count 0 |

**No tags exist.** There is no semver pattern, no `v1.0.0`, no date tags, no environment tags. Introducing tags would be from scratch, not a migration of an existing scheme.

Remote: `origin` → `https://github.com/ali92qasim/hospityo.git`. No tag objects to hang GitHub Releases on.

---

# 3. Commit message convention audit

Total history: **288** commits. Sample requested: last **100** subjects (whole project, not this session only).

Convention used for “strict”: `^(feat|fix|chore|ci|docs|test|refactor|perf|style|build|revert)(\([^)]+\))?: ` (lowercase type, optional scope, colon-space).  
“Loose extra”: same types with **wrong case**, almost all `Fix: …`.

## 3.1 Last 100

| Bucket | Count | % of 100 |
|---|---|---|
| Strict Conventional Commits | 90 | **90%** |
| `Fix:` (capital F, otherwise same shape) | 4 | 4% |
| Free-form imperative / prose (no type prefix) | 6 | 6% |

Strict type breakdown (90): `feat` 55, `fix` 21, `test` 8, `ci` 2, `docs` 2, `chore` 1, `refactor` 1. No `perf` / `style` / `build` / `revert` in this window.

Scoped among the last 100: **16 / 100** (all of them on the 90 strict). Scopes actually used:

| Count | type(scope) |
|---|---|
| 6 | `feat(doctor-share)` |
| 6 | `fix(doctor-share)` |
| 2 | `feat(catalog)` |
| 2 | `feat(nav)` |

The six free-form subjects in the last 100:

- `Ship pending auth, upload, accounting, and visit workflow work.`
- `Fixes in admited patients, appointment filter calendar and flash images duplication fixed`
- `Keep import file inputs native so Import buttons open the file picker.`
- `Stop tracking Vite public/build output so npm run build stays out of git.`
- `Fix purchase order create crashing with a Blade parse error.` (`Fix` + space, no colon)
- `Build and node_modules directory added`

**Not consistent 100%.** Dominant in this window: lowercase `feat:` / `fix:` / `test:` / `ci:` / `docs:` / `chore:` / `refactor:`. Scope is optional and rare outside the doctor-share / catalog / nav work. `Fix:` still appears.

## 3.2 Whole history (288)

| Bucket | Count | % of 288 |
|---|---|---|
| Strict lowercase type-prefix | 138 | **47.9%** |
| Loose extra (`Fix:` and other case variants) | 82 | 28.5% |
| Type-prefix combined (strict + loose) | 220 | 76.4% |
| Free-form / other | 68 | 23.6% |

Strict types across all 288: `feat` 83, `fix` 35, `test` 10, `chore` 3, `refactor` 3, `ci` 2, `docs` 2.

Oldest commits are untyped paragraphs (`Patient, Departments, Doctors, … modules are ready`). `feat:` / `Fix:` start appearing around 2026-03-05 (`6acee48 feat: Backup, Audit logs, multi-lingual added and fixed some bugs`). One near-conventional outlier in the free-form set: `ui(roles): grouped permission checkboxes from PermissionRegistry` — scoped, but `ui` is not a Conventional Commits type.

**Do not assume consistency from recent agent commits.** Last 100 is 90% strict; full history is under 50% strict.

---

# 4. Deploy process

## 4.1 What the repo contains

| Mechanism | Present? |
|---|---|
| Deploy script (`deploy.sh`, Envoy, Forge recipe, GitHub `deploy.yml`) | **No** |
| GitHub Actions on `main` | **No tracked workflows.** `.github/workflows/` exists as an empty directory. History: `ea7b50c` added `vite-build-freshness.yml`, `3ec6838` removed it |
| `composer.json` scripts | `setup`, `dev`, `test`, `check-build` — local/dev, not production deploy |
| `scripts/` | `check-vite-build-freshness.mjs`, `generate-favicons.php` — not deploy |
| `docker-compose.yml` | Local app/nginx/mysql on ports 8000/3307. Not the production path |
| README “Deployment” | Server requirements, `queue:work`, cron `schedule:run`, then `tenants:sync-permissions` + `cache:clear` **after** permission changes. No git/FTP/rsync steps |

## 4.2 How code actually reaches production

Evidence (repo + prior operator statements), not a script:

1. **Ship gate is `origin/main`.** Operator instruction 2026-09-11: put the code on `main` “so that I can deploy it right now.” Rebase-to-main, no merge commit, is the in-repo publication step.
2. **Shared hosting, no Actions.** Operator 2026-09-08: revert the Vite freshness workflow — “shared hosting doesn't need or benefit from it.” Commit `3ec6838` message: “shared hosting does not use Actions.”
3. **Server update is `git pull`.** Session 2026-08-29: after pushing Vite assets to `main`, “a `git pull` on the server gets the editor” / “After you pull this commit on production.” Session 2026-08-24 cutover notes assumed “SSH + `git pull`.”
4. **After-pull is manual.** Repeated operator-facing notes: run `npm run build` on the server (assets are gitignored again after 2026-09-11), plus artisan migrate / `config:clear` / permission sync as needed. README lists those artisan commands; nothing runs them automatically.

There is **no hook that can bump a version as part of an automated production step.** A version bump would have to be a **manual CLI (or commit) at release time**, then the same pull-on-server sequence.

CI that still exists is local-only: `npm run check-build` / `composer check-build`.

---

# 5. Changelog / What's New / reuse candidates

## 5.1 Changelog / release notes

| Candidate | Exists as release mechanism? |
|---|---|
| Root `CHANGELOG.md` / `CHANGELOG.txt` / `RELEASES.md` | **No** |
| `docs/` product changelog | **No** (superpowers specs/plans/qa only; `docs/` is gitignored) |
| GitHub Releases | None possible without tags; remote tags empty |
| Super-admin Pages / `PageSeeder` | CMS for legal/marketing pages. Service-policy copy *claims* “Major feature releases … communicated via email and/or in-app notifications at least 7 days in advance.” That sentence is policy text, not an implemented feature |
| Site settings keys | Office address/phone/email/hours and social URLs only. No version / notes key |

## 5.2 In-app “What's New” / dismissible product news

| Surface | What it is | Serves What's New? |
|---|---|---|
| Tenant `notifications` table (`2026_01_26_140001_create_notifications_table.php`) | Custom columns: `user_id`, `title`, `message`, `type`, `data`, `read_at`. **Not** Laravel’s uuid/morph `notifications` table | **No writers, no model, no UI, no tests.** Dead schema |
| `User` `Notifiable` + `NearExpiryMedicineAlert` | Mail only. Comment: “Add `database` here in the future to store in-app notifications.” | Operational pharmacy alert, not releases |
| Dashboard near-expiry banner | Entitled-pharmacy stock warning | No |
| Trial-ending banner (`admin/layout`) | Subscription trial | No |
| `partials/alerts` + Toast flash | CRUD success/error; super-admin pending module-grant prompt | Request-scoped, not a news feed |
| `components/modal.blade.php`, confirm-dialog, appointment/OT/backup modals | Feature dialogs | No product-news modal |
| Bell icon / unread product-news inbox | **None** (super-admin Messages badge is `ContactMessage` unread count) | No |

No `changelog`, `release_notes`, `whats_new`, `announcements`, or `last_seen_version` table/column in app migrations or models.

**Clean slate** for a changelog or What's New UI. The leftover `notifications` table is the only name collision: same table name as Laravel database notifications, different schema, unused.

---

# Out of scope (explicit)

No recommended version scheme, tag command, changelog location, or What's New design. That is the next pass.
