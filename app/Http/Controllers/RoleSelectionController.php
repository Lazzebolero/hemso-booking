<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActiveRoleRedirect;
use App\Support\GuideShell;
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

            if ($role === Roles::GUIDE) {
                GuideShell::markActive();
            } else {
                GuideShell::clear();
            }

            return self::redirectAfterRole($role, $user);
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

        if ($role === Roles::GUIDE) {
            GuideShell::markActive();
        } else {
            GuideShell::clear();
        }

        return self::redirectAfterRole($role, $user);
    }

    private static function redirectAfterRole(string $role, User $user): RedirectResponse
    {
        $location = ActiveRoleRedirect::location($role, $user);

        if (Roles::isProductionLoginRole($role) && self::isRoleSelectLocation($location)) {
            return redirect('/berget');
        }

        return redirect()->to($location);
    }

    private static function isRoleSelectLocation(string $location): bool
    {
        $path = parse_url($location, PHP_URL_PATH) ?: $location;

        return $path === '/select-role' || str_ends_with($path, '/select-role');
    }
}
