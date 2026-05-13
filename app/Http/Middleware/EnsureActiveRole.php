<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $activeRole = session('active_role');

        if (! $activeRole || ! $user->canActivateRole($activeRole)) {
            session()->forget('active_role');

            return redirect()->route('role.select');
        }

        return $next($request);
    }
}