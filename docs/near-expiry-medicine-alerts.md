# Near-Expiry Medicine Alert System

## Overview

Medicines with 6 months or less remaining before their expiry date must be flagged for return to the supplier. The system needs to:

1. Detect batches approaching expiry (≤ 6 months remaining, with remaining stock > 0)
2. Alert the Hospital Administrator, Super Admin, and any user with the Pharmacist role
3. Surface the alert inside the application (dashboard banner + dedicated page)
4. Optionally send an email notification via a scheduled daily job

---

## Current State

The `InventoryController::expiring()` method already exists and queries `inventory_transactions` for batches expiring within 3 months. It passes them to `resources/views/admin/inventory/expiring.blade.php`. However:

- The threshold is 3 months, not 6 months
- There is no notification to users — it is a passive page only
- There is no scheduled job to proactively alert anyone
- The page does not distinguish between batches that still have stock vs fully consumed batches

The `inventory_transactions` table already has `batch_no`, `expiry_date`, and `remaining_quantity` (added in the FIFO migration). This is the correct data source — no new columns are needed.

---

## Data Source

Query: `inventory_transactions` where:
- `type = 'stock_in'`
- `expiry_date IS NOT NULL`
- `expiry_date <= now() + 6 months`
- `expiry_date > now()` (not yet expired — expired batches are a separate concern)
- `remaining_quantity > 0` (only batches that still have stock to return)

Order by `expiry_date ASC` so the most urgent batches appear first.

---

## Implementation Plan

### 1. Fix the existing `expiring()` method in `InventoryController`

Change the threshold from 3 months to 6 months and add the `remaining_quantity > 0` filter:

```php
public function expiring()
{
    $expiringStock = InventoryTransaction::with(['medicine'])
        ->where('type', 'stock_in')
        ->whereNotNull('expiry_date')
        ->where('expiry_date', '<=', now()->addMonths(6))   // changed from 3 to 6
        ->where('expiry_date', '>', now())
        ->where('remaining_quantity', '>', 0)               // only batches with stock
        ->orderBy('expiry_date')
        ->get();

    return view('admin.inventory.expiring', compact('expiringStock'));
}
```

No frontend change needed — the view already renders whatever is passed to it.

---

### 2. Add a `getNearExpiryBatches()` scope/method to `InventoryTransaction`

Add a reusable query method so the same logic is not duplicated across the dashboard, the scheduled job, and the controller:

```php
// In InventoryTransaction model

/**
 * Returns stock_in batches expiring within the given number of months
 * that still have remaining stock available.
 */
public static function nearExpiry(int $months = 6)
{
    return static::with(['medicine'])
        ->where('type', 'stock_in')
        ->whereNotNull('expiry_date')
        ->where('expiry_date', '<=', now()->addMonths($months))
        ->where('expiry_date', '>', now())
        ->where('remaining_quantity', '>', 0)
        ->orderBy('expiry_date', 'asc');
}
```

---

### 3. Dashboard alert banner

Inject the near-expiry count into the dashboard so admins and pharmacists see it immediately on login without navigating to the inventory page.

In the dashboard controller (or the dashboard route closure in `routes/web.php`), add:

```php
$nearExpiryCount = \App\Models\InventoryTransaction::nearExpiry(6)->count();
```

Pass it to the dashboard view. In the blade template, render a dismissible warning banner when `$nearExpiryCount > 0`:

```blade
@if($nearExpiryCount > 0)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6 flex items-center justify-between">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle text-amber-500 mr-3"></i>
        <span class="text-sm text-amber-800 font-medium">
            {{ $nearExpiryCount }} medicine batch{{ $nearExpiryCount > 1 ? 'es are' : ' is' }}
            expiring within 6 months and need to be returned.
        </span>
    </div>
    <a href="{{ route('inventory.expiring') }}"
       class="text-xs text-amber-700 underline hover:text-amber-900 ml-4 whitespace-nowrap">
        View Details
    </a>
</div>
@endif
```

This banner is visible to all authenticated users. Role-based visibility (pharmacist only) can be added with `@can` or `@hasrole` if needed.

---

### 4. Laravel Notification class — `NearExpiryMedicineAlert`

Create `app/Notifications/NearExpiryMedicineAlert.php`:

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class NearExpiryMedicineAlert extends Notification
{
    use Queueable;

    public function __construct(
        protected Collection $batches,
        protected int $months = 6
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("⚠️ {$this->batches->count()} Medicine Batch(es) Expiring Within {$this->months} Months")
            ->greeting("Hello {$notifiable->name},")
            ->line("The following medicine batches are expiring within {$this->months} months and still have remaining stock. Please arrange for return to the supplier.");

        foreach ($this->batches->take(20) as $batch) {
            $message->line(
                "• {$batch->medicine->name} | Batch: {$batch->batch_no} | " .
                "Expiry: {$batch->expiry_date->format('d M Y')} | " .
                "Remaining: {$batch->remaining_quantity} units"
            );
        }

        if ($this->batches->count() > 20) {
            $message->line('... and ' . ($this->batches->count() - 20) . ' more batches.');
        }

        return $message
            ->action('View Expiring Stock', url('/inventory/expiring'))
            ->line('Please take action before these medicines expire.');
    }
}
```

---

### 5. Artisan command — `pharmacy:alert-near-expiry`

Create `app/Console/Commands/AlertNearExpiryMedicines.php`:

```php
<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NearExpiryMedicineAlert;
use Illuminate\Console\Command;

