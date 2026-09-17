# Doctor Share Rates and Settings Child Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.
>
> Do **not** start execution until a human confirms this plan. Do **not** merge any branch into `main` until the user explicitly asks — and only after every task is implemented, tested, and free of known bugs.
>
> **New feature branch:** create `feat/doctor-share-rates-settings` from current HEAD **before Task 1**. Do not work on `main`.
>
> **Phase-boundary reports (override SDD “don’t pause” at these points only):**
> 1. After Task 3 (schema + Hassan live copy + history fingerprint) — **HARD STOP.** Do not start Task 4 (calculate switch) until the user confirms the Hassan before/after. Herd serves this working tree against live tenant DBs; switching calculate before the copy would write new bills against an empty rates table.
> 2. After Task 5 (IPD `primaryDoctor()`).
> 3. After Task 6 (pharmacy POS hook).
> 4. After Task 9 (all three report fixes).
> 5. After Task 12 (Settings child + C1 tests). Do **not** run live landlord `plans.modules` migrate until the user says so at this checkpoint.
> 6. After Task 16 (matrix UI + operational sidebar + regression).

**Goal:** Replace live doctor-share rule config with per-doctor category percentages (`doctor_share_rates`), freeze historical share rows untouched, attribute IPD shares via `primaryDoctor()`, share pharmacy **lines**, fix the report, and sell the module as `settings.doctor-share`.

**Architecture:** Parallel live table `doctor_share_rates`; archive `doctor_share_rules` + pivot remain write-frozen. `DoctorShareService` resolves `(doctor_id, item_category)` then `general`. Historical `doctor_share_items` / allocations / settlements / `rule_snapshot` are never updated by this program. Catalog: remove Finance slug `doctor-share`, C1-add `settings.doctor-share` only onto plans that already have `doctor-share`.

**Tech Stack:** Laravel 12, Pest, Spatie Permission, `CheckModule`, `SidebarService`, `ModuleRegistry`, `PermissionRegistry`, `SettingsAccess`, `SettingsSectionRegistry`, `PlanSeeder`.

**Spec:** `docs/superpowers/specs/2026-09-17-doctor-share-rates-and-settings-design.md` (GATE + Part B + confirmed C1–C6 below). Investigation (do not re-open): `docs/superpowers/specs/2026-09-17-doctor-share-module-investigation.md`.

## Global Constraints

- **History is immutable.** Never `UPDATE`/`DELETE` `doctor_share_items`, `doctor_share_allocations`, `doctor_share_settlements`, or rewrite `rule_snapshot` / `share_amount` / `rule_id`. Every task that runs a migrator, `calculate()`, checkout, or report **must** include a fingerprint assertion that those tables (and archive `doctor_share_rules` + `doctor_share_rule_service`) are byte-for-byte unchanged except where a test **creates new** item rows (then only the new ids differ).
- **Parallel table only.** Do not reshape or drop `doctor_share_rules` / the pivot (C5). New table name: `doctor_share_rates`.
- **Categories (verbatim):** `general`, `opd`, `ipd`, `emergency`, `lab`, `imaging`, `pharmacy`. Six service columns + general (confirmed C1).
- **Hassan OPD widen (C6):** catalog-item rule (service 18, 70% OPD) copies to `(doctor_id, opd, 70)` with no second OPD rate to reconcile.
- **C2:** POS walk-in with no `visit_id` skips share. **C3:** operational sidebar group stays separate from Settings matrix. **C4:** empty cell = no rate row (fall through); stored `0.00` = explicit zero.
- **C1 catalog JSON:** keyed off existing slug `doctor-share`, **not** off `settings`. Starter and professional must not receive `settings.doctor-share`.
- **IPD:** change only `DoctorShareService::resolveDoctorId`. Do not change freeze columns or snapshot writer for that task except that **new** items may use the new snapshot shape after Task 3.
- **Pharmacy:** per-line `item_category = pharmacy`. Hook `PharmacyPosController::checkout`. Not a `bill_type` allow-list.
- **Report:** three separate TDD tasks (collected scope, `withSum`, `bill_items.item_category` filter). Drop `investigation`. Query param stays `bill_type` (less Blade churn) but it matches **line** `item_category`.
- `planAllows()` stays exact JSON. `normalize()` must **not** auto-add `settings.doctor-share` when `settings` is present. `plans:sync-modules` must **not** append this child.
- Additive provisioner: never `syncPermissions`. Do not revoke Spatie on slug remove.
- PHP 8.2: do not use `self::CONST` inside `ModuleRegistry` `$modules` initializer.
- Tests: `php artisan test --compact`. Add `Feature/DoctorShare` to `tests/Pest.php` tenant-migration `in()` list in Task 1. Put `fingerprintDoctorShareHistory()` in the Pest.php Functions section (same style as `otCatalogTenant()`), not a `require_once` helper file.
- Force-add `docs/superpowers/` (`git add -f`). Do not merge to `main`.
- **Never run live tenant migrate until Task 3.** Tasks 1–2 are Pest sqlite only. There is no `tenants:artisan` command in this app — live tenant migrate is `Tenant::all()` → `makeCurrent()` → `Artisan::call('migrate', ['--path' => 'database/migrations/tenant', '--database' => 'tenant', '--force' => true])` (same as `MigrateTenantDatabase`).
- Live **tenant** migrate for `doctor_share_rates` **is** Task 3 (Hassan is a paying clinic). Task 4 (calculate reads rates) must not start until Task 3 hashes match. Live **landlord** C1 JSON migrate is **not** run until the user confirms at the Phase 5 checkpoint.
- `PermissionRegistry`: `settings.doctor-share` = rules names + `manage doctor shares` only (`SettingsAccess` / provisioner). Keep items / settlements / reports groups on a PermissionRegistry key `doctor-share` that is **not** a ModuleRegistry slug, so the role form still lists them and `forModule('settings.doctor-share')` stays rules-only.
- Out of scope: settlement GL 5350/2250, dropping archive tables, `bills.doctor_id`, visit CTI HTTP identity, OT/HR catalog work.

## Confirmed Part C (baked in)

| ID | Decision |
|---|---|
| Table | Parallel `doctor_share_rates` (spec Approach B; confirmed as the parallel table) |
| C1 | Six service columns + general |
| C2 | Skip walk-in POS with no visit/doctor |
| C3 | Separate operational sidebar group |
| C4 | Empty vs explicit zero |
| C5 | No archive drop this pass |
| C6 | Hassan OPD 70% widen accepted |

## File map

**Create:**

