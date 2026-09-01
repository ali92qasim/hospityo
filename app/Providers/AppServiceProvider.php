<?php

namespace App\Providers;

use App\Support\SettingsAccess;
use App\Support\SettingsSectionRegistry;
use App\Models\Tenant;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $importTemplateHelper = app_path('Helpers/ImportTemplateHelper.php');

        if (is_file($importTemplateHelper)) {
            require_once $importTemplateHelper;
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('settings.shell', function ($view) {
            $user = auth()->user();
            $tenant = Tenant::current();
            $tabs = [];
            if ($user) {
                foreach (SettingsSectionRegistry::children() as $section) {
                    if ($tenant && (! $tenant->hasModule('settings') || ! $tenant->hasModule($section['key']))) {
                        continue;
                    }
                    if (SettingsAccess::canAccessSection($user, $section['key'], 'GET')) {
                        $tabs[] = $section;
                    }
                }
            }
            $view->with('settingsTabs', $tabs);
        });

        // Disconnect DB connections after each queue job so persistent
        // connections are released back to the pool and don't accumulate.
        Queue::after(function (JobProcessed $event) {
            DB::disconnect('landlord');
            DB::disconnect('tenant');
        });

        Queue::failing(function (JobFailed $event) {
            DB::disconnect('landlord');
            DB::disconnect('tenant');
        });

        // Route model binding for backward compatibility
        \Route::bind('labOrder', function ($value) {
            return \App\Models\LabOrder::findOrFail($value);
        });
        
        \Route::bind('lab_test', function ($value) {
            return \App\Models\Investigation::findOrFail($value);
        });
        
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
