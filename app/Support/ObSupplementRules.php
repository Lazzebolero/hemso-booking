<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Ordinarie OB-fönster (obekväm arbetstid).
 * Beloppet lagras som economy_ob_hourly_amount (SEK/timme).
 */
class ObSupplementRules
{
    /**
     * @return list<array{label: string, description: string}>
     */
    public static function windows(): array
    {
        return [
            [
                'label' => 'Måndag–fredag',
                'description' => 'från kl. 20.00 till kl. 06.00 påföljande dag',
            ],
            [
                'label' => 'Lördag, midsommar-, jul- och nyårsafton',
                'description' => 'från kl. 16.00 till kl. 06.00 påföljande dag',
            ],
            [
                'label' => 'Söndag och helgdag',
                'description' => 'från kl. 06.00 till kl. 06.00 påföljande dag',
            ],
        ];
    }

    /**
     * Antal OB-timmar inom ett pass på ett kalenderdatum (samma-dags start/slut).
     */
    public static function obHoursForShift(Carbon $shiftDate, string $startTime, string $endTime): float
    {
        $day = $shiftDate->copy()->startOfDay();
        $start = self::combine($day, $startTime);
        $end = self::combine($day, $endTime);

        if ($start === null || $end === null || $end->lte($start)) {
            return 0.0;
        }

        $obMinutes = 0;
        $cursor = $start->copy();

        while ($cursor->lt($end)) {
            $stepEnd = $cursor->copy()->addMinutes(15);
            if ($stepEnd->gt($end)) {
                $stepEnd = $end->copy();
            }

            $mid = $cursor->copy()->addSeconds((int) ($cursor->diffInSeconds($stepEnd) / 2));
            if (self::isObInstant($mid)) {
                $obMinutes += (int) $cursor->diffInMinutes($stepEnd);
            }

            $cursor = $stepEnd;
        }

        return round($obMinutes / 60, 2);
    }

    public static function isObInstant(Carbon $instant): bool
    {
        $day = $instant->copy()->startOfDay();
        $previous = $day->copy()->subDay();

        foreach ([$previous, $day] as $windowDay) {
            $window = self::windowStartingOn($windowDay);
            if ($instant->gte($window['start']) && $instant->lt($window['end'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function windowStartingOn(Carbon $day): array
    {
        $day = $day->copy()->startOfDay();
        $startHour = self::obStartHourForDay($day);

        return [
            'start' => $day->copy()->setTime($startHour, 0, 0),
            'end' => $day->copy()->addDay()->setTime(6, 0, 0),
        ];
    }

    public static function obStartHourForDay(Carbon $day): int
    {
        if (self::isSpecialEve($day)) {
            return 16;
        }

        if ($day->isSaturday()) {
            return 16;
        }

        if ($day->isSunday() || self::isPublicHoliday($day)) {
            return 6;
        }

        return 20;
    }

    public static function isSpecialEve(Carbon $day): bool
    {
        $month = (int) $day->month;
        $date = (int) $day->day;

        if ($month === 12 && ($date === 24 || $date === 31)) {
            return true;
        }

        return self::isMidsummerEve($day);
    }

    public static function isPublicHoliday(Carbon $day): bool
    {
        $month = (int) $day->month;
        $date = (int) $day->day;
        $year = (int) $day->year;

        $fixed = [
            '01-01', // Nyårsdagen
            '01-06', // Trettondedag jul
            '05-01', // Första maj
            '06-06', // Nationaldagen
            '12-25', // Juldagen
            '12-26', // Annandag jul
        ];

        if (in_array(sprintf('%02d-%02d', $month, $date), $fixed, true)) {
            return true;
        }

        $easter = self::easterSunday($year);

        $movable = [
            $easter->copy()->subDays(2)->toDateString(), // Långfredagen
            $easter->toDateString(), // Påskdagen
            $easter->copy()->addDay()->toDateString(), // Annandag påsk
            $easter->copy()->addDays(39)->toDateString(), // Kristi himmelsfärdsdag
            $easter->copy()->addDays(49)->toDateString(), // Pingstdagen
            self::midsummerDay($year)->toDateString(),
            self::allSaintsDay($year)->toDateString(),
        ];

        return in_array($day->toDateString(), $movable, true);
    }

    public static function isMidsummerEve(Carbon $day): bool
    {
        return $day->isSameDay(self::midsummerEve((int) $day->year));
    }

    public static function midsummerEve(int $year): Carbon
    {
        // Fredagen mellan 19–25 juni.
        $cursor = Carbon::create($year, 6, 19)->startOfDay();
        while (! $cursor->isFriday()) {
            $cursor->addDay();
        }

        return $cursor;
    }

    public static function midsummerDay(int $year): Carbon
    {
        return self::midsummerEve($year)->copy()->addDay();
    }

    public static function allSaintsDay(int $year): Carbon
    {
        // Lördagen mellan 31 okt–6 nov.
        $cursor = Carbon::create($year, 10, 31)->startOfDay();
        while (! $cursor->isSaturday()) {
            $cursor->addDay();
        }

        return $cursor;
    }

    public static function easterSunday(int $year): Carbon
    {
        // Anonymous Gregorian algorithm.
        $a = $year % 19;
        $b = intdiv($year, 100);
        $c = $year % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $month = intdiv($h + $l - 7 * $m + 114, 31);
        $day = (($h + $l - 7 * $m + 114) % 31) + 1;

        return Carbon::create($year, $month, $day)->startOfDay();
    }

    private static function combine(Carbon $day, string $time): ?Carbon
    {
        $normalized = trim($time);
        if (preg_match('/^\d{2}:\d{2}$/', $normalized) === 1) {
            $normalized .= ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $normalized) !== 1) {
            return null;
        }

        try {
            return Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $day->toDateString().' '.$normalized
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
