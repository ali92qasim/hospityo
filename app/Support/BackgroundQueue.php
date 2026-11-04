<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

/**
 * Starts a short-lived queue worker in a separate OS process.
 *
 * Used after dispatching jobs so environments without Supervisor
 * (Herd, Windows, shared hosting) can process the queue without a
 * persistent `queue:listen` / `queue:work` terminal.
 */
class BackgroundQueue
{
    public static function processNextJob(int $timeout = 1800): void
    {
        static::startWorker([
            '--once',
            '--timeout='.$timeout,
            '--tries=1',
        ]);
    }

    /**
     * Drain pending jobs then exit. Safe for shared hosting / cron.
     *
     * On HTTP requests, runs after the response is sent (no proc_open needed).
     * On console / tests, spawns a background process.
     */
    public static function drain(int $maxTime = 50, int $timeout = 1800): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        if (! app()->runningInConsole()) {
            dispatch(function () use ($maxTime, $timeout) {
                try {
                    Artisan::call('queue:work', [
                        'connection' => 'database',
                        '--stop-when-empty' => true,
                        '--max-time' => $maxTime,
                        '--timeout' => $timeout,
                        '--tries' => 3,
                    ]);
                } catch (Throwable $e) {
                    Log::error('[BackgroundQueue] after-response drain failed', [
                        'error' => $e->getMessage(),
                    ]);
                }
            })->afterResponse();

            return;
        }

        static::startWorker([
            '--stop-when-empty',
            '--max-time='.$maxTime,
            '--timeout='.$timeout,
            '--tries=3',
        ]);
    }

    /**
     * @param  list<string>  $options
     */
    protected static function startWorker(array $options): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        $php = (new PhpExecutableFinder())->find(false) ?: 'php';

        try {
            Process::path(base_path())
                ->timeout(0)
                ->start(array_merge([
                    $php,
                    base_path('artisan'),
                    'queue:work',
                    'database',
                ], $options));
        } catch (Throwable $e) {
            Log::warning('[BackgroundQueue] Failed to start queue worker process', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
