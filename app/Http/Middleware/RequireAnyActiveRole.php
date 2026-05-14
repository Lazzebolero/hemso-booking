<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAnyActiveRole
{
    /**
     * Laravel skickar varje värde efter kolon som separat argument, t.ex.
     * active.roles:guide,host → handle(..., 'guide', 'host').
     * Stödjer även ett enda kommaseparerat argument om det förekommer.
     */
    public function handle(Request $request, Closure $next, string ...$roleArguments): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $allowed = [];
        foreach ($roleArguments as $arg) {
            foreach (array_filter(array_map('trim', explode(',', $arg))) as $slug) {
                if ($slug !== '') {
                    $allowed[$slug] = true;
                }
            }
        }
        $allowed = array_keys($allowed);

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
