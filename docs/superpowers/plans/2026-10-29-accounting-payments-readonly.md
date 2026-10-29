# Accounting Payments (Read-Only) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a read-only Accounting Payments list (Amount, Date, Type income/expense, Method Cash/Bank) that stays in sync with Cash (`1100`) / Bank (`1110`) journal lines via a denormalized `accounting_payments` table, with full permission wiring and no N+1 on the index.

**Architecture:** Journal writers (AccountingService, AccountingController deposit/transfer/manual JE, PayrollService) call `AccountingPaymentSync` in the same tenant DB transaction after Cash/Bank lines exist. The index reads only `accounting_payments` (filters + pagination). No create/edit/delete UI for this module.

**Tech Stack:** Laravel 12, Spatie laravel-permission, Spatie multitenancy, Blade, Pest.

**Spec:** `docs/superpowers/specs/2026-10-29-accounting-payments-readonly-design.md`

## Global Constraints

- Work only in worktree `C:/Users/Qasim/Herd/saasy/.worktrees/feat-accounting-payments` on branch `feat/accounting-payments-readonly`. Never checkout or commit in the main tree (`fix/bug-fixes` WIP).
- Never run `git stash` (worktrees share the stash list with the main repo).
- TDD: failing test first, confirm red for the right reason, implement, green, commit. At least one commit per task.
- Cash/Bank mapping is **only** CoA codes `1100` → `cash`, `1110` → `bank`. Debit → `income`, credit → `expense`.
- Index must not N+1: required columns come from `accounting_payments` alone.
- Permission name is exactly `view accounting payments`. Billing `create/edit/delete payments` are unrelated and unchanged.
- Docs under `/docs` are gitignored — always `git add -f` for specs/plans/deploy notes.
- No merge/push unless the human asks.

## File Structure

| File | Responsibility |
|------|----------------|
| `database/migrations/tenant/2026_10_29_000001_create_accounting_payments_table.php` | Schema for denormalized payment rows |
| `app/Models/AccountingPayment.php` | Eloquent model (tenant connection) |
| `app/Services/AccountingPaymentSync.php` | Upsert/clear payment rows from a journal entry |
| `app/Services/AccountingService.php` | Call sync after auto posts / reverse |
| `app/Http/Controllers/AccountingController.php` | Call sync after deposit/transfer/JE store/update; add `payments()` index |
| `app/Services/PayrollService.php` | Call sync after payroll JE |
| `app/Console/Commands/BackfillViewAccountingPaymentsPermission.php` | Grant permission to Super Admin + HA |
| `app/Console/Commands/BackfillAccountingPayments.php` | Historical rows from existing JE lines |
| `database/seeders/RolePermissionSeeder.php` | Register + assign permission |
| `app/Support/PermissionRegistry.php` | Role UI group |
| `routes/web.php` | `accounting.payments` GET |
| `resources/views/admin/accounting/payments.blade.php` | Read-only list + filters |
| `app/Services/SidebarService.php` | Accounting → Payments menu item |
| `docs/superpowers/deploy-notes/2026-10-29-accounting-payments-readonly.md` | Deploy: migrate, permission backfill, data backfill |
| `tests/Feature/Accounting/AccountingPaymentSyncTest.php` | Sync mapping, transfers, supersede |
| `tests/Feature/Accounting/AccountingPaymentsIndexTest.php` | AuthZ, filters, N+1 |
| `tests/Feature/Accounting/BackfillAccountingPaymentsTest.php` | Data backfill idempotency |

---

### Task 1: Migration + `AccountingPayment` model

**Files:**
- Create: `database/migrations/tenant/2026_10_29_000001_create_accounting_payments_table.php`
- Create: `app/Models/AccountingPayment.php`
- Test: `tests/Feature/Accounting/AccountingPaymentModelTest.php`

**Interfaces:**
- Produces: `App\Models\AccountingPayment` with fillable `amount`, `payment_date`, `payment_type`, `payment_method`, `journal_entry_id`, `journal_entry_line_id`, `description`; casts `amount` decimal:2, `payment_date` date; relations `journalEntry()`, `journalEntryLine()`.

- [ ] **Step 1: Write the failing model test**

