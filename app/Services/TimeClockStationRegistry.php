<?php

namespace App\Services;

use App\Support\Roles;

class TimeClockStationRegistry
{
    /**
     * @return list<string>
     */
    public static function clockRoles(): array
    {
        $roles = config('time_clock.clock_roles');

        if (is_array($roles) && $roles !== []) {
            return array_values($roles);
        }

        return [
            Roles::GUIDE,
            Roles::HOST,
            Roles::RESTAURANT,
            Roles::ADMIN,
        ];
    }

    public static function canRoleUseClock(string $activeRole): bool
    {
        return in_array($activeRole, self::clockRoles(), true);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('time_clock.stations', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        foreach (self::all() as $key => $station) {
            $stationToken = (string) ($station['token'] ?? '');

            if ($stationToken !== '' && hash_equals($stationToken, $token)) {
                return array_merge($station, ['key' => $key]);
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByKey(string $key): ?array
    {
        $station = self::all()[$key] ?? null;

        if (! is_array($station)) {
            return null;
        }

        return array_merge($station, ['key' => $key]);
    }

    public static function labelForKey(?string $key): ?string
    {
        if (! is_string($key) || $key === '') {
            return null;
        }

        return self::findByKey($key)['label'] ?? $key;
    }

    public static function scanUrl(string $key): ?string
    {
        $station = self::findByKey($key);
        $token = is_array($station) ? (string) ($station['token'] ?? '') : '';

        if ($token === '') {
            return null;
        }

        return route('time.scan', ['token' => $token]);
    }

    /**
     * @param  array<string, mixed>  $station
     */
    public static function userMayAccess(array $station, string $activeRole): bool
    {
        return self::canRoleUseClock($activeRole);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function stationsForRole(string $activeRole): array
    {
        if (! self::canRoleUseClock($activeRole)) {
            return [];
        }

        $stations = [];

        foreach (self::all() as $key => $station) {
            if (! is_array($station)) {
                continue;
            }

            $stations[] = array_merge($station, ['key' => $key]);
        }

        return $stations;
    }

    public static function requiresQrStamp(string $activeRole): bool
    {
        return self::stationsForRole($activeRole) !== [];
    }
}
