<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class MigrateTenantDatabase implements ShouldQueue, NotTenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        Log::info("[Provisioning] Running migrations for tenant: {$this->tenant->slug}", [
            'tenant_id' => $this->tenant->id,
            'database'  => $this->tenant->database,
        ]);

        // Make this tenant current so the 'tenant' connection points to its DB
        $this->tenant->makeCurrent();

        Artisan::call('migrate', [
            '--path'     => 'database/migrations/tenant',
            '--database' => 'tenant',
            '--force'    => true,
        ]);

        Log::info("[Provisioning] Migrations complete for tenant: {$this->tenant->slug}");

        Tenant::forgetCurrent();
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[Provisioning] Migration failed for tenant {$this->tenant->id}: {$e->getMessage()}");

        $this->tenant->update(['status' => 'failed']);
        Tenant::forgetCurrent();
    }
}
