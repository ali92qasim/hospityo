<?php

namespace App\Support;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Starts a one-shot queue worker in a separate OS process.
 *
 * Used after dispatching import jobs so Herd / Windows environments
 * process the queue without a persistent `queue:listen` terminal.
 */
class BackgroundQueue
{
    public static function processNextJob(int $timeout = 1800): void
    {
        if (config('queue.default') === 'sync') {
            return;
        }

        $php = (new PhpExecutableFinder())->find(false) ?: 'php';

        $process = new Process([
            $php,
            base_path('artisan'),
            'queue:work',
            'database',
            '--once',
            '--timeout='.$timeout,
            '--tries=1',
        ], base_path());

        $process->setTimeout(null);
        $process->disableOutput();
        $process->start();
    }
}