- `database/migrations/tenant/2026_09_17_120000_create_doctor_share_rates_table.php`
- `app/Models/DoctorShareRate.php`
- `app/Services/DoctorShareRateMigrator.php`
- `tests/Feature/DoctorShare/DoctorShareRatesSchemaTest.php`
- `tests/Feature/DoctorShare/DoctorShareRateMigratorTest.php`
- `tests/Feature/DoctorShare/DoctorShareCalculateRatesTest.php`
- `tests/Feature/DoctorShare/DoctorShareIpdAttributionTest.php`
- `tests/Feature/DoctorShare/DoctorSharePharmacyPosTest.php`
- `tests/Feature/DoctorShare/DoctorShareReportCollectedScopeTest.php`
- `tests/Feature/DoctorShare/DoctorShareReportCollectedColumnTest.php`
- `tests/Feature/DoctorShare/DoctorShareReportCategoryFilterTest.php`
- `tests/Feature/Module/DoctorShareCatalogBackfillTest.php`
- `tests/Feature/DoctorShare/DoctorShareMatrixTest.php`
- `database/migrations/landlord/2026_09_17_120000_backfill_settings_doctor_share_on_plans.php`
- `resources/views/admin/doctor-share/rates/index.blade.php`
- `resources/js/doctor-share-rates-form.js` (and CSS if needed, matching existing rules-form Vite entry pattern)

**Modify:**

- `tests/Pest.php` (`in()` list)
- `app/Services/DoctorShareService.php`
- `app/Http/Controllers/DoctorShareController.php`
- `app/Http/Controllers/PharmacyPosController.php`
- `app/Models/ModuleRegistry.php`
- `app/Support/PermissionRegistry.php`
- `app/Support/SettingsSectionRegistry.php`
- `app/Services/SidebarService.php`
- `database/seeders/PlanSeeder.php`
- `routes/web.php`
- `vite.config.js` (add rates-form entry; remove rules-form only in Task 14)
- `tests/Feature/Billing/BillItemCategoryTest.php`
- `tests/Feature/Permissions/DoctorSharePermissionsTest.php`
- `tests/Feature/Module/ParentRouteEntitlementTest.php`
- `tests/Unit/ModuleRegistryTest.php`
- `tests/Unit/PermissionRegistryTest.php`
- `tests/Feature/SuperAdmin/PlanModuleDefaultsTest.php`
- `tests/Feature/Commands/SyncPlanModulesTest.php` (prove sync does **not** add this child)
- `tests/Feature/Permissions/SidebarVisibilityTest.php` if it asserts the Finance Doctor Share group

**Do not modify:** archive migrations for `doctor_share_rules`; `LabImagingSchemaSplit`; visit `moduleForRequest` guessing; settlement GL; `PlanSeeder` starter appointments/emergency/reports hygiene.

**New snapshot shape (Task 4 onward, new rows only):**

```php
[
    'doctor_id' => int,
    'service_category' => string, // category used after fallback
    'percentage' => string,       // bcmath 2-decimal string
    'source' => 'category'|'general',
]
```

**Fingerprint helper (Task 1, reused verbatim):** SHA-256 of JSON-encoded ordered row arrays from `doctor_share_items`, `doctor_share_allocations`, `doctor_share_settlements`, `doctor_share_rules`, `doctor_share_rule_service`. Compare hashes, not Eloquent casts. Live Task 3 uses the same algorithm inside `makeCurrent()`.

**Hassan expected rates after copy (doctor_id 10):**

| service_category | percentage |
|---|---|
| opd | 70.00 |
| ipd | 40.00 |
| lab | 20.00 |
| imaging | 20.00 |

Archive: 4 rules + 1 pivot unchanged. Items: 22 rows, all `rule_id=1`, snapshots unchanged.

---

### Task 1: Feature branch, rates table, fingerprint helper

**Files:**

- Create: `database/migrations/tenant/2026_09_17_120000_create_doctor_share_rates_table.php`
- Create: `app/Models/DoctorShareRate.php`
- Create: `tests/Feature/DoctorShare/DoctorShareRatesSchemaTest.php`
- Modify: `tests/Pest.php` — add `'Feature/DoctorShare'` to the tenant-migration `in()` list; add `fingerprintDoctorShareHistory()` in the Functions section

**Interfaces:**

- Consumes: existing tenant SQLite test harness.
- Produces: table `doctor_share_rates` (`id`, `doctor_id` FK restrict, `service_category` string 30, `percentage` decimal 8,2, timestamps, unique `(doctor_id, service_category)` named `dsr_rates_doctor_category_unique`). Model `App\Models\DoctorShareRate` (`UsesTenantConnection`), fillable those columns, `percentage` cast `decimal:2`. Const `DoctorShareRate::CATEGORIES = ['general','opd','ipd','emergency','lab','imaging','pharmacy']`. Function `fingerprintDoctorShareHistory(): string` in `tests/Pest.php`.

- [ ] **Step 1: Create the feature branch**

```bash
git checkout -b feat/doctor-share-rates-settings
```

Expected: on `feat/doctor-share-rates-settings`, not `main`.

- [ ] **Step 2: Write the failing schema test**

Add `Feature/DoctorShare` to `tests/Pest.php` `in()` list first so tenant migrations run. Append this next to `otCatalogTenant()` (Pest already imports `DB` usage via facades at call time — add `use Illuminate\Support\Facades\DB;` at the Functions section if needed):

```php
function fingerprintDoctorShareHistory(): string
{
    $tables = [
        'doctor_share_items',
        'doctor_share_allocations',
        'doctor_share_settlements',
        'doctor_share_rules',
        'doctor_share_rule_service',
    ];

    $payload = [];
    foreach ($tables as $table) {
        $payload[$table] = Illuminate\Support\Facades\DB::connection('tenant')->table($table)->orderBy('id')->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    return hash('sha256', json_encode($payload));
}
```

`tests/Feature/DoctorShare/DoctorShareRatesSchemaTest.php`:

```php
<?php

use Illuminate\Support\Facades\Schema;

it('creates doctor_share_rates with unique doctor plus category', function () {
    expect(Schema::connection('tenant')->hasTable('doctor_share_rates'))->toBeTrue()
        ->and(Schema::connection('tenant')->hasColumns('doctor_share_rates', [
            'id', 'doctor_id', 'service_category', 'percentage', 'created_at', 'updated_at',
        ]))->toBeTrue();
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareRatesSchemaTest.php`

Expected: FAIL (`doctor_share_rates` missing). If Pest does not pick up the folder, the `in()` list was not updated.

