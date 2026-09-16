<?php

namespace App\Support;

use Carbon\Carbon;

class StatisticsQuarterHour
{
    public static function timeToMinutes(string $time): int
    {
        $normalized = strlen($time) === 5 ? $time.':00' : substr($time, 0, 8);
        $parts = explode(':', $normalized);

        return ((int) $parts[0]) * 60 + (int) $parts[1];
    }

    public static function slotStartMinutes(int $minutes): int
    {
        return intdiv($minutes, 15) * 15;
    }

    public static function slotStartFromTime(string $time): int
    {
        return self::slotStartMinutes(self::timeToMinutes($time));
    }

    public static function formatLabel(int $startMinutes): string
    {
        $start = Carbon::createFromTime(intdiv($startMinutes, 60), $startMinutes % 60);
        $end = (clone $start)->addMinutes(15);

        return $start->format('H:i').'-'.$end->format('H:i');
    }
}