```php
<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\User;

it('persists an accounting payment row', function () {
    $user = User::create([
        'name' => 'AP Model User',
        'email' => 'ap-model-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $entry = JournalEntry::create([
        'entry_date' => '2026-10-01',
        'description' => 'Test',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $line = $entry->lines()->create([
        'account_id' => $cash->id,
        'debit' => 100,
        'credit' => 0,
    ]);

    $payment = AccountingPayment::create([
        'amount' => 100,
        'payment_date' => '2026-10-01',
        'payment_type' => 'income',
        'payment_method' => 'cash',
        'journal_entry_id' => $entry->id,
        'journal_entry_line_id' => $line->id,
        'description' => 'Test',
    ]);

    expect($payment->fresh()->payment_type)->toBe('income')
        ->and($payment->payment_method)->toBe('cash')
        ->and((float) $payment->amount)->toBe(100.0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingPaymentModelTest.php`

Expected: FAIL (class or table missing)

- [ ] **Step 3: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_payments', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_type', 16);   // income|expense
            $table->string('payment_method', 16); // cash|bank
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('journal_entry_line_id')->constrained('journal_entry_lines')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique('journal_entry_line_id');
            $table->index('payment_date');
            $table->index('payment_type');
            $table->index('payment_method');
            $table->index(['payment_type', 'payment_method', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payments');
    }
};
```

- [ ] **Step 4: Create model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class AccountingPayment extends Model
{
    use UsesTenantConnection;

    protected $fillable = [
        'amount',
        'payment_date',
        'payment_type',
        'payment_method',
        'journal_entry_id',
        'journal_entry_line_id',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function journalEntryLine(): BelongsTo
    {
        return $this->belongsTo(JournalEntryLine::class);
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingPaymentModelTest.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/tenant/2026_10_29_000001_create_accounting_payments_table.php app/Models/AccountingPayment.php tests/Feature/Accounting/AccountingPaymentModelTest.php
git commit -m "feat: add accounting_payments table and model"
```

---

### Task 2: `AccountingPaymentSync` service

**Files:**
- Create: `app/Services/AccountingPaymentSync.php`
- Test: `tests/Feature/Accounting/AccountingPaymentSyncTest.php`

**Interfaces:**
- Produces:
  - `AccountingPaymentSync::syncEntry(JournalEntry $entry): void` — delete existing rows for the entry, then insert one row per line on account code `1100` or `1110` (skip zero amounts). Requires `$entry` to have `id`, `entry_date`, `description`, and `lines.account` available (method loads `lines.account` itself).
  - `AccountingPaymentSync::clearEntry(int $journalEntryId): void` — delete all rows for that journal entry id.
- Mapping: debit > 0 → `income` with amount = debit; else credit > 0 → `expense` with amount = credit; `1100` → `cash`; `1110` → `bank`; other codes ignored.

- [ ] **Step 1: Write failing sync tests**

```php
<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\User;
use App\Services\AccountingPaymentSync;

function apSyncUser(): User
{
    return User::create([
        'name' => 'Sync User',
        'email' => 'ap-sync-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);
}

it('syncs cash debit as income cash', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-02',
        'description' => 'Payment received',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 250.50, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 250.50]);

    AccountingPaymentSync::syncEntry($entry->fresh());

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_type)->toBe('income')
        ->and($row->payment_method)->toBe('cash')
        ->and((float) $row->amount)->toBe(250.50)
        ->and($row->payment_date->format('Y-m-d'))->toBe('2026-10-02');
});

it('syncs bank credit as expense bank', function () {
    $user = apSyncUser();
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);
    $expense = Account::create(['code' => '5100', 'name' => 'Expense', 'type' => 'expense']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-03',
        'description' => 'Bank payout',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $expense->id, 'debit' => 80, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 0, 'credit' => 80]);

    AccountingPaymentSync::syncEntry($entry->fresh());

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_type)->toBe('expense')
        ->and($row->payment_method)->toBe('bank')
        ->and((float) $row->amount)->toBe(80.0);
});

it('creates both sides for a cash to bank transfer', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-04',
        'description' => 'Transfer cash to bank',
        'created_by' => $user->id,
        'entry_type' => 'original',
        'reference_type' => 'Transfer',
    ]);
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 500, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 0, 'credit' => 500]);

    AccountingPaymentSync::syncEntry($entry->fresh());

    $rows = AccountingPayment::where('journal_entry_id', $entry->id)->get();
    expect($rows)->toHaveCount(2);
    expect($rows->firstWhere('payment_method', 'bank')->payment_type)->toBe('income');
    expect($rows->firstWhere('payment_method', 'cash')->payment_type)->toBe('expense');
});

it('clearEntry removes all rows for a journal entry', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-05',
        'description' => 'To clear',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 10, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 10]);
    AccountingPaymentSync::syncEntry($entry->fresh());

    AccountingPaymentSync::clearEntry($entry->id);

    expect(AccountingPayment::where('journal_entry_id', $entry->id)->count())->toBe(0);
});

it('resync replaces prior rows for the same entry', function () {
    $user = apSyncUser();
    $cash = Account::create(['code' => '1100', 'name' => 'Cash', 'type' => 'asset']);
    $bank = Account::create(['code' => '1110', 'name' => 'Bank', 'type' => 'asset']);
    $ar = Account::create(['code' => '1200', 'name' => 'AR', 'type' => 'asset']);

    $entry = JournalEntry::create([
        'entry_date' => '2026-10-06',
        'description' => 'Resync',
        'created_by' => $user->id,
        'entry_type' => 'original',
    ]);
    $entry->lines()->create(['account_id' => $cash->id, 'debit' => 10, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 10]);
    AccountingPaymentSync::syncEntry($entry->fresh());

    $entry->lines()->delete();
    $entry->lines()->create(['account_id' => $bank->id, 'debit' => 20, 'credit' => 0]);
    $entry->lines()->create(['account_id' => $ar->id, 'debit' => 0, 'credit' => 20]);
    AccountingPaymentSync::syncEntry($entry->fresh());

    $row = AccountingPayment::where('journal_entry_id', $entry->id)->sole();
    expect($row->payment_method)->toBe('bank')
        ->and((float) $row->amount)->toBe(20.0);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingPaymentSyncTest.php`