- [ ] **Step 4: Write the migration and model**

Migration `up()`: `Schema::create` as in Interfaces. Do **not** call the copy migrator yet (Task 2). Do **not** run this migration against live tenant MySQL. `down()`: `dropIfExists('doctor_share_rates')`.

Model: `DoctorShareRate` with `UsesTenantConnection`, fillable, casts, `CATEGORIES`, `belongsTo` Doctor.

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareRatesSchemaTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add tests/Pest.php tests/Feature/DoctorShare app/Models/DoctorShareRate.php database/migrations/tenant/2026_09_17_120000_create_doctor_share_rates_table.php
git add -f docs/superpowers/plans/2026-09-17-doctor-share-rates-and-settings.md
git commit -m "$(cat <<'EOF'
feat(doctor-share): add doctor_share_rates table beside archived rules

EOF
)"
```

---

### Task 2: Hassan-shaped copy migrator (Pest) + history fingerprint

**Files:**

- Create: `app/Services/DoctorShareRateMigrator.php`
- Create: `tests/Feature/DoctorShare/DoctorShareRateMigratorTest.php`
- Modify: `database/migrations/tenant/2026_09_17_120000_create_doctor_share_rates_table.php` — after create, call `DoctorShareRateMigrator::copyFromLegacyRules()` (idempotent no-op when rules empty)

**Interfaces:**

- Consumes: Task 1 table; live archive columns `doctor_id`, `applies_to`, `share_type`, `share_value`, `is_active`.
- Produces: `DoctorShareRateMigrator::copyFromLegacyRules(): int` (rows inserted). Mapping: skip `doctor_id` null, skip `is_active=0`, skip `share_type !== percentage`, skip `applies_to === investigation`. Map `applies_to === 'all'` → `general`; otherwise category = `applies_to` if it is in `DoctorShareRate::CATEGORIES` (excluding `general` unless from `all`). Catalog-item scoped rows **still copy** (C6). If two source rows would write the same `(doctor_id, service_category)` with **different** percentages: throw `RuntimeException` with both values. Same percentage: insert once. Idempotent: existing matching rate left as-is; existing conflicting rate throws. **Never writes** items/allocations/settlements/rules/pivot.

- [ ] **Step 1: Write the failing tests**

`tests/Feature/DoctorShare/DoctorShareRateMigratorTest.php` — `fingerprintDoctorShareHistory()` is already global from Pest.php. Seed a user + doctor + service as in `BillItemCategoryTest`. Reproduce Hassan:

- Rule A: doctor, `applies_to=opd`, 70%, `is_active`, attach pivot service (catalog-item).
- Rule B: doctor, `ipd`, 40%.
- Rule C: doctor, `lab`, 20%.
- Rule D: doctor, `imaging`, 20%.
- One `doctor_share_items` row pointing at rule A with a frozen `rule_snapshot` JSON string containing `"share_value":"70.00"` and `"applies_to":"opd"`.
- One allocation on that item; no settlements.

Tests:

1. `it('copies hassan-shaped rules to rates and widens the catalog-item opd rule')` — after `copyFromLegacyRules()`, four rates as in the Hassan table; pivot and rules still present.
2. `it('does not rewrite historical share rows when copying rates')` — `$before = fingerprintDoctorShareHistory()`; copy; `$after = fingerprintDoctorShareHistory()`; `expect($after)->toBe($before)`.
3. `it('aborts when two legacy rules collapse to different percentages')` — second opd 50% for same doctor; expect exception; fingerprint unchanged.
4. `it('is idempotent when rates already match')` — copy twice, still four rows, fingerprint unchanged.

These fail because `DoctorShareRateMigrator` does not exist.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareRateMigratorTest.php`

Expected: FAIL (class not found).

- [ ] **Step 3: Implement `DoctorShareRateMigrator::copyFromLegacyRules`**

Read `doctor_share_rules` with optional `whereHas`/join to pivot (do not need pivot for the insert). Perform mapping. `DoctorShareRate::query()->create` / `firstOrCreate`. Throw on percentage mismatch. Return insert count.

Call it at the end of the Task 1 migration `up()` so live migrate copies Hassan in Task 3. Tests call the method directly after inserting fixtures (migration already ran on empty DB). Still do **not** run live tenant migrate in this task.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareRateMigratorTest.php tests/Feature/DoctorShare/DoctorShareRatesSchemaTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/DoctorShareRateMigrator.php tests/Feature/DoctorShare/DoctorShareRateMigratorTest.php database/migrations/tenant/2026_09_17_120000_create_doctor_share_rates_table.php
git commit -m "$(cat <<'EOF'
feat(doctor-share): copy legacy rules into doctor_share_rates without touching history

