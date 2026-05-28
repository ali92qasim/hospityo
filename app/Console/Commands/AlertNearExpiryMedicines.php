<?php

namespace App\Console\Commands;

use App\Models\InventoryTransaction;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\NearExpiryMedicineAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AlertNearExpiryMedicines extends Command
{
    protected $signature = 'pharmacy:alert-near-expiry
                            {--months=6 : Number of months threshold for expiry warning}
                            {--tenant=  : Slug of a specific tenant to process (omit for all active tenants)}';

    protected $description = 'Send near-expiry medicine alerts to admins and pharmacists for all active tenants';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        $slug   = $this->option('tenant');

        if ($months < 1 || $months > 24) {
            $this->error('--months must be between 1 and 24.');
            return self::FAILURE;
        }

        try {
            $tenants = $slug
                ? Tenant::where('slug', $slug)->get()
                : Tenant::where('status', 'active')->get();
        } catch (\Throwable $e) {
            $this->error('Failed to load tenants: ' . $e->getMessage());
            Log::error('[NearExpiry] Failed to load tenants', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }

        if ($tenants->isEmpty()) {
            $this->warn('No active tenants found' . ($slug ? " matching slug '{$slug}'" : '') . '.');
            return self::SUCCESS;
        }

        $this->info("Processing {$tenants->count()} tenant(s) with {$months}-month threshold...");

        foreach ($tenants as $tenant) {
            $this->processTenant($tenant, $months);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function processTenant(Tenant $tenant, int $months): void
    {
        $this->line("\n[{$tenant->slug}] Checking near-expiry stock...");

        try {
            $tenant->makeCurrent();

            $batches = InventoryTransaction::nearExpiry($months)->get();

            if ($batches->isEmpty()) {
                $this->info("[{$tenant->slug}] No near-expiry batches found. Skipping.");
                return;
            }

            $this->info("[{$tenant->slug}] Found {$batches->count()} near-expiry batch(es). Notifying recipients...");

            // Notify Super Admin, Hospital Administrator, and Pharmacist roles
            $recipients = User::role(['Super Admin', 'Hospital Administrator', 'Pharmacist'])->get();

            if ($recipients->isEmpty()) {
                $this->warn("[{$tenant->slug}] No eligible recipients found (Super Admin / Hospital Administrator / Pharmacist).");
                return;
            }

            foreach ($recipients as $user) {
                $this->notifyUser($user, $batches, $months, $tenant->slug);
            }

        } catch (\Throwable $e) {
            Log::error('[NearExpiry] Command failed for tenant', [
                'tenant' => $tenant->slug,
                'error'  => $e->getMessage(),
                'trace'  => $e->getTraceAsString(),
            ]);
            $this->error("[{$tenant->slug}] Error: {$e->getMessage()}");
        } finally {
            // Always release the tenant context, even if an exception occurred
            Tenant::forgetCurrent();
        }
    }

    private function notifyUser(User $user, $batches, int $months, string $tenantSlug): void
    {
        try {
            $user->notify(new NearExpiryMedicineAlert($batches, $months));
            $this->line("  ✓ Notified: {$user->name} <{$user->email}>");
        } catch (\Throwable $e) {
            Log::error('[NearExpiry] Failed to notify user', [
                'user_id' => $user->id,
                'email'   => $user->email,
                'tenant'  => $tenantSlug,
                'error'   => $e->getMessage(),
            ]);
            $this->warn("  ✗ Failed to notify {$user->email}: {$e->getMessage()}");
            // Continue to next user — one failure must not block others
        }
    }
}
