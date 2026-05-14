<?php

namespace App\Services;

use App\Models\LockedPayrollPeriod;
use Carbon\CarbonInterface;

class PayrollLockService
{
    public static function isLocked(string $date): bool
    {
        return LockedPayrollPeriod::query()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    public static function isLockedDate(CarbonInterface|string $date): bool
    {
        $d = $date instanceof CarbonInterface
            ? $date->format('Y-m-d')
            : (string) $date;

        return self::isLocked($d);
    }

    /**
     * True if any calendar day in [start, end] (inclusive) overlaps a locked payroll interval.
     */
    public static function rangeOverlapsLock(CarbonInterface|string $rangeStart, CarbonInterface|string $rangeEnd): bool
    {
        $start = $rangeStart instanceof CarbonInterface
            ? $rangeStart->format('Y-m-d')
            : (string) $rangeStart;

        $end = $rangeEnd instanceof CarbonInterface
            ? $rangeEnd->format('Y-m-d')
            : (string) $rangeEnd;

        return LockedPayrollPeriod::query()
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->exists();
    }

    public static function assertWorkDateUnlockedForUser(string $workDate): void
    {
        if (self::isLocked($workDate)) {
            abort(403, 'Denna dag ingår i en låst löneperiod. Kontakta administratör om du behöver ändringar.');
        }
    }
}
