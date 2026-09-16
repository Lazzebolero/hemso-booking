<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\WorkShift;
use App\Support\ObSupplementRules;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EconomyRestaurantCostService
{
    public function __construct(
        private EconomySettingsService $economySettings,
    ) {}

    /**
     * @return array{
     *     days: list<array{
     *         date: string,
     *         label: string,
     *         meal_people: int,
     *         meal_bookings: int,
     *         staff_count: int,
     *         staff_hours: float,
     *         ob_hours: float,
     *         cost: float
     *     }>,
     *     totals: array{
     *         meal_people: int,
     *         meal_bookings: int,
     *         staff_count: int,
     *         staff_hours: float,
     *         ob_hours: float,
     *         cost: float
     *     }
     * }
     */
    public function forPeriod(Carbon $from, Carbon $to): array
    {
        $fromDate = $from->copy()->startOfDay();
        $toDate = $to->copy()->startOfDay();

        $mealByDate = $this->mealGuestsByDate($fromDate, $toDate);
        $staffByDate = $this->restaurantStaffByDate($fromDate, $toDate);
        $hourly = $this->economySettings->restaurantHourlyCost();
        $obHourly = $this->economySettings->obHourlyAmount();

        $days = [];
        $cursor = $fromDate->copy();

        while ($cursor->lte($toDate)) {
            $dateKey = $cursor->toDateString();
            $meal = $mealByDate->get($dateKey, $this->emptyMeal());
            $staff = $staffByDate->get($dateKey, $this->emptyStaff());
            $cost = round(
                ($staff['staff_hours'] * $hourly) + ($staff['ob_hours'] * $obHourly),
                2
            );

            $days[] = [
                'date' => $dateKey,
                'label' => $cursor->copy()->locale('sv')->isoFormat('ddd D MMM'),
                'meal_people' => $meal['meal_people'],
                'meal_bookings' => $meal['meal_bookings'],
                'staff_count' => $staff['staff_count'],
                'staff_hours' => $staff['staff_hours'],
                'ob_hours' => $staff['ob_hours'],
                'cost' => $cost,
            ];

            $cursor->addDay();
        }

        return [
            'days' => $days,
            'totals' => $this->sumDays($days),
        ];
    }

    /**
     * @return Collection<string, array{meal_people: int, meal_bookings: int}>
     */
    private function mealGuestsByDate(Carbon $from, Carbon $to): Collection
    {
        $bookings = Booking::query()
            ->where('is_waitlist', false)
            ->where('includes_meal', true)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', function ($query) use ($from, $to) {
                $query->whereDate('tour_date', '>=', $from->toDateString())
                    ->whereDate('tour_date', '<=', $to->toDateString())
                    ->whereNotIn('status', ['cancelled']);
            })
            ->with(['tour:id,tour_date'])
            ->get();

        return $bookings
            ->groupBy(fn (Booking $booking) => Carbon::parse($booking->tour->tour_date)->toDateString())
            ->map(function (Collection $dayBookings) {
                return [
                    'meal_people' => (int) $dayBookings->sum(fn (Booking $booking) => (int) ($booking->total_count ?? 0)),
                    'meal_bookings' => $dayBookings->count(),
                ];
            });
    }

    /**
     * @return Collection<string, array{staff_count: int, staff_hours: float, ob_hours: float}>
     */
    private function restaurantStaffByDate(Carbon $from, Carbon $to): Collection
    {
        $shifts = WorkShift::query()
            ->where('shift_role', Roles::RESTAURANT)
            ->active()
            ->whereDate('shift_date', '>=', $from->toDateString())
            ->whereDate('shift_date', '<=', $to->toDateString())
            ->get(['shift_date', 'start_time', 'end_time', 'user_id']);

        return $shifts
            ->groupBy(fn (WorkShift $shift) => Carbon::parse($shift->shift_date)->toDateString())
            ->map(function (Collection $dayShifts) {
                $staffHours = round($dayShifts->sum(fn (WorkShift $shift) => $this->shiftHours($shift)), 2);
                $obHours = round($dayShifts->sum(fn (WorkShift $shift) => $this->shiftObHours($shift)), 2);

                return [
                    'staff_count' => $dayShifts->pluck('user_id')->unique()->count(),
                    'staff_hours' => $staffHours,
                    'ob_hours' => $obHours,
                ];
            });
    }

    private function shiftHours(WorkShift $shift): float
    {
        $start = $this->normalizeTime($shift->start_time);
        $end = $this->normalizeTime($shift->end_time);

        if ($start === null || $end === null) {
            return 0.0;
        }

        try {
            $startAt = Carbon::createFromFormat('H:i:s', $start);
            $endAt = Carbon::createFromFormat('H:i:s', $end);
        } catch (\Throwable) {
            return 0.0;
        }

        $minutes = $startAt->diffInMinutes($endAt, false);

        return $minutes > 0 ? $minutes / 60 : 0.0;
    }

    private function shiftObHours(WorkShift $shift): float
    {
        $start = $this->normalizeTime($shift->start_time);
        $end = $this->normalizeTime($shift->end_time);

        if ($start === null || $end === null) {
            return 0.0;
        }

        return ObSupplementRules::obHoursForShift(
            Carbon::parse($shift->shift_date)->startOfDay(),
            $start,
            $end,
        );
    }

    private function normalizeTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = (string) $value;

        if (preg_match('/^\d{2}:\d{2}$/', $raw) === 1) {
            return $raw.':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $raw) === 1) {
            return $raw;
        }

        return null;
    }

    /**
     * @return array{meal_people: int, meal_bookings: int}
     */
    private function emptyMeal(): array
    {
        return [
            'meal_people' => 0,
            'meal_bookings' => 0,
        ];
    }

    /**
     * @return array{staff_count: int, staff_hours: float, ob_hours: float}
     */
    private function emptyStaff(): array
    {
        return [
            'staff_count' => 0,
            'staff_hours' => 0.0,
            'ob_hours' => 0.0,
        ];
    }

    /**
     * @param  list<array{meal_people: int, meal_bookings: int, staff_count: int, staff_hours: float, ob_hours: float, cost: float}>  $days
     * @return array{meal_people: int, meal_bookings: int, staff_count: int, staff_hours: float, ob_hours: float, cost: float}
     */
    private function sumDays(array $days): array
    {
        $totals = [
            'meal_people' => 0,
            'meal_bookings' => 0,
            'staff_count' => 0,
            'staff_hours' => 0.0,
            'ob_hours' => 0.0,
            'cost' => 0.0,
        ];

        foreach ($days as $day) {
            $totals['meal_people'] += $day['meal_people'];
            $totals['meal_bookings'] += $day['meal_bookings'];
            $totals['staff_count'] += $day['staff_count'];
            $totals['staff_hours'] += $day['staff_hours'];
            $totals['ob_hours'] += $day['ob_hours'];
            $totals['cost'] += $day['cost'];
        }

        $totals['staff_hours'] = round($totals['staff_hours'], 2);
        $totals['ob_hours'] = round($totals['ob_hours'], 2);
        $totals['cost'] = round($totals['cost'], 2);

        return $totals;
    }
}
