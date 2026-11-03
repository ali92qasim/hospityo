<?php

namespace App\Http\Middleware;

use App\Support\SessionIdleTimeout;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureSessionNotIdle
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            if ($this->shouldSkip($request)) {
                return $next($request);
            }

            $guard = $this->activeGuard();
            if ($guard === null) {
                return $next($request);
            }

            $session = $request->session();
            $now = now()->getTimestamp();
            $lastActivity = $session->get('last_activity_at');

            if (is_numeric($lastActivity)) {
                $idleSeconds = $now - (int) $lastActivity;
                $timeoutSeconds = SessionIdleTimeout::seconds();

                if ($idleSeconds >= $timeoutSeconds) {
                    return $this->expireSession($request, $guard);
                }
            }

            $session->put('last_activity_at', $now);
        } catch (Throwable $e) {
            Log::warning('Session idle timeout check failed; continuing request.', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);
        }

        return $next($request);
    }

    private function shouldSkip(Request $request): bool
    {
        return $request->routeIs(
            'logout',
            'login',
            'central.login',
            'central.login.submit',
            'password.*',
            'super-admin.login',
            'super-admin.logout',
        ) || $request->is('super-admin/login');
    }

    private function activeGuard(): ?string
    {
        if (Auth::guard('web')->check()) {
            return 'web';
        }

        if (Auth::guard('super_admin')->check()) {
            return 'super_admin';
        }

        return null;
    }

    private function expireSession(Request $request, string $guard): Response
    {
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $loginRoute = $guard === 'super_admin' ? 'super-admin.login' : 'login';

        return redirect()
            ->route($loginRoute)
            ->with('error', 'Your session expired due to inactivity. Please sign in again.');
    }
}
