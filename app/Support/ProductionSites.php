<?php

namespace App\Support;

class ProductionSites
{
    public const STORRABERGET = 'storraberget';

    public const HAVSTOUDD = 'havstoudd';

    public const KLAFSON = 'klafson';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::STORRABERGET => 'Storråberget',
            self::HAVSTOUDD => 'Havstoudd',
            self::KLAFSON => 'Kläfsön',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::labels());
    }

    /**
     * @param  list<string>|null  $keys
     * @return list<string>
     */
    public static function labelsFor(?array $keys): array
    {
        $labels = self::labels();

        return collect($keys ?? [])
            ->filter(fn ($key) => is_string($key) && isset($labels[$key]))
            ->map(fn (string $key) => $labels[$key])
            ->values()
            ->all();
    }
}
