<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class CreateTenantDatabase implements ShouldQueue, NotTenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        $dbPath = $this->tenant->database;

        Log::info("[Provisioning] Creating SQLite database: {$dbPath}", [
            'tenant_id' => $this->tenant->id,
        ]);

        // Ensure the tenants directory exists
        $dir = dirname($dbPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create the empty SQLite file
        if (! file_exists($dbPath)) {
            touch($dbPath);
        }

        Log::info("[Provisioning] SQLite database created: {$dbPath}");
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[Provisioning] Failed to create database for tenant {$this->tenant->id}: {$e->getMessage()}");

        $this->tenant->update(['status' => 'failed']);
    }
}
