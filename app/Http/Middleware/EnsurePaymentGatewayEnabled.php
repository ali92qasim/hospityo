<?php

namespace App\Http\Middleware;

use App\Models\PaymentGateway;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defense in depth for public gateway webhooks: a disabled (or unconfigured)
 * gateway's endpoint does not exist, regardless of what the request carries.
 */
class EnsurePaymentGatewayEnabled
{
    public function handle(Request $request, Closure $next, string $slug): Response
    {
        $enabled = PaymentGateway::query()
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->exists();

        if (! $enabled) {
            abort(404);
        }

        return $next($request);
    }
}
