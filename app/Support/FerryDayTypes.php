<?php

namespace App\Support;

use Carbon\CarbonInterface;

class FerryDayTypes
{
    public const WEEKDAY = 'weekday';

    public const SATURDAY = 'saturday';

    public const SUNDAY = 'sunday';

    /** @deprecated Använd saturday/sunday */
    public const WEEKEND = 'weekend';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::WEEKDAY => 'Vardag',
            self::SATURDAY => 'Lördag',
            self::SUNDAY => 'Söndag',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }

    public static function forDate(CarbonInterface $date): string
    {
        return match ((int) $date->dayOfWeekIso) {
            6 => self::SATURDAY,
            7 => self::SUNDAY,
            default => self::WEEKDAY,
        };
    }

    public static function labelForDate(CarbonInterface $date): string
    {
        return self::labels()[self::forDate($date)] ?? 'Vardag';
    }

    public static function normalize(string $dayType): string
    {
        return match ($dayType) {
            self::SATURDAY, self::WEEKEND => self::SATURDAY,
            self::SUNDAY => self::SUNDAY,
            default => self::WEEKDAY,
        };
    }

    public static function isWeekendType(string $dayType): bool
    {
        return in_array(self::normalize($dayType), [self::SATURDAY, self::SUNDAY], true);
    }
}