Expected: FAIL (class missing)

- [ ] **Step 3: Implement sync service**

```php
<?php

namespace App\Services;

use App\Models\AccountingPayment;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Facades\DB;

class AccountingPaymentSync
{
    private const CASH_CODES = [
        '1100' => 'cash',
        '1110' => 'bank',
    ];

    public static function clearEntry(int $journalEntryId): void
    {
        AccountingPayment::where('journal_entry_id', $journalEntryId)->delete();
    }

    public static function syncEntry(JournalEntry $entry): void
    {
        $entry->loadMissing('lines.account');

        DB::connection('tenant')->transaction(function () use ($entry) {
            self::clearEntry($entry->id);

            $rows = [];
            foreach ($entry->lines as $line) {
                $mapped = self::mapLine($entry, $line);
                if ($mapped !== null) {
                    $rows[] = $mapped;
                }
            }

            if ($rows !== []) {
                AccountingPayment::query()->insert($rows);
            }
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function mapLine(JournalEntry $entry, JournalEntryLine $line): ?array
    {
        $code = $line->account?->code;
        if ($code === null || ! isset(self::CASH_CODES[$code])) {
            return null;
        }

        $debit = (float) $line->debit;
        $credit = (float) $line->credit;

        if ($debit > 0) {
            $type = 'income';
            $amount = $debit;
        } elseif ($credit > 0) {
            $type = 'expense';
            $amount = $credit;
        } else {
            return null;
        }

        $now = now();

        return [
            'amount' => $amount,
            'payment_date' => $entry->entry_date->format('Y-m-d'),
            'payment_type' => $type,
            'payment_method' => self::CASH_CODES[$code],
            'journal_entry_id' => $entry->id,
            'journal_entry_line_id' => $line->id,
            'description' => $entry->description,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
}
```

