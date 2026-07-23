<?php

namespace App\Jobs\Tenant;

use App\Services\MedicineCategoryImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportMedicineCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        private readonly string $storagePath,
        private readonly string $cacheKey,
        private readonly int $userId,
    ) {}

    public function handle(MedicineCategoryImportService $service): void
    {
        $fullPath = Storage::path($this->storagePath);

        try {
            $result = $service->importFromFile($fullPath);

            Cache::put($this->cacheKey, [
                'status' => 'done',
                'created' => $result['created'],
                'updated' => $result['updated'],
                'errors' => $result['errors'],
            ], now()->addMinutes(30));
        } finally {
            Storage::delete($this->storagePath);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[MedicineCategoryImport] Job failed', [
            'user_id' => $this->userId,
            'storage_path' => $this->storagePath,
            'error' => $e->getMessage(),
        ]);

        Cache::put($this->cacheKey, [
            'status' => 'failed',
            'message' => 'Import failed. Please check your file and try again.',
        ], now()->addMinutes(30));

        Storage::delete($this->storagePath);
    }
}
