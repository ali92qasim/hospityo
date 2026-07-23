<?php



namespace App\Jobs\Tenant;



use App\Services\UnitImportService;

use Illuminate\Bus\Queueable;

use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Foundation\Bus\Dispatchable;

use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Queue\SerializesModels;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Storage;



class ImportUnitsJob implements ShouldQueue

{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;



    public int $tries = 1;



    public int $timeout = 300;



    public function __construct(

        private readonly string $storagePath,

        private readonly string $cacheKey,

        private readonly int $userId,

    ) {}



    public function handle(UnitImportService $service): void

    {

        $fullPath = Storage::path($this->storagePath);



        try {

            $result = $service->importFromFile($fullPath);



            if ($result['created'] === 0 && $result['updated'] === 0 && $result['errors'] !== []) {

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

                'updated' => $result['updated'],

                'errors' => $result['errors'],

            ], now()->addMinutes(30));

        } finally {

            Storage::delete($this->storagePath);

        }

    }



    public function failed(\Throwable $e): void

    {

        Log::error('[UnitImport] Job failed', [

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


