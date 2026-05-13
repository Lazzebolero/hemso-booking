<?php

namespace App\Support;

use App\Models\User;

class ActiveRoleRedirect
{
    public static function routeNameFor(string $role, ?User $user = null): string
    {
        return match ($role) {
            Roles::ADMIN => 'admin.dashboard',
            Roles::HOST => 'host.dashboard',
            Roles::GUIDE => 'guide.dashboard',
            Roles::RESTAURANT => 'staff.dashboard',

            // 🔥 NY ROLL
            Roles::RESTAURANT_STATISTIK => 'restaurant-statistik.dashboard',

            default => 'role.select',
        };
    }
}