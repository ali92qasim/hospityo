<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } elseif ($request->has('locale')) {
            $locale = $request->get('locale');
            Session::put('locale', $locale);
        } else {
            $locale = $this->preferredLocaleFromActiveTenantUser($request)
                ?? config('app.locale');
        }

        $availableLocales = ['en', 'fr', 'es', 'de', 'ar'];
        if (! in_array($locale, $availableLocales, true)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Resolve a signed-in user's locale only after the tenant database is ready.
     * Loading $request->user() while status is "provisioning" queries a
     * database that does not exist yet and throws.
     */
    protected function preferredLocaleFromActiveTenantUser(Request $request): ?string
    {
        $tenant = Tenant::current();

        if (! $tenant || ! $tenant->isActive()) {
            return null;
        }

        $locale = $request->user()?->locale;

        return is_string($locale) && $locale !== '' ? $locale : null;
    }
}
