# Accounting Payments (Read-Only) — Design

**Date:** 2026-10-29  
**Branch:** `feat/accounting-payments-readonly`  
**Status:** Approved for planning

## Problem

Accounting already records every Cash / Bank money movement as journal lines on system accounts `1100` (Cash in Hand) and `1110` (Bank Account). There is no Accounting UI that lists those movements as “payments” (money in / money out, Cash / Bank). Staff must dig through Journal Entries or billing-only surfaces.

Bill `payments` (`app/Models/Payment`) remain a separate billing concept and must not be confused with this module.

## Goals

- Read-only **Payments** list under the Accounting module and sidebar.
- Columns: **Amount**, **Payment Date**, **Payment Type** (income / expense), **Payment Method** (Cash / Bank).
- Payment Type badges: success + in-arrow for income (money in); danger + out-arrow for expense (money out).
- Filters: payment type, payment method.
- Rows appear **automatically** when accounting posts Cash/Bank journal lines — no create/edit/delete UI for this module.
- Permissions wired end-to-end with no gaps.
- Avoid N+1 queries on the index and keep write-path sync efficient.

## Non-goals

- Creating, editing, or deleting payments from this screen.
- Replacing bill `payments` or Daily Cash Register.
- New plan child module slug (uses parent `accounting` entitlement).
- Mapping methods other than system accounts `1100` / `1110` (e.g. insurance `1210` is out of scope for this list).

## Decisions (approved)

| Topic | Choice |
|-------|--------|
| List scope | All Cash/Bank journal movements (patient payments, advances, refunds, deposits, transfers, payroll, manual JEs touching 1100/1110) |
| Transfers | Show both sides (out on source, in on destination) |
| Cash vs Bank | Only CoA codes `1100` → Cash, `1110` → Bank |
| Persistence | Denormalized `accounting_payments` table synced from journal lines |
| Permission | `view accounting payments` (read-only) |

## Architecture

```
Accounting write paths (payments, advances, refunds, deposit, transfer, payroll, manual JE)
        │
        ▼
JournalEntry + JournalEntryLine (1100 / 1110)
        │
        ▼
AccountingPaymentSync (same DB transaction)
        │
        ▼
accounting_payments  ──read──►  Accounting Payments index (filters, pagination)
```

### Data model

Tenant table `accounting_payments`:

| Column | Type | Notes |
|--------|------|--------|
| `id` | bigint PK | |
| `amount` | decimal(15,2) | Absolute amount from the cash/bank line |
| `payment_date` | date | From journal `entry_date` |
| `payment_type` | enum/string | `income` (debit on 1100/1110) or `expense` (credit on 1100/1110) |
| `payment_method` | enum/string | `cash` (`1100`) or `bank` (`1110`) |
| `journal_entry_id` | FK → journal_entries | Cascade-aware cleanup |
| `journal_entry_line_id` | FK → journal_entry_lines | **Unique** — one payment row per cash/bank line |
| `description` | string/text nullable | Copied from JE description for optional display/context |
| `timestamps` | | |

Indexes:

- Unique: `journal_entry_line_id`
- Filter/sort: `payment_date`, `payment_type`, `payment_method`
- Composite useful for filtered lists: `(payment_type, payment_method, payment_date)`

Model: `App\Models\AccountingPayment` (tenant connection). Keep name distinct from `App\Models\Payment`.

### Write path (sync)

Centralize in a small service (e.g. `AccountingPaymentSync`) called from the same tenant DB transaction that creates/updates/reverses journal entries that can touch Cash/Bank:

1. **After lines are persisted** for a journal entry: for each line whose account code is `1100` or `1110`, upsert an `accounting_payments` row keyed by `journal_entry_line_id`.
2. Mapping:
   - debit > 0 → `payment_type = income`
   - credit > 0 → `payment_type = expense`
   - code `1100` → `cash`; `1110` → `bank`
