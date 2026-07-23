<?php

namespace App\Jobs\Tenant;

use App\Models\Tenant;
use App\Services\OpeningStockImportService;
use App\Services\OpeningStockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Multitenancy\Jobs\NotTenantAware;

class ImportOpeningStockJob implements ShouldQueue, NotTenantAware
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

    public function handle(OpeningStockImportService $service): void
    {
        $this->activateTenant();

        if (OpeningStockService::isLocked()) {
            Cache::put($this->cacheKey, [
                'status' => 'failed',
                'message' => 'Opening stock has already been imported for this clinic.',
            ], now()->addMinutes(30));

            Storage::delete($this->storagePath);

            return;
        }

        Cache::put($this->cacheKey, [
            'status' => 'processing',
            'processed' => 0,
            'total' => 0,
            'created' => 0,
        ], now()->addMinutes(30));

        $fullPath = Storage::path($this->storagePath);

        try {
            $result = $service->importFromFile($fullPath, $this->userId, function (
                int $processed,
                int $total,
                int $created,
            ) {
                Cache::put($this->cacheKey, [
                    'status' => 'processing',
                    'processed' => $processed,
                    'total' => $total,
                    'created' => $created,
                ], now()->addMinutes(30));
            });

            if ($result['created'] === 0) {
                Cache::put($this->cacheKey, [
                    'status' => 'failed',
                    'message' => $result['errors'][0] ?? 'Import failed. Please check your file and try again.',
                    'errors' => $result['errors'],
                ], now()->addMinutes(30));

                return;
            }

            Cache::put($this->cacheKey, [
                'status' => 'done',
                'created' => $result['created'],
                'updated' => 0,
                'errors' => $result['errors'],
                'total' => $result['total'] ?? 0,
                'locked' => true,
            ], now()->addMinutes(30));
        } catch (\Throwable $e) {
            Log::error('[OpeningStockImport] Job handle failed', [
                'user_id' => $this->userId,
                'tenant_id' => $this->tenantId,
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
        Log::error('[OpeningStockImport] Job failed', [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
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
            Log::warning('[OpeningStockImport] Tenant activation skipped', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
