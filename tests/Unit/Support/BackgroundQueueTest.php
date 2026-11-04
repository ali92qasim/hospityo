<?php

use App\Support\BackgroundQueue;
use Illuminate\Support\Facades\Process;

uses(Tests\TestCase::class);

it('starts a one-shot queue worker for processNextJob', function () {
    config(['queue.default' => 'database']);
    Process::fake();

    BackgroundQueue::processNextJob(120);

    Process::assertRan(function ($process) {
        $command = is_array($process->command) ? implode(' ', $process->command) : $process->command;

        return str_contains($command, 'queue:work')
            && str_contains($command, '--once')
            && ! str_contains($command, '--stop-when-empty');
    });
});

it('starts a stop-when-empty queue worker for drain', function () {
    config(['queue.default' => 'database']);
    Process::fake();

    BackgroundQueue::drain(maxTime: 50);

    Process::assertRan(function ($process) {
        $command = is_array($process->command) ? implode(' ', $process->command) : $process->command;

        return str_contains($command, 'queue:work')
            && str_contains($command, '--stop-when-empty')
            && str_contains($command, '--max-time=50');
    });
});

it('does not start a worker when the queue runs synchronously', function () {
    config(['queue.default' => 'sync']);
    Process::fake();

    BackgroundQueue::drain();
    BackgroundQueue::processNextJob();

    Process::assertNothingRan();
});
