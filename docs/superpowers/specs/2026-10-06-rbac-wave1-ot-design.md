# RBAC Wave 1 (continued): Operation Theatre design

**Date:** 2026-10-06
**Status:** Draft, for review before the combined Wave 1 implementation plan.
**Investigation:** `2026-10-06-rbac-wave1-ot-investigation.md`
**Confirmed inputs:**
- D2: create does not imply view.
- D5: `manage …` OT permissions stay as they are.
- D6: one permission per action, with before/after tests per route.
- AC-1: admin backfill only where the seeder intends it.
- **AD-1:** conditional post-write redirect, applied here automatically.
- **OT-1:** a new `manage theatres` permission, backfilled to Super Admin and HA **in the same deploy action**.
- **OT-2:** cancel requires `delete surgeries`.
- Spatie permissions only.

**Out of scope:**
- The PAC, checklist, consumables and sterilization groups (already single-permission).
- Surgery and monitoring business logic.

---

## 1. `manage theatres` (OT-1)

| Piece | Change |
|---|---|
| `PermissionRegistry` | `ot` module: add the group `'theatres' => ['manage theatres']` next to `surgeries`. It's the same module, so tenant module provisioning picks it up with OT. |
| `RolePermissionSeeder` | Add to `PERMISSIONS` (OT section) and to `ROLE_PERMISSIONS['Hospital Administrator']`. Super Admin gets it through `Permission::all()`. |
| **Backfill command** | `rbac:backfill-manage-theatres`, a copy of the shape of `BackfillManageSubscriptionPermission`: every tenant, or the current one; per-tenant cache isolation; idempotent; `--dry-run` writes nothing. It grants the permission to the roles named `Super Admin` and `Hospital Administrator`. **Role names are used only to choose backfill targets**, never as an access check. |
| Expected result | 14 grants (2 roles × 7 tenants). |
| Deploy | It must run **in the same deploy action** as the route change. Otherwise admins get 403 on theatre create/edit from the moment the routes are live. This goes in the README deploy note together with PH-1 (see the Pharmacy design §7). |

## 2. Route mapping (27 routes)

**Shape:** replace the single any-of `Route::middleware('permission:view|create|edit|delete surgeries')->group()` with a per-route `->middleware('permission:…')`. URLs, names and verbs stay the same.

| Route | Before | After |
|---|---|---|
| `ot.calendar`, `ot.calendar.events` | any-of 4 | `view surgeries` |
| `ot.check-conflicts` | any-of 4 | `create surgeries\|edit surgeries` (read-only helper for both forms; noted exception, investigation §2.1) |
| `ot.theatres` | any-of 4 | `view surgeries` |
| `ot.theatres.create`, `.store`, `.edit`, `.update` | any-of 4 | **`manage theatres`** |
| `ot.surgeries.index`, `.show` | any-of 4 | `view surgeries` |
| `ot.surgeries.create`, `.store` | any-of 4 | `create surgeries` |
| `ot.surgeries.edit`, `.update` | any-of 4 | `edit surgeries` |
| `ot.surgeries.start`, `.complete`, `.postpone` | any-of 4 | `edit surgeries` |
| `ot.surgeries.cancel` | any-of 4 | **`delete surgeries`** (OT-2) |
| `ot.monitoring.anaesthesia`, `.store-anaesthesia`, `.vitals`, `.store-vitals`, `.post-op`, `.store-post-op` | any-of 4 | `edit surgeries` |
| `ot.monitoring.vitals-data` | any-of 4 | `view surgeries` |

## 3. Real-role effect (re-verified at execution, before merge)

| Role | Users | After |
|---|---|---|
| Super Admin | 8 | unchanged (all 4 surgery permissions, plus `manage theatres` from the backfill) |
| Hospital Administrator | 1 | unchanged (same) |
| Nurse | 1 | unchanged (no surgery permissions before or after; the `manage …` groups are untouched) |
| others | 18 | unchanged |

## 4. Redirects (AD-1)

There's one private helper in `OTController` with the same contract as the Accounting one. It goes to the list or detail page if the user can view it, and otherwise to the fallback, with the same flash message.

