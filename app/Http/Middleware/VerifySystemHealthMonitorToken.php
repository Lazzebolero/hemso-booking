<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySystemHealthMonitorToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = (string) config('services.system_health.monitor_token');

        if ($configuredToken === '') {
            abort(404);
        }

        $providedToken = (string) ($request->bearerToken() ?? $request->query('token', ''));

        if ($providedToken === '' || ! hash_equals($configuredToken, $providedToken)) {
            abort(403, 'Invalid monitor token.');
        }

        return $next($request);
    }
}