EOF
)"
```

---

### Task 3: Live Hassan copy — before/after report (Phase 1 checkpoint)

**HARD GATE.** Do not start Task 4 (calculate switch) until this report is written and the user confirms. Herd is the live paying-customer stack.

**Files:** none required if Task 2 migrator is in the tenant migration. Optional read-only inspect is inline tinker, **not** committed. Do **not** write dumps that contain connection config or passwords (the 2026-09-17 investigation inspect files were deleted for that reason).

**Interfaces:** Consumes live tenant DBs via Herd landlord `saasy`. Produces a before/after table in the Phase 1 report, same shape as the design-spec GATE table (one row per tenant).

Live tenant slug for the paying clinic: `hassan-health-care-centre`. Doctor id **10**. Other six tenants must stay at 0 rates.

- [ ] **Step 1: Before snapshot (read-only)**

For **every** tenant (`Tenant::all()`, `makeCurrent()`), record:

| column | source |
|---|---|
| slug | `tenants.slug` |
| rules | `count(doctor_share_rules)` |
| pivot | `count(doctor_share_rule_service)` |
| items | `count(doctor_share_items)` |
| allocations | `count(doctor_share_allocations)` |
| settlements | `count(doctor_share_settlements)` |
| rates | `count(doctor_share_rates)` (expect 0 — table missing is also 0) |
| history_sha256 | `fingerprintDoctorShareHistory()` algorithm |

Extra for Hassan only: dump the 4 rules (`id, doctor_id, applies_to, share_value, is_active, share_type`) and `doctor_share_items.rule_id` histogram. Expected before: 4 rules, 1 pivot, 22 items all `rule_id=1`, 21 allocations, 0 settlements.

Do **not** migrate yet.

- [ ] **Step 2: Migrate tenant schemas**

There is no `tenants:artisan` in this app. Loop every tenant:

```php
foreach (App\Models\Tenant::all() as $tenant) {
    $tenant->makeCurrent();
    Illuminate\Support\Facades\Artisan::call('migrate', [
        '--path' => 'database/migrations/tenant',
        '--database' => 'tenant',
        '--force' => true,
    ]);
    App\Models\Tenant::forgetCurrent();
}
```

Run that from tinker or a one-off that is **not** committed with secrets. This creates `doctor_share_rates` and runs `copyFromLegacyRules()`. Empty tenants stay empty. Hassan gets four rates.

- [ ] **Step 3: After snapshot and compare**

| Check | Expected |
|---|---|
| Hassan rates | exactly 4 rows: opd 70.00, ipd 40.00, lab 20.00, imaging 20.00, `doctor_id=10` |
| Hassan history_sha256 | **identical** to before (items, allocations, settlements, rules, pivot) |
| Hassan items | still 22, all `rule_id=1`, each `rule_snapshot` string unchanged |
| Other six tenants rates | 0 |
| Other six history_sha256 | identical to before |
| `doctor_share_rules` | still 4 on Hassan, 0 elsewhere |
| Pivot | still 1 on Hassan |

If **any** history hash drifted: **stop**. Do not start Task 4. Rollback of the copy = `DELETE FROM doctor_share_rates` only — never touch items. Schema table may remain.

- [ ] **Step 4: Report to the user (Phase 1 boundary)**

Paste:

1. Per-tenant before/after counts + hashes (seven rows).
2. Hassan rule dump (before) vs rate dump (after).
3. Explicit line: history hashes matched / failed.
4. Explicit line: calculate still reads `DoctorShareRule` until Task 4.

Then **stop**. Do not start Task 4 until the user confirms.

- [ ] **Step 5: Commit** only if a small inspect command was added; otherwise no commit. Do not commit `.env` or dumps with secrets.

---

### Task 4: Calculate reads rates; new snapshots; history still frozen

**Files:**

- Modify: `app/Services/DoctorShareService.php`
- Modify: `tests/Feature/Billing/BillItemCategoryTest.php` — create `DoctorShareRate` rows instead of `DoctorShareRule` for **new** calculate expectations
- Create: `tests/Feature/DoctorShare/DoctorShareCalculateRatesTest.php`

**Interfaces:**

- Consumes: Task 3 live rates already on Hassan; `DoctorShareRate` in Pest.
- Produces: `DoctorShareService::resolveRate(int $doctorId, string $itemCategory): ?DoctorShareRate` — exact category row, else `general`, else null. Remove pharmacy from `EXCLUDED_BILL_TYPES` in **Task 6**, not here (`isExcluded` still skips `item_category=pharmacy`). `writeItemShare` uses `resolveRate`; `snapshot(DoctorShareRate $rate, string $itemCategory): array` writes the **new** snapshot shape. `resolveRule` may remain unused or be deleted in this task if no callers remain (grep: only this service). Public API `calculate` / `voidForBill` / `recordPaymentAllocations` signatures unchanged. Existing Hassan items keep old snapshot shape forever.

- [ ] **Step 1: Confirm Task 3 is green**

Hassan history hashes matched. If not, **stop**.

- [ ] **Step 2: Write the failing tests**

`DoctorShareCalculateRatesTest.php`:

1. OPD visit + rate `opd` 20% + lab rate 30% on mixed bill (service opd line + lab line) — same numbers as current `BillItemCategoryTest` share case (200 / 150). Insert **only rates**, no rules. Fail until calculate reads rates.
2. Category missing, `general` 10% applies to opd line.
3. `it('does not rewrite existing items when calculating a different bill')`: seed historical item+allocation+archive rule as Task 2; `$before = fingerprintDoctorShareHistory()`; `calculate()` a **new** bill; then delete **only** the newly created share items/allocations for that new bill; `expect(fingerprintDoctorShareHistory())->toBe($before)`. Also compare the old item’s `rule_snapshot`, `share_amount`, `rule_id` by id.
4. New item `rule_snapshot` has keys `doctor_id`, `service_category`, `percentage`, `source` and does **not** require `applies_to`.

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareCalculateRatesTest.php`

Expected: FAIL (no share items from rates).

- [ ] **Step 4: Switch `DoctorShareService` to rates**

Replace `resolveRule` usage in `writeItemShare` with `resolveRate`. New `snapshot()`. Keep `isExcluded` with pharmacy still listed. Update `BillItemCategoryTest` share tests to insert `DoctorShareRate` instead of rules so they pass on the new path.

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/DoctorShare tests/Feature/Billing/BillItemCategoryTest.php`

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/DoctorShareService.php tests/Feature/DoctorShare/DoctorShareCalculateRatesTest.php tests/Feature/Billing/BillItemCategoryTest.php
git commit -m "$(cat <<'EOF'
feat(doctor-share): resolve live percentages from doctor_share_rates

EOF
)"
```

---

### Task 5: IPD `resolveDoctorId` uses `primaryDoctor()` (Phase 2)

**Files:**

- Modify: `app/Services/DoctorShareService.php` (`resolveDoctorId` only)
- Create: `tests/Feature/DoctorShare/DoctorShareIpdAttributionTest.php`

**Interfaces:**

- Consumes: `Visit::primaryDoctor()` → `IpdCareTeam.doctor_id`.
- Produces: `resolveDoctorId`: if `$bill->visit?->visit_type === 'ipd'` return `$bill->visit->primaryDoctor?->doctor_id`; else `$bill->visit?->doctor_id`. Do not change `snapshot()`, `writeItemShare` freeze fields, or `calculate` signature.

- [ ] **Step 1: Write the failing tests**

1. IPD visit with **null** spine `doctor_id`, active primary `IpdCareTeam` for doctor A, `DoctorShareRate` ipd 40%. Bill `bill_type=ipd`, one ipd line. `calculate()` creates a share item with `doctor_id` = A and `share_amount` = 40% of base. **Fails today** (unattributed).
2. OPD visit still uses spine `doctor_id` (care team ignored).
3. `it('leaves freeze fields on existing items untouched when calculating another bill')`: historical item fingerprint / per-column compare on `rule_snapshot`, `share_amount`, `rule_id`, `doctor_id` of the **old** item after calculating the IPD bill. This is the proof that the freeze/snapshot writer is untouched — only `resolveDoctorId` changed.
4. New IPD item still uses the Task 4 snapshot shape (`doctor_id`, `service_category`, `percentage`, `source`) and `share_amount` equals 40% of the line base (same `writeItemShare` math).

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareIpdAttributionTest.php`

Expected: FAIL (no share item on IPD).

- [ ] **Step 3: Change only `resolveDoctorId`**

```php
private static function resolveDoctorId(Bill $bill): ?int
{
    $bill->loadMissing(['visit.primaryDoctor']);
    $visit = $bill->visit;
    if ($visit === null) {
        return null;
    }
    if ($visit->visit_type === 'ipd') {
        return $visit->primaryDoctor?->doctor_id;
    }
    return $visit->doctor_id;
}
```

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/DoctorShare tests/Feature/Billing/BillItemCategoryTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/DoctorShareService.php tests/Feature/DoctorShare/DoctorShareIpdAttributionTest.php
git commit -m "$(cat <<'EOF'
fix(doctor-share): attribute IPD bills via primary care-team doctor

EOF
)"
```

