<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Route model binding for backward compatibility
        \Route::bind('labOrder', function ($value) {
            return \App\Models\InvestigationOrder::findOrFail($value);
        });
        
        \Route::bind('lab_test', function ($value) {
            return \App\Models\Investigation::findOrFail($value);
        });
        
        // Set application timezone from settings
        $timezone = cache('settings.timezone', config('app.timezone', 'Asia/Karachi'));
        config(['app.timezone' => $timezone]);
        date_default_timezone_set($timezone);
        
        // Register Blade directives for settings
        \Blade::directive('currency', function ($expression) {
            return "<?php echo format_currency($expression); ?>";
        });
        
        \Blade::directive('date', function ($expression) {
            return "<?php echo format_date($expression); ?>";
        });
        
        \Blade::directive('time', function ($expression) {
            return "<?php echo format_time($expression); ?>";
        });
        
        \Blade::directive('datetime', function ($expression) {
            return "<?php echo format_datetime($expression); ?>";
        });
    }
}
