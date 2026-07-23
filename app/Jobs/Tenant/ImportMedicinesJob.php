<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use App\Services\MedicineImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class ImportMedicinesJob implements ShouldQueue, NotTenantAware
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 1800;

    public function __construct(
        private readonly string $storagePath,
        private readonly string $cacheKey,
        private readonly int $userId,
        private readonly int $tenantId,
    ) {}

    public function handle(MedicineImportService $service): void
    {
        $this->activateTenant();

        Cache::put($this->cacheKey, [
            'status' => 'processing',
            'processed' => 0,
            'total' => 0,
            'created' => 0,
            'updated' => 0,
        ], now()->addMinutes(30));

        $fullPath = Storage::path($this->storagePath);

        try {
            $result = $service->importFromFile($fullPath, function (
                int $processed,
                int $total,
                int $created,
                int $updated,
            ) {
                Cache::put($this->cacheKey, [
                    'status' => 'processing',
                    'processed' => $processed,
                    'total' => $total,
                    'created' => $created,
                    'updated' => $updated,
                ], now()->addMinutes(30));
            });

            if ($result['errors'] !== [] && $result['created'] === 0 && $result['updated'] === 0 && ($result['total'] ?? 0) === 0) {
                Cache::put($this->cacheKey, [
                    'status' => 'failed',
                    'message' => $result['errors'][0] ?? 'Import failed. Please check your file and try again.',
                ], now()->addMinutes(30));

                return;
            }

            Cache::put($this->cacheKey, [
                'status' => 'done',
                'created' => $result['created'],
                'updated' => $result['updated'],
                'errors' => $result['errors'],
                'total' => $result['total'] ?? 0,
            ], now()->addMinutes(30));
        } catch (\Throwable $e) {
            Log::error('[MedicineImport] Job handle failed', [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
                'storage_path' => $this->storagePath,
                'error' => $e->getMessage(),
            ]);

            Cache::put($this->cacheKey, [
                'status' => 'failed',
                'message' => 'Import failed. Please check your file and try again.',
            ], now()->addMinutes(30));

            throw $e;
        } finally {
            Storage::delete($this->storagePath);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[MedicineImport] Job failed', [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'storage_path' => $this->storagePath,
            'error' => $e->getMessage(),
        ]);

        Cache::put($this->cacheKey, [
            'status' => 'failed',
            'message' => 'Import failed. Please check your file and try again.',
        ], now()->addMinutes(30));

        Storage::delete($this->storagePath);
    }

    private function activateTenant(): void
    {
        if ($this->tenantId <= 0) {
            return;
        }

        $current = Tenant::current();

        if ($current !== null && (int) $current->getKey() === $this->tenantId) {
            return;
        }

        try {
            Tenant::find($this->tenantId)?->makeCurrent();
        } catch (\Throwable $e) {
            Log::warning('[MedicineImport] Tenant activation skipped', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
