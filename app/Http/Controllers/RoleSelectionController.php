<?php

namespace App\Http\Controllers;

use App\Support\ActiveRoleRedirect;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleSelectionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user()->loadMissing('roles');
        $availableRoles = $user->availableRoleSlugs();

        if (count($availableRoles) === 0) {
            abort(403, 'Användaren har inga tilldelade roller.');
        }

        if (count($availableRoles) === 1) {
            $role = $availableRoles[0];

            session()->put('active_role', $role);
            session()->save();

            if ($role === Roles::RESTAURANT_STATISTIK) {
                return redirect('/statistik/restaurang');
            }

            return redirect()->route(
                ActiveRoleRedirect::routeNameFor($role, $user)
            );
        }

        return view('auth.select-role', [
            'availableRoles' => $availableRoles,
            'labels' => Roles::labels(),
            'descriptions' => Roles::descriptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string'],
        ]);

        $user = $request->user()->loadMissing('roles');
        $role = $validated['role'];

        if (! $user->canActivateRole($role)) {
            abort(403, 'Du får inte aktivera denna roll.');
        }

        session()->put('active_role', $role);
        session()->save();

        if ($role === Roles::RESTAURANT_STATISTIK) {
            return redirect('/statistik/restaurang');
        }

        return redirect()->route(
            ActiveRoleRedirect::routeNameFor($role, $user)
        );
    }
}