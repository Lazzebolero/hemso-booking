<?php

namespace App\Services;

use Carbon\Carbon;

class PayrollPeriodService
{
    public static function current(?Carbon $date = null): array
    {
        $date = $date ? $date->copy() : now();

        if ((int) $date->day >= 21) {
            $start = $date->copy()->day(21)->startOfDay();
            $end = $date->copy()->addMonthNoOverflow()->day(20)->endOfDay();
        } else {
            $start = $date->copy()->subMonthNoOverflow()->day(21)->startOfDay();
            $end = $date->copy()->day(20)->endOfDay();
        }

        return self::makePeriod($start, $end);
    }

    public static function previous(?Carbon $date = null): array
    {
        $current = self::current($date);
        $previousDate = $current['start']->copy()->subDay();

        return self::current($previousDate);
    }

    public static function forMonth(int $year, int $month): array
    {
        $end = Carbon::create($year, $month, 20)->endOfDay();
        $start = $end->copy()->subMonthNoOverflow()->day(21)->startOfDay();

        return self::makePeriod($start, $end);
    }

    public static function custom(string $from, string $to): array
    {
        return self::makePeriod(
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay()
        );
    }

    public static function resolveFromRequest(array $input): array
    {
        $period = $input['period'] ?? 'current';

        return match ($period) {
            'previous' => self::previous(),
            'custom' => self::custom($input['from'] ?? now()->toDateString(), $input['to'] ?? now()->toDateString()),
            default => self::current(),
        };
    }

    private static function makePeriod(Carbon $start, Carbon $end): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'label' => $start->format('Y-m-d') . ' – ' . $end->format('Y-m-d'),
            'file_label' => $start->format('Y-m-d') . '_' . $end->format('Y-m-d'),
        ];
    }
}
