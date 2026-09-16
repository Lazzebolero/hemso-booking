<?php

namespace App\Support;

class GuideShell
{
    public const SESSION_KEY = 'in_guide_shell';

    public static function markActive(): void
    {
        session([self::SESSION_KEY => true]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function isActive(): bool
    {
        return (bool) session(self::SESSION_KEY, false);
    }

    public static function layoutView(): string
    {
        $activeRole = session('active_role');

        if ($activeRole === Roles::GUIDE) {
            return 'layouts.guide';
        }

        if (self::isActive() && auth()->user()?->hasRole(Roles::GUIDE)) {
            return 'layouts.guide';
        }

        return 'layouts.app';
    }

    public static function homeRouteName(): string
    {
        if (session('active_role') === Roles::GUIDE
            || (self::isActive() && auth()->user()?->hasRole(Roles::GUIDE))) {
            return 'guide.dashboard';
        }

        return ActiveRoleRedirect::routeNameFor(
            (string) session('active_role'),
            auth()->user()
        );
    }
}