class AlertNearExpiryMedicines extends Command
{
    protected $signature   = 'pharmacy:alert-near-expiry {--months=6 : Months threshold} {--tenant= : Specific tenant slug}';
    protected $description = 'Send near-expiry medicine alerts to admins and pharmacists for all tenants';

    public function handle(): int
    {
        $months  = (int) $this->option('months');
        $slug    = $this->option('tenant');
        $tenants = $slug ? Tenant::where('slug', $slug)->get() : Tenant::where('status', 'active')->get();

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found.');
            return self::SUCCESS;
        }

        foreach ($tenants as $tenant) {
            $tenant->makeCurrent();

            try {
                $batches = InventoryTransaction::nearExpiry($months)->get();

                if ($batches->isEmpty()) {
                    $this->info("[{$tenant->slug}] No near-expiry batches found.");
                    Tenant::forgetCurrent();
                    continue;
                }

                $this->info("[{$tenant->slug}] Found {$batches->count()} near-expiry batch(es). Notifying users...");

                // Notify users with Hospital Administrator or Super Admin role,
                // and any user with a role named 'Pharmacist'
                $recipients = User::role(['Super Admin', 'Hospital Administrator', 'Pharmacist'])->get();

                foreach ($recipients as $user) {
                    try {
                        $user->notify(new NearExpiryMedicineAlert($batches, $months));
                        $this->line("  ✓ Notified: {$user->email}");
                    } catch (\Throwable $e) {
                        \Log::error('[NearExpiry] Failed to notify user', [
                            'user_id' => $user->id,
                            'tenant'  => $tenant->slug,
                            'error'   => $e->getMessage(),
                        ]);
                        $this->warn("  ✗ Failed to notify: {$user->email}");
                    }
                }

            } catch (\Throwable $e) {
                \Log::error('[NearExpiry] Command failed for tenant', [
                    'tenant' => $tenant->slug,
                    'error'  => $e->getMessage(),
                ]);
                $this->error("[{$tenant->slug}] Error: {$e->getMessage()}");
            }

            Tenant::forgetCurrent();
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
```

---

### 6. Schedule the command — `routes/console.php`

The app uses Laravel 11's modern bootstrap style (no `Console\Kernel`). Add the schedule in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

// Run every morning at 8:00 AM
Schedule::command('pharmacy:alert-near-expiry')->dailyAt('08:00');
```

To activate the scheduler, add this to the server's crontab (one entry covers all scheduled tasks):

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

---

### 7. Role check — ensure 'Pharmacist' role exists

The command notifies users with the `Pharmacist` role. Add this role to `RolePermissionSeeder` if it doesn't already exist:

```php
// In RolePermissionSeeder::ROLE_PERMISSIONS
'Pharmacist' => [
    'view patients',
    'view visits',
    'view bills',
    'create bills',
    'create payments',
    'view services',
],
```

Re-run `php artisan tenants:sync-permissions` after adding it.

---

## Files to Create / Modify

| File | Action | Description |
|------|--------|-------------|
| `app/Models/InventoryTransaction.php` | Edit | Add `nearExpiry()` static method |
| `app/Http/Controllers/InventoryController.php` | Edit | Change threshold to 6 months, add `remaining_quantity > 0` filter |
| `app/Notifications/NearExpiryMedicineAlert.php` | Create | Mail notification class |
| `app/Console/Commands/AlertNearExpiryMedicines.php` | Create | Artisan command |
| `routes/console.php` | Edit | Register daily schedule |
| `resources/views/admin/dashboard.blade.php` | Edit | Add banner (count injection needed in dashboard controller/route) |
| `database/seeders/RolePermissionSeeder.php` | Edit | Add Pharmacist role if missing |

## Files NOT to Change

- `resources/views/admin/inventory/expiring.blade.php` — the view already works, only the data passed to it changes
- Any other frontend views

---

## Testing Checklist

After implementation, manually verify:

1. Navigate to **Inventory → Expiring Stock** — batches expiring within 6 months with remaining stock should appear.
2. Log in as a Hospital Administrator — the dashboard should show the amber banner with the correct count.
3. Run `php artisan pharmacy:alert-near-expiry --tenant=your-slug` — emails should be sent to admin and pharmacist users.
4. Run `php artisan pharmacy:alert-near-expiry --months=1` — only batches expiring within 1 month should trigger notifications.
5. A batch with `remaining_quantity = 0` should NOT appear in the list or trigger a notification.
6. A batch with `expiry_date` in the past should NOT appear (it is already expired, not near-expiry).
