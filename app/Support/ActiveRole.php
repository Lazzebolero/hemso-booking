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