Note: if callers already wrap work in a tenant transaction, nested `transaction()` still commits with the outer one in Laravel. Keep as shown.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingPaymentSyncTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/AccountingPaymentSync.php tests/Feature/Accounting/AccountingPaymentSyncTest.php
git commit -m "feat: sync accounting_payments from cash and bank journal lines"
```

---

### Task 3: Wire `AccountingService` (auto posts + reverse)

**Files:**
- Modify: `app/Services/AccountingService.php`
- Test: extend `tests/Feature/Accounting/AccountingPaymentSyncTest.php` (or add `AccountingServicePaymentSyncTest.php`)

**Interfaces:**
- Consumes: `AccountingPaymentSync::syncEntry`, `AccountingPaymentSync::clearEntry`
- After every method that creates a journal entry with lines, call `AccountingPaymentSync::syncEntry($entry)` once lines exist (reload entry with lines if needed).
- In `reverseEntry`: after marking original `superseded`, call `AccountingPaymentSync::clearEntry($originalEntry->id)`; after creating the reversal and its lines, call `AccountingPaymentSync::syncEntry($reversal)`.

Call sites to update inside `AccountingService` (grep and touch each that creates lines on 1100/1110):

- `reverseEntry`
- `postPaymentEntry`
- `postAdmissionAdvanceEntry`
- `postAdvanceApplicationEntry` (no cash — sync is a no-op; still safe to call)
- `postAdvanceRefundEntry` (or whatever the refund method is named)
- `postPurchaseEntry` (no cash usually — safe no-op)
- `postBillEntry` / `postBillEntryFresh` (AR only — safe no-op)
- Any other `JournalEntry::create` + `lines()->create` in this file

Pattern after lines + sub-ledger writes:

```php
AccountingPaymentSync::syncEntry($entry->fresh(['lines.account']));
```

For `reverseEntry` specifically:

```php
$originalEntry->update(['entry_type' => 'superseded']);
AccountingPaymentSync::clearEntry($originalEntry->id);
// ... create $reversal and lines ...
AccountingPaymentSync::syncEntry($reversal->fresh(['lines.account']));
```

- [ ] **Step 1: Write failing integration tests** in `tests/Feature/Accounting/AccountingServicePaymentSyncTest.php`

Use the same account/patient/bill bootstrap as `tests/Feature/Accounting/PaymentEditTest.php` `beforeEach` (copy those creates verbatim into this file’s `beforeEach`).

```php
<?php

use App\Models\Account;
use App\Models\AccountingPayment;
use App\Models\Bill;
use App\Models\JournalEntry;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\AccountingService;

beforeEach(function () {
    $this->user = User::create([
        'name' => 'Test User',
        'email' => 'apsvc-'.uniqid().'@example.com',
        'password' => bcrypt('password'),
        'email_verified_at' => now(),
    ]);

    Account::create(['code' => '1100', 'name' => 'Cash in Hand', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1110', 'name' => 'Bank Account', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '1200', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_system' => true]);
    Account::create(['code' => '2100', 'name' => 'Tax Payable', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '2300', 'name' => 'Advance from Patients', 'type' => 'liability', 'is_system' => true]);
    Account::create(['code' => '4100', 'name' => 'OPD Revenue', 'type' => 'revenue', 'is_system' => true]);
    Account::create(['code' => '5200', 'name' => 'Discounts Given', 'type' => 'expense', 'is_system' => true]);

    $this->patient = Patient::create([
        'name' => 'AP Sync Patient',
        'gender' => 'male',
        'age' => 30,
        'phone' => '03003333333',
    ]);

    $this->bill = Bill::create([
        'patient_id' => $this->patient->id,
        'bill_number' => 'BILL-AP-001',
        'bill_date' => now(),
        'bill_type' => 'opd',
        'subtotal' => 10000,
        'tax_amount' => 0,
        'discount_amount' => 0,
        'total_amount' => 10000,
        'paid_amount' => 0,
        'due_amount' => 10000,
        'status' => 'pending',
        'created_by' => $this->user->id,
    ]);

    AccountingService::postBillEntry($this->bill);
});

it('posts a bill payment and creates an accounting payment row', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 5000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    $this->bill->update(['paid_amount' => 5000, 'due_amount' => 5000, 'status' => 'partial']);
    AccountingService::postPaymentEntry($payment);

    $row = AccountingPayment::where('payment_type', 'income')->where('payment_method', 'cash')->sole();
    expect((float) $row->amount)->toBe(5000.0);
});

it('clears original and syncs reversal when reversing a cash payment entry', function () {
    $payment = Payment::create([
        'bill_id' => $this->bill->id,
        'payment_date' => now(),
        'amount' => 4000,
        'payment_method' => 'cash',
        'received_by' => $this->user->id,
    ]);
    AccountingService::postPaymentEntry($payment);

    $original = JournalEntry::where('reference_type', 'Payment')
        ->where('reference_id', $payment->id)
        ->where('entry_type', 'original')
        ->with(['lines', 'subLedgerEntries'])
        ->firstOrFail();

    $originalId = $original->id;
    $reversal = AccountingService::reverseEntry($original, 'test reverse');
    expect($reversal)->not->toBeNull();

    expect(AccountingPayment::where('journal_entry_id', $originalId)->count())->toBe(0);

    $revRow = AccountingPayment::where('journal_entry_id', $reversal->id)->sole();
    expect($revRow->payment_type)->toBe('expense')
        ->and($revRow->payment_method)->toBe('cash')
        ->and((float) $revRow->amount)->toBe(4000.0);
});
```

- [ ] **Step 2: Run tests — expect FAIL** (no rows until wired)

Run: `php artisan test --compact tests/Feature/Accounting/AccountingServicePaymentSyncTest.php`

Expected: FAIL asserting `sole()` / count on `accounting_payments`

- [ ] **Step 3: Wire all AccountingService call sites**

Add `use App\Services\AccountingPaymentSync;` and the sync/clear calls as specified. Grep `JournalEntry::create` in `AccountingService.php` and ensure every create path that attaches lines ends with `AccountingPaymentSync::syncEntry(...)`.

- [ ] **Step 4: Run tests — expect PASS**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingServicePaymentSyncTest.php`