- [ ] **Step 6: Phase 2 report** — IPD uses `primaryDoctor`; freeze test passed; Hassan history not migrated again.

---

### Task 6: Pharmacy line share + POS checkout (Phase 3)

**Files:**

- Modify: `app/Services/DoctorShareService.php` — remove `EXCLUDED_BILL_TYPES` / `isExcluded` pharmacy skip (delete the skip path)
- Modify: `app/Http/Controllers/PharmacyPosController.php` — **after** the checkout `DB::transaction` commits (after the `try` assigns `$bill`, before the print redirect), call `DoctorShareService::calculate($bill)` then, if that sale created a payment, `DoctorShareService::recordPaymentAllocations($payment)`. Do **not** call calculate inside the POS transaction (share must not roll back stock/accounting). Same non-throwing posture as `BillController::store`.
- Create: `tests/Feature/DoctorShare/DoctorSharePharmacyPosTest.php`
- Modify: `tests/Feature/Pharmacy/PharmacyPosCheckoutTest.php` only if existing assertions break

**Interfaces:**

- Consumes: B2 `resolveDoctorId`; rates `pharmacy` / `general`.
- Produces: pharmacy **lines** participate in `writeItemShare`. Walk-in checkout with `visit_id` null → no share items (C2). Prescription checkout with visit → share using that visit’s OPD/IPD resolution. An OPD **header** bill with `item_category=pharmacy` uses the pharmacy rate, not opd.

- [ ] **Step 1: Write the failing tests**

Reuse POS seed from `PharmacyPosCheckoutTest` (accounts, medicine, stock). Give the doctor a `pharmacy` rate 15%.

1. Walk-in POST to `pharmacy.pos.checkout` with no visit / no prescription — `DoctorShareItem::count()` stays 0. History fingerprint of any pre-seeded historical rows unchanged.
2. Prescription mode: prescription with `visit_id` = OPD visit with spine doctor, `doctor_id` on prescription optional. Checkout creates a share item, `doctor_id` = visit doctor, `share_amount` = 15% of line. New snapshot `source=category`, `service_category=pharmacy`.
3. IPD prescription visit: spine `doctor_id` null, primary care team set — share doctor is primary (B2).
4. Direct `calculate()` on an OPD bill with a pharmacy line + an opd line: pharmacy line uses pharmacy %, opd line uses opd %.
5. Historical fingerprint: pre-inserted archive item unchanged after POS checkout.

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorSharePharmacyPosTest.php`

Expected: FAIL (excluded / no calculate on POS).

- [ ] **Step 3: Implement skip removal + POS hooks**

Delete `EXCLUDED_BILL_TYPES` / `isExcluded` (today `isExcluded` already keys off `item_category`, so pharmacy **lines** on any header are skipped). After the POS transaction returns `$bill`, reload payments if needed and call calculate then allocations. Walk-in with `visit_id` null hits existing `resolveDoctorId === null` and skips — no extra walk-in branch required beyond C2 coverage in the test.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/DoctorShare tests/Feature/Pharmacy/PharmacyPosCheckoutTest.php tests/Feature/Billing/BillItemCategoryTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/DoctorShareService.php app/Http/Controllers/PharmacyPosController.php tests/Feature/DoctorShare/DoctorSharePharmacyPosTest.php
git commit -m "$(cat <<'EOF'
feat(doctor-share): share pharmacy lines and hook POS checkout

EOF
)"
```

- [ ] **Step 6: Phase 3 report**

---

### Task 7: Report Total Collected scoping

**Files:**

- Modify: `app/Http/Controllers/DoctorShareController.php` `buildReportData` summary collected query
- Create: `tests/Feature/DoctorShare/DoctorShareReportCollectedScopeTest.php`

**Interfaces:**

- Consumes: filtered `DoctorShareItem` query (same doctor/date/`bill_type` filters as the summary).
- Produces: `total_collected` = `SUM(allocations.amount)` where `doctor_share_item_id` **in that filtered id set**, not all items for the doctor.

- [ ] **Step 1: Write the failing test**

Bind tenant with `doctor-share` like `DoctorSharePermissionsTest` (catalog swap is Task 10). User with `view share reports`.

Create doctor D, two pending items for D: item Old (created_at 2020-01-01) with allocation 100; item New (today) with allocation 5. GET `doctor-share.reports.index?date_from=today&date_to=today`. Assert the summary collected for D is **5**, not 105.

Also fingerprint history tables unchanged (report is read-only).

- [ ] **Step 2: Run test to verify it fails**

Expected: FAIL (collected 105).

- [ ] **Step 3: Fix the summary map**

Replace the allocations query that uses `DoctorShareItem::where('doctor_id', $row->doctor_id)` with `whereIn('doctor_share_item_id', $filteredIdsForThatDoctor)` from the cloned filtered base query.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareReportCollectedScopeTest.php tests/Feature/Permissions/DoctorSharePermissionsTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DoctorShareController.php tests/Feature/DoctorShare/DoctorShareReportCollectedScopeTest.php
git commit -m "$(cat <<'EOF'
fix(doctor-share): scope report collected totals to the filtered items

EOF
)"
```

---

### Task 8: Report detail Collected column (`withSum`)

**Files:**

- Modify: `app/Http/Controllers/DoctorShareController.php` detail query in `buildReportData`
- Create: `tests/Feature/DoctorShare/DoctorShareReportCollectedColumnTest.php`

**Interfaces:**

- Produces: detail rows have `allocations_sum_amount` populated (same as `itemsIndex`).

- [ ] **Step 1: Write the failing test**

One item, allocation 12.50. GET reports index. `assertSee` the formatted 12.50 in the detail Collected cell (use `number_format` / `currency_symbol()` as the Blade does). Today it shows 0.00.

Read-only fingerprint.

- [ ] **Step 2: Run test to verify it fails**

Expected: FAIL (sees 0.00 / missing 12.50).

- [ ] **Step 3: Add `->withSum('allocations', 'amount')` to the detail query (paginate and print clones).**

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareReportCollectedColumnTest.php tests/Feature/DoctorShare/DoctorShareReportCollectedScopeTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DoctorShareController.php tests/Feature/DoctorShare/DoctorShareReportCollectedColumnTest.php
git commit -m "$(cat <<'EOF'
fix(doctor-share): populate report detail collected from allocations

EOF
)"
```

