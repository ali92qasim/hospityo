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
