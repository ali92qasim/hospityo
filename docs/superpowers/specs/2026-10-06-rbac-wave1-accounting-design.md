# RBAC Wave 1 (continued): Accounting design

**Date:** 2026-10-06
**Status:** Draft. **Awaiting confirmation** of §6 before the implementation plan is written.
**Investigation:** `2026-10-06-rbac-wave1-accounting-investigation.md`
**Confirmed inputs:**
- D2: create does not imply view.
- D5: `view accounting` is valid for viewing only.
- D6: one permission per action, with before/after tests per route.
- AC-1: no backfill. HA loses fiscal-year close, closed as the bug. The same rule now applies automatically in later modules.
- AC-2: the deposit/transfer forms are gated on `create …`.
- AC-3: pre-close requires `close fiscal years`.
- AC-4: Fiscal Years sidebar entry is a follow-up only.
- Spatie permissions only. No role-name checks.

**Out of scope:**
- Posting and accounting logic.
- The Fiscal Years sidebar entry (AC-4).
- `delete …` and `create/edit fiscal years` permissions that no route uses (no routes will be invented for them).

---

## 1. Route mapping

Each route gets **one action permission**. `view accounting` is OR'd **only onto read GETs** (D5). It is never accepted on a form page or a write.

| Route | Before (any-of) | After |
|---|---|---|
| `accounting.chart-of-accounts` | `view\|create\|edit\|delete chart of accounts\|view accounting` | `view chart of accounts\|view accounting` |
| `accounting.create-account` | same | `create chart of accounts` |
| `accounting.store-account` | same | `create chart of accounts` |
| `accounting.edit-account` | same | `edit chart of accounts` |
| `accounting.update-account` | same | `edit chart of accounts` |
| `accounting.deposit` | `view\|create deposits\|view accounting` | `create deposits` (AC-2) |
| `accounting.process-deposit` | same | `create deposits` |
| `accounting.transfer` | `view\|create transfers\|view accounting` | `create transfers` (AC-2) |
| `accounting.process-transfer` | same | `create transfers` |
| `accounting.journal-entries` | `view\|create\|edit\|delete journal entries\|view accounting` | `view journal entries\|view accounting` |
| `accounting.create-journal-entry` | same | `create journal entries` |
| `accounting.store-journal-entry` | same | `create journal entries` |
| `accounting.edit-journal-entry` | same | `edit journal entries` |
| `accounting.update-journal-entry` | same | `edit journal entries` |
| `accounting.fiscal-years` | `view\|create\|edit\|close fiscal years\|view accounting` | `view fiscal years\|view accounting` |
| `accounting.fiscal-years.pre-close` | same | **`close fiscal years`** (AC-3) |
| `accounting.fiscal-years.close` | same | **`close fiscal years`** |
| general-ledger, 3 ledgers, profit-loss, balance-sheet (6) | `view <x>\|view accounting` | **unchanged** |

**Shape:**
- Replace the four any-of `Route::middleware(...)->group()` blocks with a per-route `->middleware('permission:…')`, the same pattern as Doctor Share (`68076de`).
- URLs, names and HTTP verbs stay the same.

**Registry / seeder / backfill:**
- `PermissionRegistry` and `RolePermissionSeeder`: **no changes.** Every permission used already exists, and AC-1 means nothing is added to HA.
- **No backfill command.** Nothing is granted.

## 2. Real-role effect (re-verified at execution, before merge)

| Role | Users | After |
|---|---|---|
| Super Admin | 8 | **unchanged.** Holds every accounting permission. |
| Hospital Administrator | 1 | Loses `fiscal-years.pre-close` and `fiscal-years.close` (AC-1). Everything else is unchanged, because HA holds view+create+edit for CoA and journal entries, create for deposits and transfers, view for fiscal years and every report. |
| every other role | 19 | unchanged (holds no accounting permissions) |

## 3. Redirects after a successful write (**AD-1**)

All writes redirect to a list page:
- store/update account, deposit and transfer go to `chart-of-accounts`;
- journal-entry writes go to `journal-entries`;
- close goes to `fiscal-years`.

Under D2, a user with the write permission but not the list's `view` permission would save successfully and then land on a 403, and lose the success message.

**Proposal:**
- Redirect to the list **when the user can view it**.
- Otherwise, redirect back to the page the action came from, with the same success flash:
  - a store or process action returns to its create form;
  - an update returns to its edit form;
  - close returns to `pre-close`, which now shows "already closed" through its existing guard. `fiscal-years` is the better target if they can view it.
- **Implementation:** a small shared trait, `App\Http\Controllers\Concerns\RedirectsAfterWrite`, with one method, `redirectAfterWrite(string $target, array $targetPermissions, string $fallback, array $params, string $message)`. It sends the user to `$target` if they can any of `$targetPermissions` (here `view <x>`, `view accounting`), and otherwise to `$fallback`.
  - It's used by Accounting, OT and Pharmacy.
  - *Amended 2026-10-06:* this was originally a private method in `AccountingController`. It became a trait once OT and Pharmacy needed the same helper, and the behaviour is unchanged.
