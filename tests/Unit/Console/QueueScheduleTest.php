<?php

use Illuminate\Console\Scheduling\Schedule;

uses(Tests\TestCase::class);

it('schedules a short-lived queue drain every minute without an overlap mutex', function () {
    // Ensure routes/console.php schedule definitions are registered.
    $this->artisan('schedule:list')->assertSuccessful();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) ($event->command ?? ''), 'queue:work'));

    expect($event)->not->toBeNull()
        ->and($event->command)->toContain('stop-when-empty')
        ->and($event->command)->toContain('--max-time=50')
        // Overlap mutex skipped later runs on shared hosting when the worker was killed.
        ->and($event->withoutOverlapping)->toBeFalse();
});
