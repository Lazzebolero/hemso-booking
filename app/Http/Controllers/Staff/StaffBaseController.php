<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

abstract class StaffBaseController extends Controller
{
    protected function authorizeStaffAccess(): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $allowedRoles = ['guide', 'restaurant', 'host', 'admin'];

        $hasAccess = collect($allowedRoles)->contains(fn ($role) => $user->hasRole($role));

        abort_unless($hasAccess, 403);
    }
}