<?php

namespace App\Services;

use App\Models\LockedPayrollPeriod;

class PayrollLockService
{
    public static function isLocked(string $date): bool
    {
        return LockedPayrollPeriod::query()
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }
}
