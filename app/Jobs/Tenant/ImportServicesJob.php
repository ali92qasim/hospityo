<?php

namespace App\Jobs\Tenant;

use App\Services\ServiceImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportServicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Do not retry — a failed import should be re-uploaded by the user.
     * Upsert logic makes retries safe, but surfacing the failure is cleaner.
     */
    public int $tries   = 1;
    public int $timeout = 300; // 5 minutes max

    public function __construct(
        private readonly string $storagePath,
        private readonly string $cacheKey,
        private readonly int    $userId,
    ) {}

    public function handle(ServiceImportService $service): void
    {
        $fullPath = Storage::path($this->storagePath);

        try {
            $result = $service->importFromFile($fullPath);

            Cache::put($this->cacheKey, [
                'status'  => 'done',
                'created' => $result['created'],
                'updated' => $result['updated'],
                'errors'  => $result['errors'],
            ], now()->addMinutes(30));

        } finally {
            // Always clean up the uploaded file, even if import failed
            Storage::delete($this->storagePath);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[ServiceImport] Job failed', [
            'user_id'      => $this->userId,
            'storage_path' => $this->storagePath,
            'error'        => $e->getMessage(),
        ]);

        Cache::put($this->cacheKey, [
            'status'  => 'failed',
            'message' => 'Import failed. Please check your file and try again.',
        ], now()->addMinutes(30));

        // Clean up even on failure
        Storage::delete($this->storagePath);
    }
}
