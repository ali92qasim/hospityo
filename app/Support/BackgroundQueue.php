<?php

namespace App\Support;

use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\PhpExecutableFinder;

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
     */
    public static function drain(int $maxTime = 50, int $timeout = 1800): void
    {
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

        Process::path(base_path())
            ->timeout(0)
            ->start(array_merge([
                $php,
                base_path('artisan'),
                'queue:work',
                'database',
            ], $options));
    }
}
