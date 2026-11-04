<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Send near-expiry medicine alerts to admins and pharmacists every morning at 8:00 AM.
// Checks all active tenants for stock_in batches expiring within 6 months.
// To test manually: php artisan pharmacy:alert-near-expiry --tenant=your-slug
Schedule::command('pharmacy:alert-near-expiry')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[Schedule] pharmacy:alert-near-expiry failed.');
    });

// Shared hosting fallback: short-lived worker. Exits when empty or after ~50s.
// schedule:run itself is one-shot — cPanel cron must invoke it EVERY minute.
// No withoutOverlapping: a stuck mutex was skipping all later drains on this host.
// Database queue already locks reserved jobs, so a brief overlap is safe.
Schedule::command('queue:work database --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('[Schedule] queue:work drain failed.');
    });
