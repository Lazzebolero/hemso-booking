<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAnyActiveRole
{
    /**
     * @param  string  $roles  Kommaseparerade slugs, t.ex. "guide,host"
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = array_values(array_filter(array_map('trim', explode(',', $roles))));

        $activeRole = session('active_role');

        if (! is_string($activeRole) || ! in_array($activeRole, $allowed, true)) {
            abort(403, 'Fel aktiv roll för denna sida.');
        }

        if (! $user->canActivateRole($activeRole)) {
            abort(403, 'Du saknar tillgång till denna roll.');
        }

        return $next($request);
    }
}
