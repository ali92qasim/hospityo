<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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
        $database = $this->tenant->database;
        $driver = config('database.connections.tenant.driver', 'sqlite');

        Log::info("[Provisioning] Creating {$driver} database: {$database}", [
            'tenant_id' => $this->tenant->id,
        ]);

        try {
            if ($driver === 'sqlite') {
                $this->createSqliteDatabase($database);
            } else {
                $this->createMysqlDatabase($database);
            }

            Log::info("[Provisioning] Database created: {$database}");
        } catch (\Throwable $e) {
            Log::error("[Provisioning] Failed to create database: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function createSqliteDatabase(string $path): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        if (! file_exists($path)) {
            touch($path);
        }
    }

    protected function createMysqlDatabase(string $database): void
    {
        DB::connection('landlord')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );
    }

    public function failed(\Throwable $e): void
    {
        Log::error("[Provisioning] Failed to create database for tenant {$this->tenant->id}: {$e->getMessage()}");
        $this->tenant->update(['status' => 'failed']);
    }
}
