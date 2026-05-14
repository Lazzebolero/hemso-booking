<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class ActiveRoleRedirect
{
    /**
     * Namngiven rutt efter aktiv roll. Använder Route::has så att gammal route-cache
     * eller ofullständig deploy inte ger 500 (RouteNotFoundException) vid redirect från t.ex. /select-role.
     */
    public static function routeNameFor(string $role, ?User $_user = null): string
    {
        $preferred = match ($role) {
            Roles::ADMIN => 'admin.dashboard',
            Roles::HOST => 'host.entry',
            Roles::GUIDE => 'guide.dashboard',
            Roles::RESTAURANT => 'staff.dashboard',
            Roles::RESTAURANT_STATISTIK => 'restaurant-statistik.dashboard',
            default => 'role.select',
        };

        if (Route::has($preferred)) {
            return $preferred;
        }

        return self::fallbackRouteNameFor($role);
    }

    private static function fallbackRouteNameFor(string $role): string
    {
        $candidates = match ($role) {
            Roles::HOST => ['host.dashboard', 'role.select'],
            Roles::ADMIN => ['admin.dashboard', 'role.select'],
            Roles::GUIDE => ['guide.dashboard', 'role.select'],
            Roles::RESTAURANT => ['staff.dashboard', 'role.select'],
            Roles::RESTAURANT_STATISTIK => ['restaurant-statistik.dashboard', 'role.select'],
            default => ['role.select'],
        };

        foreach ($candidates as $name) {
            if (Route::has($name)) {
                return $name;
            }
        }

        return 'role.select';
    }
}
