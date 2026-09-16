<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Http\Request;

class StatisticsPeriod
{
    /**
     * @return array{0: string, 1: Carbon, 2: Carbon, 3: Carbon, 4: int, 5: int}
     */
    public static function resolve(Request $request): array
    {
        $period = $request->string('period')->toString();
        if (! in_array($period, ['day', 'week', 'month', 'year'], true)) {
            $period = 'week';
        }

        $now = now();
        $year = max(2000, min(2100, (int) $request->get('year', $now->year)));

        if ($period === 'month') {
            $month = max(1, min(12, (int) $request->get('month', $now->month)));
            $date = Carbon::create($year, $month, 1);

            return [
                $period,
                $date,
                (clone $date)->startOfMonth(),
                (clone $date)->endOfMonth(),
                $year,
                $month,
            ];
        }

        if ($period === 'year') {
            $date = Carbon::create($year, 1, 1);

            return [
                $period,
                $date,
                (clone $date)->startOfYear(),
                (clone $date)->endOfYear(),
                $year,
                1,
            ];
        }

        $date = Carbon::parse($request->get('date', $now->toDateString()));

        [$from, $to] = match ($period) {
            'day' => [(clone $date)->startOfDay(), (clone $date)->endOfDay()],
            default => [(clone $date)->startOfWeek(), (clone $date)->endOfWeek()],
        };

        return [$period, $date, $from, $to, $date->year, $date->month];
    }
}