---

### Task 9: Report filter uses `bill_items.item_category` (Phase 4)

**Files:**

- Modify: `app/Http/Controllers/DoctorShareController.php` — `entitledReportBillTypes` / `abortUnlessReportBillTypeEntitled` / `buildReportData` filter; views `reports/index.blade.php` option loop already uses `$reportBillTypes`
- Modify: `tests/Feature/Module/ParentRouteEntitlementTest.php` report `bill_type=emergency` cases still valid (emergency is still a category)
- Create: `tests/Feature/DoctorShare/DoctorShareReportCategoryFilterTest.php`

**Interfaces:**

- Produces: filter values `opd|ipd|emergency|lab|imaging|pharmacy` (no `investigation`). Filter: `whereHas('billItem', fn ($q) => $q->where('item_category', $request->bill_type))`. Entitlement map: opd→`visits`, ipd→`ipd`, emergency→`emergency`, lab→`laboratory`, imaging→`imaging`, pharmacy→`pharmacy`. Investigation token: `abortUnlessReportBillTypeEntitled` 403s or ignores — **do not list it**; posting it 403s.

- [ ] **Step 1: Write the failing tests**

1. OPD header bill with one `item_category=lab` share item and one `opd` share item. GET `bill_type=lab` shows the lab item, not the opd item. GET `bill_type=opd` shows only the opd item. **Fails today** (header `bill_type=opd` includes both).
2. GET `bill_type=investigation` → 403 (and not listed on create/index HTML).
3. GET `bill_type=pharmacy` 403 without pharmacy module; 200 with `['doctor-share','pharmacy']` (empty list ok).
4. Fingerprint history unchanged.

- [ ] **Step 2: Run tests to verify they fail**

Expected: FAIL (lab filter uses header).

- [ ] **Step 3: Implement line-category filter + option list** (add pharmacy; drop investigation; keep entitlement helpers).

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/DoctorShare/DoctorShareReportCategoryFilterTest.php tests/Feature/Module/ParentRouteEntitlementTest.php tests/Feature/DoctorShare`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DoctorShareController.php resources/views/admin/doctor-share/reports tests/Feature/DoctorShare/DoctorShareReportCategoryFilterTest.php tests/Feature/Module/ParentRouteEntitlementTest.php
git commit -m "$(cat <<'EOF'
fix(doctor-share): filter share reports by bill line category

EOF
)"
```

- [ ] **Step 6: Phase 4 report**

---

### Task 10: Registry — `settings.doctor-share`, remove Finance slug

**Files:**

- Modify: `app/Models/ModuleRegistry.php`
- Modify: `app/Support/PermissionRegistry.php` — add `settings.doctor-share` with **rules** groups + `manage doctor shares` only. Keep items / settlements / reports groups on PermissionRegistry key `doctor-share` (role form still lists them; this key is **not** a ModuleRegistry slug). `PermissionRegistry::forModule('settings.doctor-share')` must **not** contain `view share reports` / items / settlements names. `flat()` / `all()` still contain every share name once (no duplicates).
- Modify: `app/Support/SettingsSectionRegistry.php` — child order 30, label Doctor Share, `route => 'doctor-share.rules.index'` as a **placeholder**. Task 14 retargets to `doctor-share.rates.index`. Until then Settings link opens the still-present rules index. Do not add a temporary rates alias.
- Modify: `tests/Unit/Services/SettingsAccessTest.php` — user with only `view share reports` cannot access `settings.doctor-share`; user with `view share rules` can (unbundled, not `view settings`).

- Modify: `database/seeders/PlanSeeder.php` — enterprise: replace `'doctor-share'` with `'settings.doctor-share'`
- Modify: `tests/Unit/ModuleRegistryTest.php` — `all()` stays **52**; `topLevel()` **20 → 19**; `moduleForRoute('doctor-share.reports.index') === 'settings.doctor-share'`; `parentOf('settings.doctor-share') === 'settings'`; **not** bundled; **not** added by `normalize(['settings'])`
- Modify: `tests/Feature/SuperAdmin/PlanModuleDefaultsTest.php` — enterprise contains `settings.doctor-share`, not `doctor-share`
- Modify: `tests/Feature/Permissions/DoctorSharePermissionsTest.php` — `hasModule` true for `settings.doctor-share` (and `settings` if CheckModule parent is exercised)

**Interfaces:**

- Produces: `settings.doctor-share` node: `parent => settings`, `entitlement => plan`, `routes => ['doctor-share.']`, **no** `bundled`. Parent `settings.children` inline list includes `settings.hospital-info`, `settings.prescription-print`, `settings.doctor-share`. Delete top-level Finance `doctor-share` node. `all()` stays **52** (remove one top-level, add one child). `topLevel()` **19**.

- [ ] **Step 1: Write failing unit tests** (`moduleForRoute`, `normalize(['settings'])` does not add child, `topLevel` 19, seeder expectation, `forModule('settings.doctor-share')` is rules-only, SettingsAccess reports-only user is denied).

- [ ] **Step 2: Run to verify fail**

Run: `php artisan test --compact tests/Unit/ModuleRegistryTest.php tests/Unit/PermissionRegistryTest.php tests/Feature/SuperAdmin/PlanModuleDefaultsTest.php`

Expected: FAIL.

- [ ] **Step 3: Implement registry + seeder + permission key split.** Update `DoctorSharePermissionsTest`, `ParentRouteEntitlementTest`, and the three Task 7–9 report tests so `hasModule` mocks return true for **both** `settings` and `settings.doctor-share` (CheckModule parent + child). Operational HTTP still uses `doctor-share.*` route names.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Unit/ModuleRegistryTest.php tests/Unit/PermissionRegistryTest.php tests/Feature/SuperAdmin/PlanModuleDefaultsTest.php tests/Feature/Permissions/DoctorSharePermissionsTest.php tests/Feature/Module/ParentRouteEntitlementTest.php tests/Feature/DoctorShare tests/Unit/Services/SettingsAccessTest.php`

Expected: PASS.
- [ ] **Step 5: Commit**

```bash
git add app/Models/ModuleRegistry.php app/Support/PermissionRegistry.php app/Support/SettingsSectionRegistry.php database/seeders/PlanSeeder.php tests
git commit -m "$(cat <<'EOF'
feat(catalog): register settings.doctor-share and drop finance slug

