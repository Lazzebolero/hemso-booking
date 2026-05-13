<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireActiveRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $activeRole = session('active_role');

        if ($activeRole !== $role) {
            abort(403, 'Fel aktiv roll för denna sida.');
        }

        if (! $user->canActivateRole($role)) {
            abort(403, 'Du saknar tillgång till denna roll.');
        }

        return $next($request);
    }
}