<?php

namespace App\Http\Middleware;

use App\Support\GuideShell;
use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContinueGuideShell
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (GuideShell::isActive() && $user?->hasRole(Roles::GUIDE)) {
            session(['active_role' => Roles::GUIDE]);
        }

        return $next($request);
    }
}
