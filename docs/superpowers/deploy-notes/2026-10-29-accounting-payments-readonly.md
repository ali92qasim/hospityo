# Deploy: Accounting Payments (Read-Only)

**Branch:** `feat/accounting-payments-readonly`  
**Date:** 2026-10-29

Adds a read-only Accounting → Payments index backed by denormalized `accounting_payments` rows synced from Cash (`1100`) / Bank (`1110`) journal lines. Permission: `view accounting payments` (Super Admin + Hospital Administrator).

> **Hard requirement.** Run these steps in order in the same deploy action as the code. Migrations must land before either backfill. Dry-run the backfills in staging before applying.

## Steps

### 1. Deploy code

Ship / pull `feat/accounting-payments-readonly` (or the merge that contains it) to the target environment.

### 2. Run tenant migrations

Creates the tenant `accounting_payments` table (`database/migrations/tenant/2026_10_29_000001_create_accounting_payments_table.php`).

```bash
php artisan tenants:artisan "migrate --database=tenant --path=database/migrations/tenant"
```

Confirm the migration ran on every tenant before continuing.

### 3. Permission backfill

Grants `view accounting payments` to Super Admin and Hospital Administrator in every tenant. Idempotent. Dry-run first in staging.

```bash
# Staging: preview only (writes nothing)
php artisan rbac:backfill-view-accounting-payments --dry-run

# Apply (same command without --dry-run)
php artisan rbac:backfill-view-accounting-payments
```

Optional but recommended after permission catalog changes:

```bash
php artisan tenants:sync-permissions
```

### 4. Historical payments backfill

Fills missing `accounting_payments` from existing non-superseded Cash/Bank journal lines. Idempotent by `journal_entry_line_id`. The command uses the current tenant connection — run it via `tenants:artisan` so every tenant is covered. Dry-run first.

```bash
# Staging / preview: report would-insert counts, write nothing
php artisan tenants:artisan "accounting:backfill-payments --dry-run"

# Apply
php artisan tenants:artisan "accounting:backfill-payments"
```

### 5. Smoke checks

As a Hospital Administrator (or Super Admin):

1. **Menu / page:** Accounting → Payments loads without 403.
2. **Filters:** `payment_type` (income/expense) and `payment_method` (cash/bank) narrow the list; pagination keeps query string.
3. **Live sync:** Record a new bill payment (or other Cash/Bank journal path). Confirm a matching row appears on Accounting → Payments.

## Notes

- Index is read-only — no create/edit/delete for accounting payments.
- Billing `create/edit/delete payments` permissions are unrelated and unchanged.
- Both backfills are safe to re-run; they skip rows/roles already present.