- [ ] **Step 5: Commit**

```bash
git add app/Services/AccountingService.php tests/Feature/Accounting/AccountingServicePaymentSyncTest.php
git commit -m "feat: sync accounting payments from AccountingService posts"
```

---

### Task 4: Wire `AccountingController` deposit, transfer, manual JE

**Files:**
- Modify: `app/Http/Controllers/AccountingController.php` (`processDeposit`, `processTransfer`, `storeJournalEntry`, `updateJournalEntry`)
- Test: `tests/Feature/Accounting/AccountingPaymentControllerWriteSyncTest.php`

**Interfaces:**
- Consumes: `AccountingPaymentSync::syncEntry`
- Inside each existing tenant `transaction` closure, after lines are created (and after line delete+recreate on update), call `AccountingPaymentSync::syncEntry($entry->fresh(['lines.account']))`.

- [ ] **Step 1: Write failing HTTP sync tests**

Use `accountingPermissionUser` / `bindAccountingTenant` patterns from `tests/Feature/Permissions/AccountingPermissionsTest.php`. Grant `create deposits` / `create transfers` / `create journal entries` as needed. Seed accounts `1100` and `1110`.

Example deposit assertion:

```php
it('records an accounting payment when depositing into cash', function () {
    bindAccountingTenant();
    // create 1100 + a source revenue/liability account
    $this->actingAs(accountingPermissionUser(['create deposits']));

    $this->post(route('accounting.process-deposit'), [
        'to_account_id' => $cash->id,
        'from_account_id' => $source->id,
        'amount' => 150,
        'date' => '2026-10-10',
        'description' => 'Daily deposit',
    ])->assertRedirect();

    expect(\App\Models\AccountingPayment::query()->count())->toBe(1)
        ->and(\App\Models\AccountingPayment::first()->payment_type)->toBe('income')
        ->and(\App\Models\AccountingPayment::first()->payment_method)->toBe('cash');
});
```

Add similar tests for cash↔bank transfer (2 rows) and manual JE store with a cash debit.

- [ ] **Step 2: Run — expect FAIL**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingPaymentControllerWriteSyncTest.php`

- [ ] **Step 3: Add sync calls inside controller transactions**

- [ ] **Step 4: Run — expect PASS**

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AccountingController.php tests/Feature/Accounting/AccountingPaymentControllerWriteSyncTest.php
git commit -m "feat: sync accounting payments from deposits transfers and manual journals"
```

---

### Task 5: Wire `PayrollService::postToAccounting`

**Files:**
- Modify: `app/Services/PayrollService.php` — after the cash/deductions lines are created and before the method returns successfully, call `AccountingPaymentSync::syncEntry($entry->fresh(['lines.account']))`
- Modify: `tests/Feature/HR/PayrollServiceTest.php` — extend `it('posts payroll to accounting using employee expense accounts')`

**Interfaces:**
- Consumes: `AccountingPaymentSync::syncEntry`

- [ ] **Step 1: Extend the existing payroll accounting test with a failing assertion**

In `tests/Feature/HR/PayrollServiceTest.php`, inside the test that already runs `PayrollService::postToAccounting($run)`, add:

```php
use App\Models\AccountingPayment;

// after postToAccounting($run):
$row = AccountingPayment::where('payment_type', 'expense')
    ->where('payment_method', 'cash')
    ->where('amount', $run->total_net)
    ->first();

expect($row)->not->toBeNull();
```

- [ ] **Step 2: Run — FAIL**

Run: `php artisan test --compact --filter="posts payroll to accounting using employee expense accounts"`

Expected: FAIL (`$row` is null)

