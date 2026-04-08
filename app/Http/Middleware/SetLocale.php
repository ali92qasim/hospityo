<?php

namespace App\Http\Middleware;

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
        // Check if locale is set in session
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } 
        // Check if locale is set in request
        elseif ($request->has('locale')) {
            $locale = $request->get('locale');
            Session::put('locale', $locale);
        }
        // Check if user has a preferred locale (only when tenant is active)
        elseif (\App\Models\Tenant::checkCurrent() && $request->user() && $request->user()->locale) {
            $locale = $request->user()->locale;
        }
        // Use default locale
        else {
            $locale = config('app.locale');
        }

        // Validate locale
        $availableLocales = ['en', 'fr', 'es', 'de', 'ar'];
        if (!in_array($locale, $availableLocales)) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
