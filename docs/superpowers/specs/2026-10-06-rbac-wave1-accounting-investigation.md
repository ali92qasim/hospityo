# RBAC Wave 1 (continued): Accounting investigation

**Date:** 2026-10-06
**Status:** Findings only. **No code changes.** Design comes after the decisions in §5.
**Code read:** `main` @ `6d891b3` (the working tree is on `fix/bug-fixes`, fast-forwarded to `6d891b3`, plus unrelated WIP).
**Parent:** `2026-10-03-rbac-permission-escalation-investigation.md`
**Precedent:** `2026-10-04-rbac-wave1-doctor-share-subscription-*`
**Rules:**
- D2: create does not imply view.
- D5: coarse `view accounting` is valid for viewing only.
- D6: one permission per action, with before/after tests per route.
- Backfill policy: admins backfilled, frontline closed, sign-off per module.
- Spatie permissions only.

**Method:**
- Read `routes/web.php:357-412`, `AccountingController` and the 16 accounting views.
- Read `SidebarService`, `PermissionRegistry`, `RolePermissionSeeder` and `MigrateCoarsePermissions`.
- Read-only SQL scan of all roles in the 7 local tenant databases, plus their `fiscal_years` tables. No writes.

---

## 1. Routes (23): current gate and candidate permission

**Authorization layers:** `AccountingController` has **no** `authorize`/`can`/`abort_*` calls and uses no FormRequests (plain `Request` + inline `validate`). The route middleware is the only authorization.

| Route | Method | What it does | Current (any-of) | Candidate |
|---|---|---|---|---|
| `chart-of-accounts` | GET | Account list | `view\|create\|edit\|delete chart of accounts\|view accounting` | `view chart of accounts\|view accounting` |
| `create-account` | GET | New-account form | same | `create chart of accounts` |
| `store-account` | POST | Creates account (+ opening-balance JE) | same | `create chart of accounts` |
| `edit-account` | GET | Edit form | same | `edit chart of accounts` |
| `update-account` | PUT | Updates account | same | `edit chart of accounts` |
| `deposit` | GET | Deposit **form** (no list) | `view\|create deposits\|view accounting` | `create deposits` (**AC-2**) |
| `process-deposit` | POST | Posts a deposit journal entry | same | `create deposits` |
| `transfer` | GET | Transfer **form** (no list) | `view\|create transfers\|view accounting` | `create transfers` (**AC-2**) |
| `process-transfer` | POST | Posts a transfer / expense-reclass JE | same | `create transfers` |
| `journal-entries` | GET | JE list | `view\|create\|edit\|delete journal entries\|view accounting` | `view journal entries\|view accounting` |
| `create-journal-entry` | GET | Form | same | `create journal entries` |
| `store-journal-entry` | POST | Posts JE | same | `create journal entries` |
| `edit-journal-entry` | GET | Form | same | `edit journal entries` |
| `update-journal-entry` | PUT | Rewrites JE lines | same | `edit journal entries` |
| `fiscal-years` | GET | FY list | `view\|create\|edit\|close fiscal years\|view accounting` | `view fiscal years\|view accounting` |
| `fiscal-years.pre-close` | GET | Pre-close summary; its only action is the Close form | same | `close fiscal years` (**AC-3**) |
| `fiscal-years.close` | POST | **Closes the fiscal year** (final, locks posting) | same | **`close fiscal years`** |
| `general-ledger` | GET | Report | `view general ledger\|view accounting` | unchanged |
| `patient-ledger` / `vendor-ledger` / `employee-ledger` | GET | Reports | `view <x> ledgers\|view accounting` | unchanged |
| `profit-loss` / `balance-sheet` | GET | Reports | `view <x>\|view accounting` | unchanged |

**Total:** 17 routes change and 6 (the reports) are already correct.

**The escalation today:**
- `view accounting` or any single `view …` permission opens every write action in its group. For example, `view fiscal years` **closes a fiscal year**, and `view accounting` alone can post journal entries, deposits and transfers.
- Create-only and edit-only users cross over to each other's actions.

**Permissions no route uses:**
- `delete chart of accounts` and `delete journal entries` (there are no delete routes)
- `create fiscal years` and `edit fiscal years` (fiscal years are created only by `ChartOfAccountsSeeder`)
- `view deposits` and `view transfers` (after AC-2)

These aren't bugs, and the design won't invent routes for them.

## 2. Real-role impact (all 7 tenants)