- [ ] **Step 3: Add sync call at end of successful `postToAccounting` path** (after all lines exist; skip if early-return paths delete the entry)

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Commit**

```bash
git add app/Services/PayrollService.php tests/Feature/HR/PayrollServiceTest.php
git commit -m "feat: sync accounting payments from payroll posting"
```

---

### Task 6: Permission registration + HA backfill command

**Files:**
- Modify: `database/seeders/RolePermissionSeeder.php` — add `'view accounting payments'` to `PERMISSIONS` (after journal entries block) and to Hospital Administrator `ROLE_PERMISSIONS` next to `'view journal entries'`
- Modify: `app/Support/PermissionRegistry.php` — under `accounting` → new group:

```php
'payments' => [
    'view accounting payments',
],
```

- Create: `app/Console/Commands/BackfillViewAccountingPaymentsPermission.php` — clone structure from `BackfillManageTheatresPermission.php` with:
  - `PERMISSION_NAME = 'view accounting payments'`
  - `TARGET_ROLES = ['Super Admin', 'Hospital Administrator']`
  - signature `rbac:backfill-view-accounting-payments {--dry-run}`
- Test: `tests/Feature/Commands/BackfillViewAccountingPaymentsPermissionTest.php` (mirror an existing backfill command test if present; otherwise assert dry-run writes nothing and live run grants the permission to HA)

**Interfaces:**
- Produces: permission string available to seeder, registry, and `php artisan rbac:backfill-view-accounting-payments`

- [ ] **Step 1: Write failing seeder/registry/backfill tests** in `tests/Feature/Commands/BackfillViewAccountingPaymentsPermissionTest.php`

Clone structure from `tests/Feature/Commands/BackfillManageTheatresPermissionTest.php` (landlord tenant helper, role seed helper, dry-run vs live assertions). Replace permission/role names:

```php
<?php

use App\Support\PermissionRegistry;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('registers view accounting payments under the accounting module', function () {
    expect(PermissionRegistry::forModule('accounting'))->toContain('view accounting payments')
        ->and(PermissionRegistry::flat())->toContain('view accounting payments');
});

it('lists view accounting payments in the seeder catalog', function () {
    expect(RolePermissionSeeder::catalogPermissions())->toContain('view accounting payments');
});

it('seeds view accounting payments for Super Admin and Hospital Administrator', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Permission::where('name', 'view accounting payments')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(Role::findByName('Super Admin', 'web')->hasPermissionTo('view accounting payments'))->toBeTrue()
        ->and(Role::findByName('Hospital Administrator', 'web')->hasPermissionTo('view accounting payments'))->toBeTrue();
});

// Plus dry-run / live backfill cases copied from BackfillManageTheatresPermissionTest,
// swapping manage theatres → view accounting payments and command signature
// rbac:backfill-view-accounting-payments.
```

- [ ] **Step 2: Run — FAIL**

Run: `php artisan test --compact tests/Feature/Commands/BackfillViewAccountingPaymentsPermissionTest.php`

- [ ] **Step 3: Implement seeder, registry, command**

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Commit**

```bash
git add database/seeders/RolePermissionSeeder.php app/Support/PermissionRegistry.php app/Console/Commands/BackfillViewAccountingPaymentsPermission.php tests/Feature/Commands/BackfillViewAccountingPaymentsPermissionTest.php
git commit -m "feat: register view accounting payments permission"
```

---

### Task 7: Index route, controller, view, sidebar

**Files:**
- Modify: `routes/web.php` — inside accounting group, after journal-entries block:

```php
Route::get('payments', [AccountingController::class, 'payments'])
    ->name('payments')
    ->middleware('permission:view accounting payments|view accounting');
```

- Modify: `app/Http/Controllers/AccountingController.php` — add:

```php
public function payments(Request $request)
{
    $query = \App\Models\AccountingPayment::query()
        ->orderByDesc('payment_date')
        ->orderByDesc('id');

    if ($request->filled('payment_type') && in_array($request->payment_type, ['income', 'expense'], true)) {
        $query->where('payment_type', $request->payment_type);
    }

    if ($request->filled('payment_method') && in_array($request->payment_method, ['cash', 'bank'], true)) {
        $query->where('payment_method', $request->payment_method);
    }

    $payments = $query->paginate(20)->withQueryString();

    return view('admin.accounting.payments', compact('payments'));
}
```

Do **not** eager-load relations for the required columns.

