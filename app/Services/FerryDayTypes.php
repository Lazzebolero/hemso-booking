<?php

namespace App\Support;

class FerryDayTypes
{
    public const WEEKDAY = 'weekday';

    public const WEEKEND = 'weekend';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::WEEKDAY => 'Vardag',
            self::WEEKEND => 'Helg',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