| Role | Tenants | Users | Accounting permissions held | Loses after scoping |
|---|---|---|---|---|
| Super Admin | 7 | 8 | all 23 | **nothing** |
| Hospital Administrator | 7 | **1** (demo-hospital) | view/create/edit CoA, JE; view/create deposits, transfers; all ledger/report views; view/create/edit FY; `view accounting`. **Not** `close fiscal years`, no deletes. | **`fiscal-years.close` + `pre-close`** |
| all others (Doctor, Nurse, Receptionist, Pharmacist, Lab Technician, Medical Records Clerk, Test Role) | 1–7 | 19 | none | nothing |

No direct user-level grants of any accounting permission exist.

**Correction:** the 2026-10-04 sequencing scan listed Accounting as "0 live users affected". In fact **Hospital Administrator (1 user) loses fiscal-year close**.

**How much it matters locally:**
- The HA user is in `demo-hospital`, which has **no fiscal years**.
- No fiscal year in any tenant has ever been closed (`is_closed = 0`, `closed_by = null` everywhere).
- So nobody uses the capability today. **Production role data is unknown to me.**

**Seeder intent:** `RolePermissionSeeder` gives HA view/create/edit across every module and consistently withholds `delete …` and `close fiscal years` (it was introduced that way in `f5ebdbe`). HA's ability to close a fiscal year exists **only through the bug**. This conflicts with the "admins always backfilled" policy (**AC-1**).

## 3. UI coupling (must change with the routes)

**No accounting view has any `@can`.** Every action control renders for anyone who can open the page:

| View | Ungated control | Route it hits |
|---|---|---|
| `chart-of-accounts.blade.php:8,11,14` | Deposit / Transfer / New Account buttons | `deposit`, `transfer`, `create-account` |
| `chart-of-accounts.blade.php:80` | per-row Edit | `edit-account` |
| `journal-entries.blade.php:12` | New Journal Entry | `create-journal-entry` |
| `journal-entries.blade.php:72` | per-row Edit | `edit-journal-entry` |
| `fiscal-years.blade.php:49` | Close (→ pre-close) | `fiscal-years.pre-close` |
| `pre-close-summary.blade.php:107` | Close form | `fiscal-years.close` (route also blocks it) |

**Sidebar** (`SidebarService.php:250-278`): each item is gated `view <x> || view accounting`. That already matches the candidates, so **no change**.

**Orphan page:** Fiscal Years has **no sidebar entry**. The only link to it is the Cancel button on pre-close, so it's reachable only by typing the URL. This is pre-existing, unrelated to scoping, and noted only (**AC-4**).

## 4. Existing tests

- `tests/Feature/Permissions/AccountingPermissionsTest.php` has 2 tests:
  - JE blocked with only `view chart of accounts`;
  - CoA allowed with `view chart of accounts`.
  - Both stay valid.
- `tests/Feature/Accounting/*` covers posting logic (FiscalYearLockTest, JournalEntryTest, …). It doesn't cover authorization.

## 5. Decisions needed before design

| # | Question | Recommendation |
|---|---|---|
| **AC-1** | HA loses fiscal-year close. The policy says to backfill admins, but the seeder deliberately withholds `close …`/`delete …` from HA everywhere. Should we backfill `close fiscal years` to HA (and add it to the seeder), or close it as the bug? | **Close it (no backfill).** Closing a fiscal year is final, financial, irreversible and yearly. The seeder's intent is explicit, and no local HA user has fiscal years or has ever closed one. Super Admin keeps it. **It also sets the rule for Wave 2:** the same pattern makes HA lose `departments.destroy`, HR deletes and ward/bed deletes, so this answer decides those cases too unless you rule per module. |
| **AC-2** | `deposit` and `transfer` GET are forms only (there's no list). Gate them on `create deposits` / `create transfers`, which leaves `view deposits` / `view transfers` opening nothing? | **Yes.** The forms exist only to post. Deposits and transfers appear afterwards as journal entries, under `view journal entries`. Leave the two `view` permissions in the registry. |
| **AC-3** | `pre-close` is the first step towards close (like DS-1 settlement preview). Require `close fiscal years` for it? | **Yes**, so nobody opens a page whose only action returns 403. |
| **AC-4** | Add a Fiscal Years sidebar entry (`view fiscal years \|\| view accounting`)? | **Out of scope.** Record it as a minor follow-up; it's a navigation gap, not an RBAC issue. |
