<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;

trait RedirectsAfterWrite
{
    /**
     * AD-1: send the user to $route only if they may view it; otherwise to $fallbackRoute
     * (or back() when null). The flash message is kept either way.
     *
     * @param  list<string>  $viewPermissions  any-of permissions that open $route
     */
    protected function redirectAfterWrite(
        string $route,
        array $routeParams,
        array $viewPermissions,
        string $message,
        ?string $fallbackRoute = null,
        array $fallbackParams = [],
        string $flashKey = 'success',
    ): RedirectResponse {
        if (auth()->user()?->canany($viewPermissions)) {
            return redirect()->route($route, $routeParams)->with($flashKey, $message);
        }

        return $fallbackRoute === null
            ? back()->with($flashKey, $message)
            : redirect()->route($fallbackRoute, $fallbackParams)->with($flashKey, $message);
    }
}
