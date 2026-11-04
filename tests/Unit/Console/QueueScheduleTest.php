<?php

use Illuminate\Console\Scheduling\Schedule;

uses(Tests\TestCase::class);

it('schedules a short-lived queue drain every minute', function () {
    // Ensure routes/console.php schedule definitions are registered.
    $this->artisan('schedule:list')->assertSuccessful();

    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event) => $event->command ?? '')
        ->implode("\n");

    expect($commands)
        ->toContain('queue:work')
        ->toContain('stop-when-empty')
        ->toContain('--max-time=50');
});
