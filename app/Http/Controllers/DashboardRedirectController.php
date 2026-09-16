<?php

namespace App\Http\Controllers;

use App\Support\ActiveRoleRedirect;
use App\Support\GuideShell;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            return redirect('/login');
        }

        $user->loadMissing('roles');
        $activeRole = session('active_role');

        if (! is_string($activeRole) || $activeRole === '' || ! $user->canActivateRole($activeRole)) {
            $availableRoles = $user->availableRoleSlugs();

            if (count($availableRoles) === 1) {
                $activeRole = $availableRoles[0];
                session()->put('active_role', $activeRole);
                $this->syncGuideShell($activeRole);
            } else {
                session()->forget('active_role');

                return redirect('/select-role');
            }
        }

        return redirect()->to(ActiveRoleRedirect::location($activeRole, $user));
    }

    private function syncGuideShell(string $role): void
    {
        if ($role === Roles::GUIDE) {
            GuideShell::markActive();

            return;
        }

        GuideShell::clear();
    }
}
