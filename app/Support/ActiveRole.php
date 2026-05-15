<?php

namespace App\Support;

class ActiveRole
{
    public static function slug(): ?string
    {
        return session('active_role');
    }

    public static function routePrefix(): string
    {
        return match (self::slug()) {
            Roles::ADMIN => 'admin',
            Roles::HOST => 'host',
            Roles::GUIDE => 'guide',
            Roles::RESTAURANT => 'restaurant',
            default => 'admin',
        };
    }

    public static function routeName(string $suffix): string
    {
        if (self::slug() === Roles::RESTAURANT) {
            return match ($suffix) {
                'restaurant-board' => 'restaurant.dashboard',
                'restaurant-board.kiosk' => 'restaurant.kiosk',
                default => 'restaurant.'.$suffix,
            };
        }

        return self::routePrefix().'.'.$suffix;
    }

    public static function visitorDogsRoutePrefix(): string
    {
        return self::slug() === Roles::HOST ? 'host' : 'admin';
    }

    public static function isAdmin(): bool
    {
        return self::slug() === Roles::ADMIN;
    }

    public static function isHost(): bool
    {
        return self::slug() === Roles::HOST;
    }

    public static function isGuide(): bool
    {
        return self::slug() === Roles::GUIDE;
    }

    public static function isRestaurant(): bool
    {
        return self::slug() === Roles::RESTAURANT;
    }
}
