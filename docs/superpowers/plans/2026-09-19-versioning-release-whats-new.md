# Versioning, Release CLI, and What's New Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> Do **not** start execution until a human confirms this plan. Do **not** merge any branch into `main` until the user explicitly asks — and only after every task is implemented, tested, and free of known bugs.
>
> **New feature branch:** create `feat/versioning-whats-new` from current HEAD **before Task 1**. Do not work on `main`.
>
> **Phase-boundary reports (override SDD “don’t pause” at these points only):**
> 1. After Task 1 (`VERSION` + `config('app.version')`).
> 2. After Task 2 (landlord `releases` / `changelog_entries`).
> 3. After Task 4 (`app:release` including bump/filter/format + git preflight). Tasks 3 and 4 are one phase; do not report between them.
> 4. After Task 5 (What's New tenant + super-admin).
> 5. After Task 6 (footer indicator on both layouts).
> 6. After Task 7 (full regression). No merge.

**Goal:** Give UseClinicSync a committed semver, a manual `app:release` command that tags git and writes landlord changelog rows, and an ungated What's New page linked from a small admin/super-admin footer version.

**Architecture:** Root `VERSION` file is the git source of truth; `config('app.version')` reads it. Pure PHP `SemverBumper` / `ChangelogCompiler` decide the bump and customer lines. `app:release` writes the file + landlord rows, then commits/tags/pushes through a `ReleaseGit` port (real Process in production, Fake in tests — **never** run real `git tag`/`git push` inside Pest). What's New reads landlord `Release` records. CheckModule stays quiet because `whats-new.*` is not in `ModuleRegistry`.

**Tech Stack:** Laravel 12, Pest, landlord SQLite in tests / MySQL in production, Symfony Process for git, existing `admin.layout` / `super-admin.layout`.

**Spec:** `docs/superpowers/specs/2026-09-19-versioning-release-whats-new-design.md` (Part B + confirmed C1–C5 below). Investigation (do not re-open): `docs/superpowers/specs/2026-09-19-versioning-and-release-process-investigation.md`.

## Global Constraints

- **No `composer.json` / `package.json` `version` field.** Do not add `APP_VERSION` to `.env.example`. Version is not `env()`.
- **`VERSION` file** at repo root: one line `MAJOR.MINOR.PATCH` with no `v`. Display and git tags prefix `v`. DB stores unprefixed `1.2.0`.
- **Tag format (verbatim):** `v1.0.0` (annotated). File/DB: `1.0.0`.
- **Bump (verbatim):** `BREAKING CHANGE:` in the body **or** `type!:` / `type(scope)!:` → **major**; else any strict `feat:` / `feat(scope):` (optional `!` already handled as major) → **minor**; else → **patch**. Patch includes `fix`, `chore`, `test`, `docs`, `ci`, `refactor`, `perf`, `style`, `build`, `revert`, `Fix:`, free-form, `chore: release …`.
- **Changelog visibility (verbatim, different rule):** only strict lowercase `feat:` / `feat(scope):` / `fix:` / `fix(scope):` (optional `!` before `:`). Map feat → `added`, fix → `fixed`. Exclude `refactor`, `perf`, `chore`, `test`, `ci`, `docs`, `Fix:`, `ui(roles):`, free-form. These two rules must not be implemented as one `if`.
- **Format (verbatim):** strip `^(feat|fix)(\([^)]+\))?!?: `, then uppercase the first character of the remainder. If breaking, prefix `Breaking: ` after that capitalize. No grammar rewrite. No scope in the visible line.
- **`--initial` blurb (C1, verbatim):** `Initial versioned release of UseClinicSync.` Stored on `releases.summary`. **Zero** `changelog_entries`. Version `1.0.0`, tag `v1.0.0`. No tags + no `--initial` → abort. `--initial` when a semver tag already exists → abort.
- **Do not dump git history** into the first release.
- **Working tree must be clean** before any write (except `--dry-run`, which writes nothing).
- **`--dry-run`:** print bump + lines; do not write `VERSION`, DB, commit, tag, or push.
- **No `main`-only guard (C5).** No public landing What's New (C3). Accept DB-ahead-of-pull skew (C2). No third “Breaking” group (C4) — inline prefix only.
- **Landlord only.** `Schema::connection('landlord')`. Models `protected $connection = 'landlord'` like `Page`. Do **not** use tenant `notifications`. Do **not** add a `ModuleRegistry` slug or Spatie name.
- **Ungated like Dashboard.** `whats-new.*` has no `permission:` / `module:` middleware. CheckModule allows it because it will not match any `routes[]` prefix.
- **Tests never run real git tag/push** against this repo. Bind `FakeReleaseGit`. Command tests that touch `base_path('VERSION')` must restore it in `afterEach`.
- **Do not run live landlord migrate** in this plan. Pest sqlite only. Do not run `app:release` against origin.
- Tests: `php artisan test --compact`. Add `'Feature/Release'` to `tests/Pest.php` tenant-migration `in()` list in Task 2 (schema/HTTP tests live there). `tests/Unit/Services` already has TestCase.
- Force-add `docs/superpowers/` (`git add -f`). Do not merge to `main`.
- PHP 8.2. Artisan command name: `app:release`.
- Out of scope: GitHub Releases API, deploy script, POS layout, print layouts, public copyright line, super-admin changelog editor, email blast, dismissible modal.

## Confirmed Part C (baked in)

| ID | Decision |
|---|---|
| C1 | `--initial` writes the **fixed** summary `Initial versioned release of UseClinicSync.` — not empty, not hand-authored per release |
| C2 | Accept What's New DB rows existing shortly before `git pull` |
| C3 | No public landing What's New this pass |
| C4 | `Breaking: ` prefix on Added/Fixed; no third group |
| C5 | No hard `main`-only check |

## File map

**Create:**

- `VERSION`
- `app/Support/AppVersion.php`
- `database/migrations/landlord/2026_09_19_000001_create_releases_tables.php`
- `app/Models/Release.php`
- `app/Models/ChangelogEntry.php`
- `app/Support/Release/CommitMessage.php`
- `app/Support/Release/SemverBumper.php`
- `app/Support/Release/ChangelogCompiler.php`
- `app/Support/Release/ReleaseGit.php` (interface)
- `app/Support/Release/ProcessReleaseGit.php`
- `tests/Support/FakeReleaseGit.php` (namespace `Tests\Support`; Pest binds it over `ReleaseGit`)
- `app/Console/Commands/AppReleaseCommand.php`
- `app/Http/Controllers/WhatsNewController.php`
- `app/Http/Controllers/SuperAdmin/WhatsNewController.php`
- `resources/views/whats-new/_list.blade.php`
- `resources/views/admin/whats-new/index.blade.php`
- `resources/views/super-admin/whats-new/index.blade.php`
- `resources/views/partials/version-footer.blade.php`
- `tests/Unit/Services/SemverBumperTest.php`
- `tests/Unit/Services/ChangelogCompilerTest.php`
- `tests/Feature/Release/ReleasesSchemaTest.php`
- `tests/Feature/Commands/AppReleaseCommandTest.php`
- `tests/Feature/Release/WhatsNewPageTest.php`
- `tests/Feature/Release/VersionFooterTest.php`
- `tests/Support/FakeReleaseGit.php`

**Modify:**

- `config/app.php` — add `version` (and only that app-version key)
- `tests/Pest.php` — add `'Feature/Release'` to the `in()` list in Task 2
- `routes/web.php` — tenant `whats-new.index`; super-admin `super-admin.whats-new.index`
- `resources/views/admin/layout.blade.php`
- `resources/views/super-admin/layout.blade.php`

**Do not modify:** `composer.json`, `package.json`, `ModuleRegistry`, `PermissionRegistry`, `SidebarService`, tenant `notifications` migration, landing footer, POS layout.

**Shared types (lock now; every later task uses these names):**

```php
namespace App\Support\Release;

final class CommitMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body = '',
    ) {}
}

interface ReleaseGit
{
    public function isWorkingTreeClean(): bool;

    /** Highest existing tag like v1.2.0, or null if none. */
    public function latestSemverTag(): ?string;

    /** @return list<CommitMessage> */
    public function commitsSince(?string $tag): array;

    /** Commit VERSION, annotated tag v{$version}, push HEAD and that tag. */
    public function commitVersionAndTag(string $version): void;
}
```

`SemverBumper::bump(string $current, array $commits): string` — `$commits` is `list<CommitMessage>`.  
`ChangelogCompiler::entries(array $commits): array` — list of `['category' => 'added'|'fixed', 'description' => string]`.  
`AppVersion::path(): string`, `AppVersion::read(): string`, `AppVersion::write(string $version): void`.  
`AppReleaseCommand::INITIAL_BLURB = 'Initial versioned release of UseClinicSync.'`  
`AppReleaseCommand` signature: `app:release {--dry-run} {--initial}`.

---

### Task 1: Feature branch, VERSION file, `config('app.version')`

**Files:**

- Create: `VERSION` (contents exactly `0.0.0` plus a trailing newline)
- Create: `app/Support/AppVersion.php`
- Create: `tests/Unit/Services/AppVersionTest.php`
- Modify: `config/app.php` — add `'version'` immediately after `'name'`

**Interfaces:**

- Consumes: `base_path('VERSION')`.
- Produces: `AppVersion::path()`, `read()`, `write(string $version): void`. `config('app.version')` equals `AppVersion::read()` at config load. Missing/empty file → `0.0.0`. `write` overwrites the file with `{semver}\n`. Do not validate semver in this task beyond what the test asserts (`1.2.3` round-trip).

- [ ] **Step 1: Create the feature branch**

```bash
git checkout -b feat/versioning-whats-new
```

Expected: on `feat/versioning-whats-new`, not `main`.

- [ ] **Step 2: Write the failing tests**

`tests/Unit/Services/AppVersionTest.php`:

```php
<?php

use App\Support\AppVersion;

it('reads the VERSION file as config app.version', function () {
    expect(config('app.version'))->toBe(AppVersion::read())
        ->and(AppVersion::read())->toMatch('/^\d+\.\d+\.\d+$/');
});

it('writes a new semver into the VERSION file', function () {
    $path = AppVersion::path();
    $backup = is_file($path) ? file_get_contents($path) : null;

    try {
        AppVersion::write('1.2.3');
        expect(trim((string) file_get_contents($path)))->toBe('1.2.3');
    } finally {
        if ($backup === null) {
            @unlink($path);
        } else {
            file_put_contents($path, $backup);
        }
    }
});
```

These fail because `AppVersion` does not exist and `config('app.version')` is null.

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact tests/Unit/Services/AppVersionTest.php`

Expected: FAIL (class not found / version key missing).

- [ ] **Step 4: Write VERSION, helper, and config**

`VERSION` file at repo root:

```
0.0.0
```

`app/Support/AppVersion.php`:

```php
<?php

namespace App\Support;

final class AppVersion
{
    public static function path(): string
    {
        return base_path('VERSION');
    }

    public static function read(): string
    {
        $path = self::path();
        if (! is_file($path)) {
            return '0.0.0';
        }

        $value = trim((string) file_get_contents($path));

        return $value === '' ? '0.0.0' : $value;
    }

    public static function write(string $version): void
    {
        file_put_contents(self::path(), $version."\n");
    }
}
```

In `config/app.php`, immediately after the `'name'` entry, add:

```php
    'version' => \App\Support\AppVersion::read(),
```

Do not add `version_file` / env keys.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Unit/Services/AppVersionTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add VERSION app/Support/AppVersion.php config/app.php tests/Unit/Services/AppVersionTest.php
git add -f docs/superpowers/plans/2026-09-19-versioning-release-whats-new.md docs/superpowers/specs/2026-09-19-versioning-release-whats-new-design.md docs/superpowers/specs/2026-09-19-versioning-and-release-process-investigation.md
git commit -m "feat: add VERSION file and config app.version"
```

**Phase 1 report:** `VERSION` is `0.0.0`; `config('app.version')` reads it; tests that ran and passed.

---

### Task 2: Landlord `releases` and `changelog_entries`

**Files:**

- Create: `database/migrations/landlord/2026_09_19_000001_create_releases_tables.php`
- Create: `app/Models/Release.php`
- Create: `app/Models/ChangelogEntry.php`
- Create: `tests/Feature/Release/ReleasesSchemaTest.php`
- Modify: `tests/Pest.php` — add `'Feature/Release'` to the tenant-migration `in()` list

**Interfaces:**

- Consumes: landlord sqlite test harness (same `beforeEach` migrate as `tests/Feature/Commands/SyncPlanModulesTest.php`).
- Produces: tables on connection `landlord`:
  - `releases`: `id`, `version` string unique, `summary` nullable text, `released_at` timestamp, timestamps
  - `changelog_entries`: `id`, `release_id` FK cascade, `category` string, `description` text, `sort_order` unsignedInteger default 0, timestamps
- Model `Release`: `$connection = 'landlord'`, fillable `version`, `summary`, `released_at`; casts `released_at` => `datetime`; `hasMany` `changelogEntries()`.
- Model `ChangelogEntry`: `$connection = 'landlord'`, fillable `release_id`, `category`, `description`, `sort_order`; `belongsTo` `release()`.

- [ ] **Step 1: Add Feature/Release to Pest `in()` and write the failing schema test**

`tests/Feature/Release/ReleasesSchemaTest.php`:

```php
<?php

use App\Models\ChangelogEntry;
use App\Models\Release;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);
});

it('creates landlord releases and changelog_entries tables', function () {
    expect(Schema::connection('landlord')->hasTable('releases'))->toBeTrue()
        ->and(Schema::connection('landlord')->hasColumns('releases', [
            'id', 'version', 'summary', 'released_at', 'created_at', 'updated_at',
        ]))->toBeTrue()
        ->and(Schema::connection('landlord')->hasTable('changelog_entries'))->toBeTrue()
        ->and(Schema::connection('landlord')->hasColumns('changelog_entries', [
            'id', 'release_id', 'category', 'description', 'sort_order', 'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('stores a release with summary and no entries', function () {
    $release = Release::create([
        'version' => '1.0.0',
        'summary' => 'Initial versioned release of UseClinicSync.',
        'released_at' => now(),
    ]);

    expect($release->changelogEntries()->count())->toBe(0)
        ->and($release->summary)->toBe('Initial versioned release of UseClinicSync.');
});

it('stores added and fixed changelog entries for a release', function () {
    $release = Release::create([
        'version' => '1.1.0',
        'summary' => null,
        'released_at' => now(),
    ]);
    ChangelogEntry::create([
        'release_id' => $release->id,
        'category' => 'added',
        'description' => 'Add rates matrix HTTP with empty-versus-zero cells',
        'sort_order' => 0,
    ]);
    ChangelogEntry::create([
        'release_id' => $release->id,
        'category' => 'fixed',
        'description' => 'Scope rate sync deletes to submitted doctors',
        'sort_order' => 1,
    ]);

    expect($release->changelogEntries()->orderBy('sort_order')->pluck('category')->all())
        ->toBe(['added', 'fixed']);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Release/ReleasesSchemaTest.php`

Expected: FAIL (table missing / class not found).

- [ ] **Step 3: Write migration and models**

Migration `up()`:

```php
Schema::connection('landlord')->create('releases', function (Blueprint $table) {
    $table->id();
    $table->string('version')->unique();
    $table->text('summary')->nullable();
    $table->timestamp('released_at');
    $table->timestamps();
});

Schema::connection('landlord')->create('changelog_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('release_id')->constrained('releases')->cascadeOnDelete();
    $table->string('category');
    $table->text('description');
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```

`down()`: drop `changelog_entries` then `releases` on the landlord connection.

Models as in Interfaces. No `Auditable`. No tenant connection.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Release/ReleasesSchemaTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/landlord/2026_09_19_000001_create_releases_tables.php app/Models/Release.php app/Models/ChangelogEntry.php tests/Feature/Release/ReleasesSchemaTest.php tests/Pest.php
git commit -m "feat: add landlord releases and changelog_entries tables"
```

**Phase 2 report:** landlord tables + models; tests that ran and passed. No live migrate.

---

### Task 3: Bump vs changelog compiler (pure; the correctness surface)

**Files:**

- Create: `app/Support/Release/CommitMessage.php`
- Create: `app/Support/Release/SemverBumper.php`
- Create: `app/Support/Release/ChangelogCompiler.php`
- Create: `tests/Unit/Services/SemverBumperTest.php`
- Create: `tests/Unit/Services/ChangelogCompilerTest.php`

**Interfaces:**

- Consumes: `list<CommitMessage>`.
- Produces: `SemverBumper::bump(string $current, array $commits): string`. `ChangelogCompiler::entries(array $commits): array`. Shared private-or-public helper for “is breaking” may live on `SemverBumper` as `SemverBumper::isBreaking(CommitMessage $commit): bool` and be called from the compiler so bang/`BREAKING CHANGE:` is defined once.

**These two rules are separate classes (or at least separate public methods) and separate test files.** Do not implement changelog filtering inside `SemverBumper` or bump math inside `ChangelogCompiler`.

- [ ] **Step 1: Write the failing bump tests**

`tests/Unit/Services/SemverBumperTest.php` — helper in the file:

```php
<?php

use App\Support\Release\CommitMessage;
use App\Support\Release\SemverBumper;

function c(string $subject, string $body = ''): CommitMessage
{
    return new CommitMessage($subject, $body);
}

it('bumps minor for feat and patch for every other non-breaking type', function () {
    expect(SemverBumper::bump('1.0.0', [c('feat: add matrix')]))->toBe('1.1.0')
        ->and(SemverBumper::bump('1.0.0', [c('feat(nav): hide rules link')]))->toBe('1.1.0')
        ->and(SemverBumper::bump('1.2.3', [c('fix: scope deletes')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('chore: ship vite build')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('test: assert hr child gates')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('docs: add catalog plan')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('ci: drop the GitHub Actions workflow')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('refactor: CheckModule uses planAllows')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('perf: cache sidebar')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('style: format pint')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('build: bump vite')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('revert: undo experiment')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('Fix: restore LabOrderItem::hasParameters')]))->toBe('1.2.4')
        ->and(SemverBumper::bump('1.2.3', [c('Ship pending auth work.')]))->toBe('1.2.4');
});

it('lets feat win over patch-only types in the same window', function () {
    expect(SemverBumper::bump('1.0.0', [
        c('chore: ignore build output'),
        c('feat: add VERSION file'),
        c('test: cover footer'),
    ]))->toBe('1.1.0');
});

it('bumps major for BREAKING CHANGE footer or bang type', function () {
    expect(SemverBumper::bump('1.4.2', [
        c('feat: drop old slug', "BREAKING CHANGE: finance slug is gone"),
    ]))->toBe('2.0.0')
        ->and(SemverBumper::bump('1.4.2', [c('feat!: remove rules CRUD')]))->toBe('2.0.0')
        ->and(SemverBumper::bump('1.4.2', [c('fix(nav)!: hide unentitled cells')]))->toBe('2.0.0');
});
```

- [ ] **Step 2: Write the failing changelog tests (separate file, separate rule)**

`tests/Unit/Services/ChangelogCompilerTest.php`:

```php
<?php

use App\Support\Release\ChangelogCompiler;
use App\Support\Release\CommitMessage;

function line(string $subject, string $body = ''): CommitMessage
{
    return new CommitMessage($subject, $body);
}

it('includes only strict feat and fix subjects after prefix-strip capitalize', function () {
    $entries = ChangelogCompiler::entries([
        line('feat(doctor-share): add rates matrix HTTP with empty-versus-zero cells'),
        line('fix: scope rate sync deletes to submitted doctors'),
        line('chore: ship Vite production build'),
        line('test: assert hr child plan gates on HTTP'),
        line('docs: add unified feature catalog plan'),
        line('ci: drop the GitHub Actions Vite freshness workflow'),
        line('refactor: CheckModule uses ModuleRegistry::planAllows'),
        line('perf: cache sidebar'),
        line('Fix: restore LabOrderItem::hasParameters so batch result entry does not 500.'),
        line('Ship pending auth, upload, accounting, and visit workflow work.'),
    ]);

    expect($entries)->toBe([
        [
            'category' => 'added',
            'description' => 'Add rates matrix HTTP with empty-versus-zero cells',
        ],
        [
            'category' => 'fixed',
            'description' => 'Scope rate sync deletes to submitted doctors',
        ],
    ]);
});

it('does not treat patch-only commits as changelog lines even though they bump patch', function () {
    $commits = [
        line('chore: ignore build output'),
        line('refactor(pharmacy): use MedicinePricing in prescription store'),
    ];

    expect(\App\Support\Release\SemverBumper::bump('1.0.0', $commits))->toBe('1.0.1')
        ->and(ChangelogCompiler::entries($commits))->toBe([]);
});

it('prefixes Breaking: on changelog lines that triggered a major bump', function () {
    $commits = [
        line('feat: drop finance slug', "The details.\n\nBREAKING CHANGE: old URLs 404."),
        line('fix!: reject unentitled cells before validation'),
    ];

    expect(\App\Support\Release\SemverBumper::bump('1.0.0', $commits))->toBe('2.0.0')
        ->and(ChangelogCompiler::entries($commits))->toBe([
            [
                'category' => 'added',
                'description' => 'Breaking: Drop finance slug',
            ],
            [
                'category' => 'fixed',
                'description' => 'Breaking: Reject unentitled cells before validation',
            ],
        ]);
});
```

The middle test is the **conflation proof**: same commit list, bump uses all types, entries stay feat/fix-only (here: empty). The first changelog test proves feat/fix survive while other types vanish. Do not merge these into one `it()`.

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact tests/Unit/Services/SemverBumperTest.php tests/Unit/Services/ChangelogCompilerTest.php`

Expected: FAIL (classes not found).

- [ ] **Step 4: Implement bumper and compiler**

`CommitMessage` as in the file map.

`SemverBumper::isBreaking`: subject matches `/^[a-z]+(\([^)]+\))?!: /` **or** body contains `BREAKING CHANGE:`.

`SemverBumper::bump`: start from parsed `$current` (`explode('.', $current)` three integers). Severity starts at patch. For each commit: if breaking → major; else if subject matches `/^feat(\([^)]+\))?!?: /` → at least minor. Then increment: major → `($major+1).0.0`; minor → `$major.($minor+1).0`; patch → `$major.$minor.($patch+1)`.

`ChangelogCompiler::entries`: foreach commit, if subject matches `/^(feat|fix)(\([^)]+\))?!?: /`, remainder = `preg_replace` that prefix (including optional `!`), capitalize first character (use `strtoupper($rest[0]).substr($rest, 1)` on the trimmed remainder; skip empty remainder). If `SemverBumper::isBreaking($commit)`, prefix `Breaking: `. Category from the captured type. Preserve input order. Do not look at chore/fix-capital/etc.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Unit/Services/SemverBumperTest.php tests/Unit/Services/ChangelogCompilerTest.php`

Expected: PASS (all examples above).

- [ ] **Step 6: Commit**

```bash
git add app/Support/Release tests/Unit/Services/SemverBumperTest.php tests/Unit/Services/ChangelogCompilerTest.php
git commit -m "feat: compile semver bumps and feat-fix changelog lines"
```

No phase report (same phase as Task 4).

---

### Task 4: `app:release` command (preflight, `--initial`, git port)

**Files:**

- Create: `app/Support/Release/ReleaseGit.php`
- Create: `app/Support/Release/ProcessReleaseGit.php`
- Create: `tests/Support/FakeReleaseGit.php`
- Create: `app/Console/Commands/AppReleaseCommand.php`
- Create: `tests/Feature/Commands/AppReleaseCommandTest.php`
- Bind `ReleaseGit` → `ProcessReleaseGit` in `AppServiceProvider::register`

**Interfaces:**

- Consumes: Task 1 `AppVersion`, Task 2 models, Task 3 bumper/compiler, `ReleaseGit`.
- Produces: `php artisan app:release {--dry-run} {--initial}` exit codes `SUCCESS`/`FAILURE`.
- `FakeReleaseGit` (namespace `Tests\Support`): public `$clean = true`, `$latestTag = null`, `$commits = []`, `$tagged = []` (list of version strings passed to `commitVersionAndTag`).
- `ProcessReleaseGit` runs git in `base_path()` via `Symfony\Component\Process\Process`. `isWorkingTreeClean`: `git status --porcelain` empty. `latestSemverTag`: `git tag -l v[0-9]*` and pick highest semver (`v1.10.0` > `v1.9.0`). `commitsSince`: `git log {$tag}..HEAD` or `git log` if tag null (command must not call this on the no-tag abort path). Format: subject then body separated so both reach `CommitMessage`. `commitVersionAndTag`: `git add VERSION`; `git commit -m "chore: release v{$version}"`; `git tag -a v{$version} -m "v{$version}"`; `git push origin HEAD`; `git push origin v{$version}`.
- Command order: (1) if not dry-run and tree dirty → error `Working tree must be clean.` FAILURE, no writes. (2) `$tag = latestSemverTag()`. (3) `--initial` and `$tag !== null` → error `Already has a release tag; omit --initial.` (4) no `--initial` and `$tag === null` → error `No release tags exist. Re-run with --initial.` **Do not** read commits or dump history. (5) `--initial`: write VERSION `1.0.0`; `Release::create` version `1.0.0`, summary `AppReleaseCommand::INITIAL_BLURB`, `released_at` now; **zero** changelog rows; unless dry-run, `commitVersionAndTag('1.0.0')`. (6) subsequent: `commitsSince($tag)`; if empty → `Nothing to release.` FAILURE. Current version = tag without leading `v`. Bump via `SemverBumper`. Entries via `ChangelogCompiler`. Write VERSION, create `Release` with `summary` null, create entries with `sort_order` 0..n-1, `commitVersionAndTag`. (7) `--dry-run` at any successful path: print planned version + formatted lines; skip file, DB, git writes.

- [ ] **Step 1: Write the failing command tests**

`tests/Support/FakeReleaseGit.php` implements `ReleaseGit` with the public fields above.

`tests/Feature/Commands/AppReleaseCommandTest.php`:

```php
<?php

use App\Console\Commands\AppReleaseCommand;
use App\Models\Release;
use App\Support\AppVersion;
use App\Support\Release\CommitMessage;
use App\Support\Release\ReleaseGit;
use Tests\Support\FakeReleaseGit;

beforeEach(function () {
    config([
        'database.connections.landlord' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    $this->app['db']->purge('landlord');
    $this->artisan('migrate', [
        '--path' => 'database/migrations/landlord',
        '--database' => 'landlord',
    ]);

    $this->versionBackup = is_file(AppVersion::path()) ? file_get_contents(AppVersion::path()) : null;
    $this->git = new FakeReleaseGit;
    $this->app->instance(ReleaseGit::class, $this->git);
});

afterEach(function () {
    if ($this->versionBackup === null) {
        @unlink(AppVersion::path());
    } else {
        file_put_contents(AppVersion::path(), $this->versionBackup);
    }
});

it('creates the initial 1.0.0 release with the fixed blurb and no changelog entries', function () {
    $this->git->clean = true;
    $this->git->latestTag = null;

    $this->artisan('app:release', ['--initial' => true])->assertSuccessful();

    $release = Release::query()->sole();
    expect($release->version)->toBe('1.0.0')
        ->and($release->summary)->toBe(AppReleaseCommand::INITIAL_BLURB)
        ->and($release->changelogEntries()->count())->toBe(0)
        ->and(trim((string) file_get_contents(AppVersion::path())))->toBe('1.0.0')
        ->and($this->git->tagged)->toBe(['1.0.0']);
});

it('aborts when no tags exist and --initial was not passed', function () {
    $this->git->latestTag = null;

    $this->artisan('app:release')->assertFailed();

    expect(Release::count())->toBe(0)
        ->and($this->git->tagged)->toBe([])
        ->and(trim((string) file_get_contents(AppVersion::path())))->toBe(trim($this->versionBackup));
});

it('aborts when the working tree is dirty', function () {
    $this->git->clean = false;
    $this->git->latestTag = null;

    $this->artisan('app:release', ['--initial' => true])->assertFailed();

    expect(Release::count())->toBe(0)->and($this->git->tagged)->toBe([]);
});

it('writes feat and fix entries on a subsequent release and tags the bumped version', function () {
    Release::create([
        'version' => '1.0.0',
        'summary' => AppReleaseCommand::INITIAL_BLURB,
        'released_at' => now()->subDay(),
    ]);
    $this->git->latestTag = 'v1.0.0';
    $this->git->commits = [
        new CommitMessage('feat: add VERSION file and config app.version'),
        new CommitMessage('chore: ignore build output'),
        new CommitMessage('fix: scope rate sync deletes to submitted doctors'),
    ];

    $this->artisan('app:release')->assertSuccessful();

    $release = Release::query()->where('version', '1.1.0')->sole();
    expect($release->summary)->toBeNull()
        ->and($release->changelogEntries()->orderBy('sort_order')->pluck('description')->all())->toBe([
            'Add VERSION file and config app.version',
            'Scope rate sync deletes to submitted doctors',
        ])
        ->and($this->git->tagged)->toBe(['1.1.0']);
});

it('does not write file db or git on dry-run', function () {
    $this->git->latestTag = null;

    $this->artisan('app:release', ['--initial' => true, '--dry-run' => true])->assertSuccessful();

    expect(Release::count())->toBe(0)
        ->and($this->git->tagged)->toBe([])
        ->and(trim((string) file_get_contents(AppVersion::path())))->toBe(trim($this->versionBackup));
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Commands/AppReleaseCommandTest.php`

Expected: FAIL (command / interface missing).

- [ ] **Step 3: Implement interface, fake, ProcessReleaseGit, command, container bind**

`AppServiceProvider::register`: `$this->app->singleton(ReleaseGit::class, ProcessReleaseGit::class);`

Command `handle(ReleaseGit $git): int` follows the order in Interfaces. Print the new version on success. On `--initial` dry-run, still print `1.0.0` and the blurb text.

`ProcessReleaseGit` must pass `base_path()` as the process cwd. If a git command fails, throw `RuntimeException` with stderr (command catches and returns FAILURE). Tests must not instantiate `ProcessReleaseGit`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Commands/AppReleaseCommandTest.php tests/Unit/Services/SemverBumperTest.php tests/Unit/Services/ChangelogCompilerTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Support/Release app/Console/Commands/AppReleaseCommand.php app/Providers/AppServiceProvider.php tests/Support/FakeReleaseGit.php tests/Feature/Commands/AppReleaseCommandTest.php
git commit -m "feat: add app:release for semver tags and changelog rows"
```

**Phase 3 report:** bump tests (all types) + changelog tests (feat/fix only) + `--initial` / abort / dirty / subsequent / dry-run. Confirm fake git, not real tags. Confirm `VERSION` restored after tests.

---

### Task 5: What's New route and views (tenant + super-admin)

**Files:**

- Create: `app/Http/Controllers/WhatsNewController.php`
- Create: `app/Http/Controllers/SuperAdmin/WhatsNewController.php`
- Create: `resources/views/whats-new/_list.blade.php`
- Create: `resources/views/admin/whats-new/index.blade.php`
- Create: `resources/views/super-admin/whats-new/index.blade.php`
- Create: `tests/Feature/Release/WhatsNewPageTest.php`
- Modify: `routes/web.php`

**Interfaces:**

- Tenant: inside existing `Route::middleware('tenant')->group` → `Route::middleware('auth')` group, next to dashboard: `Route::get('/whats-new', [WhatsNewController::class, 'index'])->name('whats-new.index');` No extra middleware.
- Super-admin: inside `middleware('super_admin')` group: `Route::get('/whats-new', [SuperAdmin\WhatsNewController::class, 'index'])->name('whats-new.index');` (full name `super-admin.whats-new.index`).
- Both controllers: `Release::query()->orderByDesc('released_at')->with(['changelogEntries' => fn ($q) => $q->orderBy('sort_order')])->get()` passed as `$releases`.
- `_list.blade.php`: foreach release, heading `v{{ $release->version }}` and `released_at` formatted `M j, Y`. If `filled($release->summary)`, a paragraph with the summary. Then if any `added` entries, `<h3>Added</h3><ul>…`; same for `fixed`. Omit empty groups.
- Tenant view extends `admin.layout` with `@section('content')` including `_list`. Super-admin view extends `super-admin.layout` the same way. Page titles: `What's New`.
- Do **not** add `ModuleRegistry` routes or Spatie names.

- [ ] **Step 1: Write the failing HTTP tests**

`tests/Feature/Release/WhatsNewPageTest.php` — copy the landlord `beforeEach` migrate from Task 2. Also `withoutMiddleware` `EnsureTenantActive` and `SetTenantTimezone` (same as ungated tests). Create a `User` like `ungatedUser([])` (no extra permissions). Create a `SuperAdmin` like `PlanModuleFormTest`. Seed two releases: `1.1.0` (null summary, one added “Add VERSION file and config app.version”, one fixed “Scope rate sync deletes to submitted doctors”) with later `released_at`; `1.0.0` (summary = `AppReleaseCommand::INITIAL_BLURB`, no entries) with earlier `released_at`.

Tests:

1. `it('lists releases newest first for any authenticated tenant user')` — `actingAs($user)` GET `route('whats-new.index')` 200; see `v1.1.0` before `v1.0.0`; see `Added`, `Add VERSION file and config app.version`, `Fixed`, `Scope rate sync deletes to submitted doctors`, and `Initial versioned release of UseClinicSync.`; do not require any permission name.
2. `it('is reachable on a tenant whose plan has no extra modules')` — `ungatedTenant([])` then same GET 200 (proves CheckModule allow-by-no-match). Reuse `ungatedTenant` from `UngatedUiVisibilityTest` only if that helper is already in Pest.php; otherwise duplicate a 10-line mock in this file. Do not add a module slug to make this pass.
3. `it('redirects guests to login')` — GET 302 to login.
4. `it('lists the same releases for a super admin')` — `actingAs($superAdmin, 'super_admin')` GET `route('super-admin.whats-new.index')` 200; see `v1.1.0` and the blurb.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Release/WhatsNewPageTest.php`

Expected: FAIL (route not defined).

- [ ] **Step 3: Implement routes, controllers, views**

Keep controllers thin: query + return view. Shared `_list` only.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Release/WhatsNewPageTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/WhatsNewController.php app/Http/Controllers/SuperAdmin/WhatsNewController.php resources/views/whats-new resources/views/admin/whats-new resources/views/super-admin/whats-new routes/web.php tests/Feature/Release/WhatsNewPageTest.php
git commit -m "feat: add ungated What\'s New page for tenants and super-admins"
```

If the escaped apostrophe is painful on Windows, use: `feat: add ungated Whats New page for tenants and super-admins`.

**Phase 4 report:** both routes 200 without module/Spatie; guest redirect; newest-first + blurb + groups.

---

### Task 6: Footer version indicator on both admin layouts

**Files:**

- Create: `resources/views/partials/version-footer.blade.php`
- Create: `tests/Feature/Release/VersionFooterTest.php`
- Modify: `resources/views/admin/layout.blade.php` — include the partial as the last child of `<body>` before the closing `</body>` (after the existing mobile-menu script)
- Modify: `resources/views/super-admin/layout.blade.php` — same, after the mobile-menu script

**Interfaces:**

- Partial (verbatim structure):

```blade
<footer class="lg:ml-64 px-3 sm:px-4 md:px-6 py-3 text-center text-xs text-gray-400">
    <a href="{{ $whatsNewUrl }}" class="hover:text-gray-600">v{{ config('app.version') }}</a>
</footer>
```

- Tenant layout sets `$whatsNewUrl = route('whats-new.index')` when `auth()->check()`; skip the include when guest.
- Super-admin layout sets `$whatsNewUrl = route('super-admin.whats-new.index')` when `auth('super_admin')->check()`.
- Do not add the footer to landing, POS, or print views.

- [ ] **Step 1: Write the failing footer tests**

`tests/Feature/Release/VersionFooterTest.php` — same landlord migrate + `withoutMiddleware` tenant active/timezone as Task 5. `config(['app.version' => '1.2.3'])` in `beforeEach`.

1. `it('shows a version link to whats-new in the tenant admin layout')` — `ungatedTenant([])`, `actingAs` a user with no extra perms, GET `route('dashboard')` 200, `assertSee('v1.2.3')`, `assertSee(route('whats-new.index'), false)`.
2. `it('shows a version link to whats-new in the super-admin layout')` — actingAs super_admin, GET `route('super-admin.dashboard')` 200, see `v1.2.3` and `route('super-admin.whats-new.index')`.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Release/VersionFooterTest.php`

Expected: FAIL (v1.2.3 not in HTML).

- [ ] **Step 3: Add the partial and includes**

Match Interfaces. Keep classes muted/`text-xs`. `lg:ml-64` so it lines up with main content, not under the sidebar.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Release/VersionFooterTest.php tests/Feature/Release/WhatsNewPageTest.php`

Expected: PASS. Confirm dashboard still 200 (no layout regression).

- [ ] **Step 5: Commit**

```bash
git add resources/views/partials/version-footer.blade.php resources/views/admin/layout.blade.php resources/views/super-admin/layout.blade.php tests/Feature/Release/VersionFooterTest.php
git commit -m "feat: link admin footers to Whats New via the app version"
```

**Phase 5 report:** both layouts show `v{config}` linking to the matching What's New route.

---

### Task 7: Regression + stop (no merge)

**Files:** none new.

**Interfaces:** Consumes Tasks 1–6. Produces: full suite green; phase report; branch unmerged.

- [ ] **Step 1: Run the new tests together**

Run: `php artisan test --compact tests/Unit/Services/AppVersionTest.php tests/Unit/Services/SemverBumperTest.php tests/Unit/Services/ChangelogCompilerTest.php tests/Feature/Release tests/Feature/Commands/AppReleaseCommandTest.php`

Expected: PASS.

- [ ] **Step 2: Run the full suite**

Run: `php artisan test --compact`

Expected: PASS (report the counts). If anything fails, fix on this branch with TDD; do not merge.

- [ ] **Step 3: Confirm VERSION restore and no stray tags**

Run: `git status --short`; `git tag -l`; `git diff VERSION`

Expected: `VERSION` still `0.0.0` (or whatever Task 1 committed); **no new git tags** on this repo from tests; no uncommitted `VERSION` mutation.

- [ ] **Step 4: Do not merge, do not run live `app:release`, do not live-migrate landlord**

Stop. Phase 6 report: suite counts, HEAD hash, branch name, reminder that `--initial` against origin/live landlord is **operator** work after the user asks.

No commit unless Step 2 required a fix; if so, `fix:` conventional commit then re-run Step 2.

---

# Self-review

- Spec B1 VERSION+config → Task 1. B3 schema including C1 `summary` → Task 2. B2 bump/filter/format → Task 3 (two files so bump vs visibility cannot be one assertion). B2 CLI preflight/`--initial`/tag `vX.Y.Z`/push via port → Task 4. B4–B5 What's New ungated → Task 5. B6 footer → Task 6. Regression → Task 7.
- Required tests present: `--initial` one row + fixed blurb + zero entries; abort without `--initial` when no tags; bump all types vs changelog feat/fix (separate tests + mixed-window proof); BREAKING/`!` major + `Breaking: ` prefix; dirty working tree.
- No `composer.json` version, no tenant `notifications`, no `ModuleRegistry` slug, no real git tag in Pest, no live migrate, no merge.
- `FakeReleaseGit` path locked to `tests/Support/FakeReleaseGit.php` (not `app/`).
- `AppReleaseCommand::INITIAL_BLURB` is the single source for the C1 sentence.
- No TBD. No “similar to Task N” without repeating the needed setup.
