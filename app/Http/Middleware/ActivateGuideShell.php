<?php

namespace App\Http\Middleware;

use App\Support\GuideShell;
use App\Support\Roles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivateGuideShell
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole(Roles::GUIDE)) {
            session([
                'active_role' => Roles::GUIDE,
            ]);
            GuideShell::markActive();
        }

        return $next($request);
    }
}