| Action | Target today | Fallback when the target isn't viewable |
|---|---|---|
| `storeTheatre`, `updateTheatre` | `ot.theatres` (`view surgeries`) | `ot.theatres.create` / `ot.theatres.edit` |
| `store` (surgery) | `ot.surgeries.index` | `ot.surgeries.create` |
| `update` (surgery) | `ot.surgeries.show` | `ot.surgeries.edit` |
| `OperativeMonitoringController::storeAnaesthesia` | `ot.surgeries.show` | `ot.monitoring.anaesthesia` |
| lifecycle, `storeVitals`, `storePostOp` | `back()` | unchanged |

**Shared helper:** this is the second controller needing the same helper, after Accounting. The plan puts it once in a small trait, `App\Http\Controllers\Concerns\RedirectsAfterWrite`, used by Accounting, OT and Pharmacy, rather than copying a private method.

## 5. UI gating

| View | Control | Gate |
|---|---|---|
| `surgeries/show.blade.php:33` | Start Surgery form | `@can('edit surgeries')` |
| `:40` | Postpone button (+ form `:204`) | `@can('edit surgeries')` |
| `:43` | Edit link | `@can('edit surgeries')` |
| `:49` | Complete button (+ form `:165`) | `@can('edit surgeries')` |
| `:56` | Cancel button (+ form `:187`) | `@can('delete surgeries')` |
| `:346 / :353 / :360` | Anaesthesia / Vitals / Post-op links | `@can('edit surgeries')` |
| `calendar.blade.php:40` | Schedule | `@can('create surgeries')` |
| `theatres/index.blade.php:10` | Add Theatre | `@can('manage theatres')` |
| `theatres/index.blade.php:51` | per-theatre Edit | `@can('manage theatres')` |
| `checklist/show.blade.php:35`, `consumables/usage.blade.php:86`, `pac/show.blade.php:219` | Back-link to the surgery (`@canany` of all 4 today) | `@can('view surgeries')` |

**JS:** `resources/js/ot-surgery-show.js` `setupToggle` already returns early when a button or form is missing. Hiding them is safe, so no JS change is needed.

**Sidebar:** no change (Theatres and Surgeries are gated by `view surgeries`).

## 6. Tests

**`tests/Feature/Permissions/OtSurgeryRouteScopingTest.php`** (new; helper names checked for collisions; reuses the `makeScheduledSurgery` fixture approach from `tests/Feature/OT`).

**For each of the 27 routes:**
- **Red before the fix:** a `view surgeries`-only user gets **403** on every write and form route. Each read route also gets the wrong-verb case, e.g. `create surgeries`-only gets 403 on index/show/calendar.
- **Positive control:** exactly the new permission passes (200, or the expected 302).

**Side effects** (blocked *and* nothing written):
- A `view surgeries`-only POST to `start` / `complete` / `cancel` / `postpone` leaves the status unchanged.
- A store-anaesthesia / vitals / post-op attempt adds no row.
- A theatre store adds no `operation_theatres` row.
- An `edit surgeries`-only POST to `cancel` gets 403 and the status isn't `cancelled` (OT-2). A `delete surgeries` user cancels.

**`manage theatres`:**
- An `edit surgeries`-only user gets 403 on theatre create, store, edit and update.
- A `manage theatres` user succeeds.
- A `view surgeries` user still sees the theatre list.

**Redirects (AD-1):** each of the 5 redirecting writes gets both cases (view held, view not held).

**UI:**
- show page: a view-only user sees no Start, Postpone, Edit, Complete, Cancel or monitoring links; an editor sees them; Cancel appears only with `delete surgeries`;
- calendar: Schedule only with `create`;
- theatres: Add/Edit only with `manage theatres`;
- the PAC, checklist and usage back-links appear only with `view surgeries`.

**`tests/Feature/Commands/BackfillManageTheatresPermissionTest.php`:**
- registry and seeder contain the permission;
- grants exactly SA and HA;
- idempotent;
- leaves other roles untouched;
- `--dry-run` writes nothing (permission, grants and cache).
- Mirrors `BackfillManageSubscriptionPermissionTest`.

**Parity tests:** update the exact-list assertion for the `ot` module in `tests/Unit/PermissionRegistryTest.php` if one exists (as Wave 1 did for `settings`). `OtPermissionSeederTest` should keep passing; if it asserts exact HA lists, extend it.

**Regression:** `tests/Feature/OT` (12) green; the full suite at the 34-failure baseline.