- Create: `resources/views/admin/accounting/payments.blade.php` — mirror journal-entries card/filter/table:
  - Filters: select `payment_type` (All / Income / Expense), select `payment_method` (All / Cash / Bank), GET submit
  - Columns: Amount (`format_currency`), Payment Date (`Y-m-d`), Payment Type badge, Payment Method label
  - Income: `bg-green-100 text-green-800` (or existing success badge classes in the app) + `<i class="fas fa-arrow-down">` (in)
  - Expense: danger badge classes + `<i class="fas fa-arrow-up">` (out)
  - No create/edit/delete buttons

Match existing Tailwind/badge conventions already used in accounting Blade files (grep `bg-green` / `bg-red` in `resources/views/admin`).

- Modify: `app/Services/SidebarService.php` — after Journal Entries item:

```php
if ($user->can('view accounting payments') || $user->can('view accounting')) {
    $items[] = $this->item('Payments', 'fa-money-bill-wave', 'accounting.payments', ['accounting.payments']);
}
```

- Test: `tests/Feature/Accounting/AccountingPaymentsIndexTest.php`

**Interfaces:**
- Produces: named route `accounting.payments`; sidebar label `Payments`

- [ ] **Step 1: Write failing index/auth/sidebar tests**

```php
it('forbids payments index without permission', function () {
    bindAccountingTenant();
    $this->actingAs(accountingPermissionUser(['view chart of accounts']));
    $this->get(route('accounting.payments'))->assertForbidden();
});

it('allows payments index with view accounting payments', function () {
    bindAccountingTenant();
    $this->actingAs(accountingPermissionUser(['view accounting payments']));
    $this->get(route('accounting.payments'))->assertOk();
});

it('allows payments index with deprecated view accounting', function () {
    bindAccountingTenant();
    $this->actingAs(accountingPermissionUser(['view accounting']));
    $this->get(route('accounting.payments'))->assertOk();
});

it('shows payments sidebar item when permitted', function () {
    // Follow existing SidebarService test pattern if one exists; otherwise hit a layout page
    // and assertSee('Payments') / assertDontSee for unpermitted user.
});
```

Reuse `bindAccountingTenant` / `accountingPermissionUser` from `AccountingPermissionsTest.php` (extract to Pest.php only if duplication is already the project pattern — otherwise copy the helpers into the new test file to avoid cross-file helper collisions; Pest helpers are global — **grep before adding**).

- [ ] **Step 2: Run — FAIL**

Run: `php artisan test --compact tests/Feature/Accounting/AccountingPaymentsIndexTest.php`

- [ ] **Step 3: Implement route, controller method, Blade, sidebar**

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/AccountingController.php resources/views/admin/accounting/payments.blade.php app/Services/SidebarService.php tests/Feature/Accounting/AccountingPaymentsIndexTest.php
git commit -m "feat: add read-only accounting payments index under Accounting"
```

---

### Task 8: Filters + N+1 guard on index

**Files:**
- Modify: `tests/Feature/Accounting/AccountingPaymentsIndexTest.php` (and controller/view only if gaps found)

**Interfaces:**
- Consumes: index query from Task 7

- [ ] **Step 1: Write failing filter + query-count tests**

```php
it('filters by payment type and method', function () {
    bindAccountingTenant();
    $user = accountingPermissionUser(['view accounting payments']);
    $this->actingAs($user);

    AccountingPayment::create([/* income/cash */]);
    AccountingPayment::create([/* expense/bank */]);
    // Minimal: create two rows with required FKs (journal entries/lines/accounts) or
    // insert via syncEntry helpers from Task 2.

    $this->get(route('accounting.payments', ['payment_type' => 'income', 'payment_method' => 'cash']))
        ->assertOk()
        ->assertSee(/* income amount */)
        ->assertDontSee(/* expense amount */);
});

it('does not N+1 when listing many accounting payments', function () {
    bindAccountingTenant();
    $this->actingAs(accountingPermissionUser(['view accounting payments']));

    // Create >= 5 accounting_payments rows (via syncEntry).
    DB::connection('tenant')->enableQueryLog();
    $this->get(route('accounting.payments'))->assertOk();
    $queries = DB::connection('tenant')->getQueryLog();
    // Assert no per-row selects against journal_entry_lines / accounts.
    $relationHits = collect($queries)->filter(function ($q) {
        $sql = strtolower($q['query']);
        return str_contains($sql, 'journal_entry_lines')
            || (str_contains($sql, 'from `accounts`') || str_contains($sql, 'from "accounts"'));
    });
    expect($relationHits)->toHaveCount(0);
});
```

If the app already uses `$this->expectsDatabaseQueryCount()`, prefer that with a documented ceiling measured once after a green run (record the exact number in the test comment).

- [ ] **Step 2: Run — FAIL if filters/N+1 broken**

- [ ] **Step 3: Fix controller/view only as needed** (no relation access in Blade for required columns)

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/Accounting/AccountingPaymentsIndexTest.php app/Http/Controllers/AccountingController.php resources/views/admin/accounting/payments.blade.php
git commit -m "test: cover accounting payments filters and N+1 guard"
```

