<?php

use Illuminate\Console\Scheduling\Schedule;

uses(Tests\TestCase::class);

it('schedules a short-lived queue drain every minute', function () {
    // Ensure routes/console.php schedule definitions are registered.
    $this->artisan('schedule:list')->assertSuccessful();

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) ($event->command ?? ''), 'queue:work'));

    expect($event)->not->toBeNull()
        ->and($event->command)->toContain('stop-when-empty')
        ->and($event->command)->toContain('--max-time=50')
        ->and($event->withoutOverlapping)->toBeTrue()
        // Must expire quickly: default 1440m leaves a stuck mutex for a full day
        // if shared hosting kills the worker mid-run.
        ->and($event->expiresAt)->toBe(1);
});