EOF
)"
```

---

### Task 11: C1 backfill keyed off `doctor-share`, not `settings`

**Files:**

- Create: `ModuleRegistry::backfillSettingsDoctorShare(array $modules): array` next to other backfills.

  - If `'doctor-share'` is not in the list: return values unchanged (**even if** `'settings'` is present). This is the starter/professional proof.
  - If `'doctor-share'` is in the list: append `settings.doctor-share`, **remove** `doctor-share`, unique.
  - If the child is already present, keep it and still strip a leftover `doctor-share` slug.
- Create: `database/migrations/landlord/2026_09_17_120000_backfill_settings_doctor_share_on_plans.php` — `Plan::query()->each` apply helper, save.
- Create: `tests/Feature/Module/DoctorShareCatalogBackfillTest.php` (landlord sqlite like `HrOtBackfillTest`)
- Modify: `app/Console/Commands/SyncPlanModules.php` — do **not** call this helper
- Modify: `tests/Feature/Commands/SyncPlanModulesTest.php` — a plan with `settings` + hospital-info + print and **no** doctor-share does not gain `settings.doctor-share` after `plans:sync-modules`

**Interfaces:** Produces the helper + landlord migration. Live migrate of this file is **gated** (Phase 5).

- [ ] **Step 1: Write failing tests**

1. Plan modules `['patients','settings','settings.hospital-info']` → helper returns the same (no child). This is the **starter** shape.
2. Plan modules that include `settings` plus a professional-like set (`billing`, `pharmacy`, `laboratory`, `visits`, …) **without** `doctor-share` → helper returns the same (no child). This is the **professional** proof.
3. Plan `['doctor-share','settings']` → contains `settings.doctor-share`, does not contain `doctor-share`.
4. Plan `['settings.doctor-share','settings']` unchanged, no `doctor-share` reintroduced.
5. `plans:sync-modules` does not add the child to a settings-only plan.

- [ ] **Step 2: Run to verify fail**

- [ ] **Step 3: Implement helper + landlord migration + sync-modules test.** Do not run the landlord migration against live `saasy` in this task.

- [ ] **Step 4: Run tests**

Run: `php artisan test --compact tests/Feature/Module/DoctorShareCatalogBackfillTest.php tests/Feature/Commands/SyncPlanModulesTest.php tests/Unit/ModuleRegistryTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Models/ModuleRegistry.php database/migrations/landlord/2026_09_17_120000_backfill_settings_doctor_share_on_plans.php tests/Feature/Module/DoctorShareCatalogBackfillTest.php tests/Feature/Commands/SyncPlanModulesTest.php
git commit -m "$(cat <<'EOF'
feat(catalog): C1-backfill settings.doctor-share from doctor-share entitlement

EOF
)"
```

---

### Task 12: Sidebar Settings child + operational group on `settings.doctor-share` (Phase 5)

**Files:**

- Modify: `app/Services/SidebarService.php` — Settings loop already emits children via `SettingsSectionRegistry`. After Task 10 the new child appears when `hasModule('settings.doctor-share')`. Change the **Finance** Doctor Share group `hasModule($tenant, 'doctor-share')` to `hasModule($tenant, 'settings.doctor-share')` **now** so live JSON after C1 does not hide operational nav. Remove the Rules item in Task 15, not here (matrix not built yet).
- Modify: sidebar tests if they look for module `doctor-share`.

**Interfaces:** Operational group Layer 1 slug = `settings.doctor-share`. Rules index still listed until Task 15.

- [ ] **Step 1: Write failing sidebar test**

Tenant modules `['settings','settings.doctor-share']`, user `view share items` — group Doctor Share items include Share Items, **not** hidden. Tenant `['settings']` only — no Doctor Share group, no Settings Doctor Share child. Tenant `['settings','settings.hospital-info']` — Hospital Info yes, Doctor Share Settings item no.

- [ ] **Step 2: Run to verify fail**

- [ ] **Step 3: Switch `hasModule(..., 'doctor-share')` to `settings.doctor-share`.** Settings child comes from registry automatically.

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/Permissions/SidebarVisibilityTest.php tests/Feature/DoctorShare tests/Feature/Module`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/SidebarService.php tests
git commit -m "$(cat <<'EOF'
feat(nav): gate doctor-share sidebar on settings.doctor-share

EOF
)"
```

- [ ] **Step 6: Phase 5 report.** Remind: live landlord C1 migrate is waiting on user confirmation. Pest proves starter/professional helpers do not add the child.

---

### Task 13: Matrix HTTP — empty vs zero, unique doctors, module columns

**Files:**

- Modify: `app/Http/Controllers/DoctorShareController.php` — add `ratesIndex`, `ratesSync`; stop using rule store/update in new tests
- Modify: `routes/web.php` — `GET doctor-share/rates` name `doctor-share.rates.index`; `PUT doctor-share/rates` name `doctor-share.rates.sync`; middleware `view share rules|create share rules|edit share rules|manage doctor shares` (GET view; PUT create|edit|manage)
- Create: `tests/Feature/DoctorShare/DoctorShareMatrixTest.php`

**Interfaces:**

- GET: doctors with existing rates; `$categoryOptions` filtered by `Tenant::currentHasModule` (opd if `visits`, ipd, emergency, lab→laboratory, imaging, pharmacy). `general` always. Each row: doctor_id + map of category → percentage **or** `null` if no row.
- PUT body: `doctors` array of `{ doctor_id, general, opd, ipd, emergency, lab, imaging, pharmacy }` where missing/empty string = no rate; `0` / `0.0` / `0.00` = store 0. Duplicate `doctor_id` → 422. Unentitled category with a value → 403. Replace that doctor’s rates in a transaction (delete doctor’s rates, insert submitted). Doctors omitted from the payload: **delete all their rates** (remove-row). History fingerprint unchanged.

- [ ] **Step 1: Write failing tests**

1. PUT one doctor general empty, opd `70`, pharmacy empty → one rate opd 70, no general, no pharmacy.
2. PUT opd `0` → rate row percentage 0.00 exists (not treated as empty).
3. Two rows same doctor_id → 422, rates unchanged.
4. PUT `lab` value without laboratory module → 403.
5. GET with laboratory off does not include `lab` in options (`assertDontSee` value=lab on the form).
6. Fingerprint history unchanged after sync.

- [ ] **Step 2: Run to verify fail**

- [ ] **Step 3: Implement `ratesIndex` / `ratesSync` + routes.** Do not delete old rule routes yet.

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/DoctorShare/DoctorShareMatrixTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/DoctorShareController.php routes/web.php tests/Feature/DoctorShare/DoctorShareMatrixTest.php
git commit -m "$(cat <<'EOF'
feat(doctor-share): add rates matrix HTTP with empty-versus-zero cells

EOF
)"
```