- **No real role is affected today:** Super Admin and HA hold the view permissions. This is about D2 correctness for custom roles.

## 4. UI gating

Each control gets an `@can` matching its route. That way no one sees a button that returns 403, and nothing else changes.

| View | Control | Gate |
|---|---|---|
| `chart-of-accounts.blade.php:8` | Deposit button | `@can('create deposits')` |
| `chart-of-accounts.blade.php:11` | Transfer button | `@can('create transfers')` |
| `chart-of-accounts.blade.php:14` | New Account button | `@can('create chart of accounts')` |
| `chart-of-accounts.blade.php:80` | per-row Edit | `@can('edit chart of accounts')` |
| `journal-entries.blade.php:12` | New Journal Entry | `@can('create journal entries')` |
| `journal-entries.blade.php:72` | per-row Edit (manual entries) | `@can('edit journal entries')`. The "Auto, locked" icon is unchanged. |
| `fiscal-years.blade.php:49` | Close Period link | `@can('close fiscal years')`. Without it, open years show "—". |
| `pre-close-summary.blade.php:107` | Close form | already behind `close fiscal years` at the route level. No view change is needed. |

**Sidebar** (`SidebarService.php:250-278`): **no change.** Each item is already `view <x> || view accounting`.

**Write-only users** (e.g. only `create deposits`) won't get a sidebar entry, because the sidebar lists read pages. They reach the form by URL. Accepted under D2: nobody holds such a role, and adding sidebar entries for forms is out of scope.

## 5. Tests

**New file:** `tests/Feature/Permissions/AccountingRouteScopingTest.php`. It reuses the tenant-binding and user-factory approach of `AccountingPermissionsTest.php` (a mocked tenant with module `accounting`), with new helper names, checked against `tests/` for collisions.

**For every one of the 17 changed routes:**
- **Escalation (red before the fix):** a user holding only a wrong-verb permission in the same group, which passes today, gets **403**. The `view accounting`-only case covers every write and form route.
- **Positive control:** a user holding exactly the new permission gets 200 (GET) or the expected 302 (write).
- **Coarse view:** `view accounting` alone gets 200 on the 3 list GETs (CoA, journal entries, fiscal years) and 403 on all 14 form, write and close routes.

**Side-effect assertions** (the request is blocked *and* nothing is written):
- `view chart of accounts`-only `POST store-account` gives 403 and the `accounts` count is unchanged.
- `view accounting`-only `POST process-deposit` / `process-transfer` / `store-journal-entry` gives 403 and the `journal_entries` count is unchanged.
- `view fiscal years`-only `POST fiscal-years.close` gives 403 and the year stays `is_closed = 0`. This is the headline case.
- A `close fiscal years` user closes it (positive). The existing `FiscalYearLockTest` setup is reused for a valid year.

**Seeder-HA case (AC-1, documents the decision):**
- After seeding `RolePermissionSeeder`, a user assigned the seeded `Hospital Administrator` role gets 403 on pre-close and close. (The seeder's `ROLE_PERMISSIONS` is `private`, so the test seeds and uses the real role rather than reading the constant.)
- The same role gets 200 on every other accounting page.

**Redirects (AD-1):**
- A `create chart of accounts`-only store is redirected to `create-account` with the success flash.
- With `view chart of accounts` added, it goes to `chart-of-accounts`.
- Each of the 6 redirecting writes gets one case of each kind.

**UI:**
- A view-only user on chart-of-accounts sees no Deposit, Transfer, New Account or Edit controls; a user with the permissions sees each of them.
- The same check for journal entries (New / Edit).
- For fiscal years, Close Period is hidden without `close fiscal years`.

**Regression:**
- `AccountingPermissionsTest` (2) and `tests/Feature/Accounting/*` stay green.
- The full suite stays at the known 34-failure baseline.

**Real-render check:** not needed. No layout or print changes.

## 6. Decision to confirm

| # | Question | Recommendation |
|---|---|---|
| **AD-1** | After a successful write, redirect to the list only when the user can view it, and otherwise back to the originating form with the success flash (§3)? | **Yes.** It's the smallest change that stops D2 producing "saved, then 403". **I'd apply the same helper pattern in OT and Pharmacy** wherever their writes redirect to a list, without re-asking. |

**Delivery:** branch `feat/rbac-wave1-accounting` in a worktree (never `git stash`; removed after merge).
- Docs commit first.
- Then routes and tests, then redirects (AD-1), then UI.
- Then a closing gate: focused suites, the full chunked regression, a re-run of the role scan, and your sign-off before merge.
- No deploy-ordering requirement, because there's no backfill.