---

### Task 9: Historical data backfill command

**Files:**
- Create: `app/Console/Commands/BackfillAccountingPayments.php`
- Test: `tests/Feature/Accounting/BackfillAccountingPaymentsTest.php`

**Interfaces:**
- Signature: `accounting:backfill-payments {--dry-run}`
- Behavior: chunk journal entry lines joined to accounts where `accounts.code` in (`1100`,`1110`) and parent entry `entry_type` != `superseded`; for each missing `journal_entry_line_id`, insert mapped row (same mapping as sync). Idempotent. Dry-run inserts nothing.

```php
// Core query shape (adjust to project style):
JournalEntryLine::query()
    ->whereHas('account', fn ($q) => $q->whereIn('code', ['1100', '1110']))
    ->whereHas('journalEntry', fn ($q) => $q->where('entry_type', '!=', 'superseded'))
    ->with(['account', 'journalEntry'])
    ->orderBy('id')
    ->chunkById(200, function ($lines) use ($dryRun) { ... });
```

- [ ] **Step 1: Write failing tests**

```php
it('backfills missing accounting payments from existing cash lines', function () {
    // create JE + cash line WITHOUT calling sync
    // run artisan accounting:backfill-payments
    // assert row exists
});

it('is idempotent and dry-run writes nothing', function () {
    // sync already present OR backfill once
    // dry-run → count unchanged
    // second live run → count unchanged
});
```

- [ ] **Step 2: Run — FAIL**

- [ ] **Step 3: Implement command**

- [ ] **Step 4: Run — PASS**

- [ ] **Step 5: Commit**

```bash
git add app/Console/Commands/BackfillAccountingPayments.php tests/Feature/Accounting/BackfillAccountingPaymentsTest.php
git commit -m "feat: backfill accounting_payments from existing journal lines"
```

---

### Task 10: Deploy notes

**Files:**
- Create: `docs/superpowers/deploy-notes/2026-10-29-accounting-payments-readonly.md`

Content must include, in order:

1. Deploy code on `feat/accounting-payments-readonly`
2. Run tenant migrations (`accounting_payments`)
3. `php artisan rbac:backfill-view-accounting-payments` (then `--dry-run` first in staging)
4. `php artisan accounting:backfill-payments` (dry-run first)
5. Smoke: Accounting → Payments visible for HA; filters work; a new bill payment creates a row

- [ ] **Step 1: Write the deploy notes file**

- [ ] **Step 2: Commit with force-add**

```bash
git add -f docs/superpowers/deploy-notes/2026-10-29-accounting-payments-readonly.md
git commit -m "docs: add accounting payments readonly deploy notes"
```

---

## Plan Self-Review

| Spec requirement | Task |
|------------------|------|
| Denormalized `accounting_payments` | Task 1 |
| Sync mapping income/expense + cash/bank | Task 2 |
| Auto from accounting write paths | Tasks 3–5 |
| Supersede clear + reversal sync | Task 3 |
| Transfers both sides | Task 2 (+ Task 4) |
| Read-only index + badges/arrows + filters | Task 7–8 |
| Only 1100/1110 | Task 2 |
| Permission no gaps (seeder, registry, route, sidebar, backfill) | Tasks 6–7 |
| N+1 avoidance | Tasks 7–8 |
| Historical backfill | Task 9 |
| Isolation / deploy | Global Constraints + Task 10 |

Placeholder scan: no TBD/TODO left; Task 3 and Task 6 include full test bodies against real APIs (`RolePermissionSeeder::catalogPermissions()`, `PermissionRegistry::forModule` / `flat()`).

Type consistency: `AccountingPaymentSync::syncEntry(JournalEntry $entry): void`, `clearEntry(int $journalEntryId): void`, permission string `view accounting payments`, route `accounting.payments` used uniformly.