3. **On reversal / line replacement / supersede:**
   - When an entry is marked `superseded`, **delete** all `accounting_payments` for that `journal_entry_id` (original cash effect leaves the list).
   - When a `reversal` (or replacement) entry is posted, sync from **its** Cash/Bank lines so the mirrored effect appears as new row(s).
   - Prefer delete-by-`journal_entry_id` + batch insert over per-line N queries when rewriting an entry.
4. Call sites must cover all paths that post 1100/1110 lines: `AccountingService` auto posts, `AccountingController` deposit/transfer/manual JE store/update, payroll posting, and any other Cash/Bank posters discovered during implementation.

Do **not** expose Form Requests for creating accounting payments — sync is internal only.

### Backfill

Artisan command (or one-shot seeder invoked by deploy notes) that:

- Selects non-`superseded` journal entry lines on accounts `1100`/`1110`
- Inserts missing `accounting_payments` (idempotent via unique `journal_entry_line_id`)
- Runs in chunked queries (no loading entire ledger into memory)

### Read UI

- Route: `GET accounting/payments` → name `accounting.payments`
- Middleware: `permission:view accounting payments|view accounting` (match existing accounting OR-with-deprecated pattern)
- Controller: index-only on `AccountingController` or thin `AccountingPaymentController`
- View: `resources/views/admin/accounting/payments.blade.php` — same card + filter bar + table pattern as Journal Entries
- Columns: Amount, Payment Date, Payment Type (badge + arrow), Payment Method (Cash/Bank label)
- Filters (GET): `payment_type` (`income`|`expense`), `payment_method` (`cash`|`bank`)
- Default sort: `payment_date` desc, then `id` desc
- Pagination with `withQueryString()`
- No New / Edit / Delete actions

### N+1 / query rules (hard requirement)

- Index reads **only** from `accounting_payments` for the four required columns — no per-row journal/account lookups.
- If a description or journal link is shown later: eager-load with `with([...])` in one query; never resolve relations inside the Blade loop without eager load.
- Write sync: batch upsert/delete by entry; avoid N round-trips per line when rewriting an entry.
- Feature test: create multiple payment rows, hit index, assert query count stays bounded (no linear growth with row count for relation loads).

### Menu

`SidebarService` Accounting group: add **Payments** (icon e.g. `fa-money-bill` / `fa-exchange-alt`) when:

- Tenant has module `accounting`, and
- User `can('view accounting payments')` or `can('view accounting')`

Place near Journal Entries. Active route pattern: `accounting.payments*`.

### Permissions (no gaps)

| Surface | Change |
|---------|--------|
| `RolePermissionSeeder::PERMISSIONS` | Add `view accounting payments` |
| `ROLE_PERMISSIONS` | Grant to roles that already receive comparable accounting views (e.g. Hospital Administrator; mirror `view journal entries` assignees) |
| `PermissionRegistry` | Accounting group entry |
| Route middleware | As above |
| Sidebar | As above |
| Blade | `@can` only if chrome is added; page itself is route-gated |

Existing billing permissions (`create payments`, `edit payments`, `delete payments`) are unrelated and unchanged.

### Testing

- **AuthZ:** permitted → 200; unpermitted → 403; sidebar item visible/hidden as appropriate if covered by existing patterns.
- **Sync:** posting a Cash/Bank JE creates correct row(s); transfer creates two rows (in + out); reversal/cleanup removes or replaces rows; filters return correct subsets.
- **N+1:** index query-count assertion with multiple rows.
- **Backfill:** idempotent; unique constraint respected.

### Isolation

Implemented on branch `feat/accounting-payments-readonly` in an isolated worktree so existing WIP on other branches is untouched.

## Out of scope follow-ups

- Date-range filters (can add later without schema change).
- Child plan slug `accounting.payments`.
- Showing insurance / non-1100-1110 asset movements.
- Unifying bill `Payment` UI with this list.