---

### Task 14: Matrix view, drop rule CRUD UI

**Files:**

- Create: `resources/views/admin/doctor-share/rates/index.blade.php`
- Create: `resources/js/doctor-share-rates-form.js` — add-row from `<select>` of doctors **not** already in `data-added-ids`; remove-row; disable option client-side. Server still validates (Task 13).
- Modify: `vite.config.js` — add the new entry
- Modify: `app/Support/SettingsSectionRegistry.php` — `route => 'doctor-share.rates.index'`
- Modify: `routes/web.php` — **remove** `doctor-share.rules.{create,store,edit,update,destroy,toggle}`. Keep `rules.index` as redirect to `rates.index` **or** delete and fix all `route('doctor-share.rules.index')` references (permissions test GET rules.index → change to rates.index).
- Delete (or leave unused): `resources/views/admin/doctor-share/rules/{index,create,edit}.blade.php`, `resources/js/doctor-share-rules-form.js` — **delete** in this task and drop the Vite entry.
- Modify: `tests/Feature/Permissions/DoctorSharePermissionsTest.php` — `rules.index` → `rates.index`
- Modify: `tests/Feature/Module/ParentRouteEntitlementTest.php` — **remove** applies_to store/create checkbox tests; replace with matrix lab-column 403 tests if not already in Task 13

**Interfaces:** One settings-capable page: rows of doctor + general + up to six category inputs. Add doctor / remove row. Save PUT `doctor-share.rates.sync`.

- [ ] **Step 1: Write failing GET test** `rates.index` assertSee General, OPD, and `name="doctors[0][opd]"` (or equivalent). `assertDontSee` “Create Share Rule”. `rules.create` 404.

- [ ] **Step 2: Run to verify fail**

- [ ] **Step 3: Blade + JS + route removal + SettingsSectionRegistry + Vite.** Update permission tests.

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/DoctorShare tests/Feature/Permissions/DoctorSharePermissionsTest.php tests/Feature/Module/ParentRouteEntitlementTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/admin/doctor-share resources/js vite.config.js routes/web.php app/Support/SettingsSectionRegistry.php tests
git commit -m "$(cat <<'EOF'
feat(doctor-share): replace rule CRUD with the rates matrix screen

EOF
)"
```

---

### Task 15: Operational sidebar group without Rules (C3)

**Files:**

- Modify: `app/Services/SidebarService.php` — Doctor Share group: **drop** Share Rules item. Keep Items, Settlements, Reports. Still `hasModule('settings.doctor-share')`. Settings child remains the matrix.

- [ ] **Step 1: Write failing sidebar test**

User with `view share rules` + `view share items` + modules settings child: Settings contains Doctor Share → `doctor-share.rates.index`. Operational group contains Items, Settlements (if perm), Reports (if perm), **does not** contain Share Rules / `doctor-share.rules.index`.

- [ ] **Step 2: Run to verify fail**

- [ ] **Step 3: Remove the Rules sidebar item.**

- [ ] **Step 4: Run** `php artisan test --compact tests/Feature/Permissions/SidebarVisibilityTest.php`

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/SidebarService.php tests
git commit -m "$(cat <<'EOF'
feat(nav): keep doctor-share ops group without the old rules link

EOF
)"
```

---

### Task 16: Regression + Phase 6 report

**Files:** none except fixes if anything fails.

- [ ] **Step 1: Run**

```bash
php artisan test --compact tests/Feature/DoctorShare tests/Feature/Billing/BillItemCategoryTest.php tests/Feature/Pharmacy/PharmacyPosCheckoutTest.php tests/Feature/Module tests/Feature/Permissions/DoctorSharePermissionsTest.php tests/Feature/Permissions/SidebarVisibilityTest.php tests/Feature/SuperAdmin/PlanModuleDefaultsTest.php tests/Unit/ModuleRegistryTest.php tests/Unit/PermissionRegistryTest.php
```

Expected: PASS. If fail, fix in a follow-up commit on this branch — do not start a new feature.

- [ ] **Step 2: Confirm no writes to history** — re-read `DoctorShareRateMigrator`, `calculate` `updateOrCreate` (new bills only), `ratesSync` (rates table only). Grep for `DoctorShareItem::` `update` in this branch diff; only `voidForBill` / settlement (unchanged) allowed.

- [ ] **Step 3: Phase 6 report** to the user: branch name, task list done, Hassan live copy hashes (from Task 4), C1 landlord migrate still waiting, no merge.

- [ ] **Step 4: Commit** only if Step 2 required a grep-driven fix.

---

## Live landlord C1 (not a numbered task until the user says)

After Phase 5/6 confirmation:

```bash
php artisan migrate --database=landlord --path=database/migrations/landlord/2026_09_17_120000_backfill_settings_doctor_share_on_plans.php --force
```

Then report `plans.modules` for starter / professional / enterprise: only enterprise has `settings.doctor-share`; none still have `doctor-share`.

---

## Spec coverage (self-review)

| Spec | Task |
|---|---|
| Parallel `doctor_share_rates` | 1–2 |
| Hassan copy + OPD widen C6 | 2 (Pest) + 3 (live) |
| History immutable | 2, 3, 4, 5, 6, 7, 13 fingerprints |
| Calculate from rates + new snapshot | 4 (after live copy) |
| IPD `primaryDoctor` only; freeze writer untouched | 5 |
| Pharmacy lines + POS + C2 skip | 6 |
| Collected scope / column / category filter | 7, 8, 9 |
| `settings.doctor-share` + remove Finance slug | 10 |
| C1 off `doctor-share` not `settings`; starter/professional stay out | 11 |
| Nav Settings child + ops group | 12, 15 |
| Matrix + empty vs 0 + six columns | 13–14 |
| C5 no archive drop | no drop task |
| Phase reports | 3, 5, 6, 9, 12, 16 |
| No merge | header |

No placeholders. `resolveRate` / `copyFromLegacyRules` / `ratesSync` names are stable across tasks.
