<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\WorkShift;
use App\Support\ObSupplementRules;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class EconomyProfitabilityService
{
    public function __construct(
        private EconomySettingsService $economySettings,
    ) {}

    /**
     * @return array{
     *     days: list<array{
     *         date: string,
     *         label: string,
     *         men_count: int,
     *         women_count: int,
     *         adult_count: int,
     *         unspecified_count: int,
     *         youth_count: int,
     *         child_count: int,
     *         people: int,
     *         revenue: float,
     *         guide_count: int,
     *         host_count: int,
     *         guide_hours: float,
     *         host_hours: float,
     *         staff_hours: float,
     *         ob_hours: float,
     *         cost: float,
     *         result: float
     *     }>,
     *     totals: array{
     *         men_count: int,
     *         women_count: int,
     *         adult_count: int,
     *         unspecified_count: int,
     *         youth_count: int,
     *         child_count: int,
     *         people: int,
     *         revenue: float,
     *         guide_count: int,
     *         host_count: int,
     *         guide_hours: float,
     *         host_hours: float,
     *         staff_hours: float,
     *         ob_hours: float,
     *         cost: float,
     *         result: float
     *     }
     * }
     */
    public function forPeriod(Carbon $from, Carbon $to): array
    {
        $fromDate = $from->copy()->startOfDay();
        $toDate = $to->copy()->startOfDay();

        $bookingsByDate = $this->activeBookingsByDate($fromDate, $toDate);
        $staffByDate = $this->staffByDate($fromDate, $toDate);
        $hourly = $this->economySettings->guideHourlyCost();
        $obHourly = $this->economySettings->obHourlyAmount();

        $days = [];
        $cursor = $fromDate->copy();

        while ($cursor->lte($toDate)) {
            $dateKey = $cursor->toDateString();
            $counts = $bookingsByDate->get($dateKey, $this->emptyCounts());
            $staff = $staffByDate->get($dateKey, $this->emptyStaff());
            $revenue = $this->economySettings->estimateRevenue(
                $counts['men_count'],
                $counts['women_count'],
                $counts['youth_count'],
                $counts['child_count'],
                $counts['unspecified_count'],
            );
            $cost = round(
                ($staff['staff_hours'] * $hourly) + ($staff['ob_hours'] * $obHourly),
                2
            );

            $days[] = [
                'date' => $dateKey,
                'label' => $cursor->copy()->locale('sv')->isoFormat('ddd D MMM'),
                'men_count' => $counts['men_count'],
                'women_count' => $counts['women_count'],
                'adult_count' => $counts['men_count'] + $counts['women_count'],
                'unspecified_count' => $counts['unspecified_count'],
                'youth_count' => $counts['youth_count'],
                'child_count' => $counts['child_count'],
                'people' => $counts['people'],
                'revenue' => $revenue,
                'guide_count' => $staff['guide_count'],
                'host_count' => $staff['host_count'],
                'guide_hours' => $staff['guide_hours'],
                'host_hours' => $staff['host_hours'],
                'staff_hours' => $staff['staff_hours'],
                'ob_hours' => $staff['ob_hours'],
                'cost' => $cost,
                'result' => round($revenue - $cost, 2),
            ];

            $cursor->addDay();
        }

        return [
            'days' => $days,
            'totals' => $this->sumDays($days),
        ];
    }

    /**
     * @return Collection<string, array{men_count: int, women_count: int, youth_count: int, child_count: int, unspecified_count: int, people: int}>
     */
    private function activeBookingsByDate(Carbon $from, Carbon $to): Collection
    {
        $bookings = Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', function ($query) use ($from, $to) {
                $query->whereDate('tour_date', '>=', $from->toDateString())
                    ->whereDate('tour_date', '<=', $to->toDateString());
            })
            ->with(['tour:id,tour_date'])
            ->get();

        return $bookings
            ->groupBy(fn (Booking $booking) => Carbon::parse($booking->tour->tour_date)->toDateString())
            ->map(function (Collection $dayBookings) {
                $men = (int) $dayBookings->sum(fn (Booking $booking) => (int) ($booking->men_count ?? 0));
                $women = (int) $dayBookings->sum(fn (Booking $booking) => (int) ($booking->women_count ?? 0));
                $youth = (int) $dayBookings->sum(fn (Booking $booking) => (int) ($booking->youth_count ?? 0));
                $child = (int) $dayBookings->sum(fn (Booking $booking) => (int) ($booking->child_count ?? 0));
                $unspecified = (int) $dayBookings->sum(fn (Booking $booking) => (int) ($booking->unspecified_count ?? 0));

                return [
                    'men_count' => $men,
                    'women_count' => $women,
                    'youth_count' => $youth,
                    'child_count' => $child,
                    'unspecified_count' => $unspecified,
                    'people' => $men + $women + $youth + $child + $unspecified,
                ];
            });
    }

    /**
     * Planerade guide- och värdpass: unika personer + timmar + OB-timmar.
     *
     * @return Collection<string, array{guide_count: int, host_count: int, guide_hours: float, host_hours: float, staff_hours: float, ob_hours: float}>
     */
    private function staffByDate(Carbon $from, Carbon $to): Collection
    {
        $shifts = WorkShift::query()
            ->whereIn('shift_role', [Roles::GUIDE, Roles::HOST])
            ->active()
            ->whereDate('shift_date', '>=', $from->toDateString())
            ->whereDate('shift_date', '<=', $to->toDateString())
            ->get(['shift_date', 'start_time', 'end_time', 'shift_role', 'user_id']);

        return $shifts
            ->groupBy(fn (WorkShift $shift) => Carbon::parse($shift->shift_date)->toDateString())
            ->map(function (Collection $dayShifts) {
                $guideShifts = $dayShifts->where('shift_role', Roles::GUIDE);
                $hostShifts = $dayShifts->where('shift_role', Roles::HOST);

                $guideHours = round($guideShifts->sum(fn (WorkShift $shift) => $this->shiftHours($shift)), 2);
                $hostHours = round($hostShifts->sum(fn (WorkShift $shift) => $this->shiftHours($shift)), 2);
                $obHours = round($dayShifts->sum(fn (WorkShift $shift) => $this->shiftObHours($shift)), 2);

                return [
                    'guide_count' => $guideShifts->pluck('user_id')->unique()->count(),
                    'host_count' => $hostShifts->pluck('user_id')->unique()->count(),
                    'guide_hours' => $guideHours,
                    'host_hours' => $hostHours,
                    'staff_hours' => round($guideHours + $hostHours, 2),
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
     * @return array{men_count: int, women_count: int, youth_count: int, child_count: int, unspecified_count: int, people: int}
     */
    private function emptyCounts(): array
    {
        return [
            'men_count' => 0,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'people' => 0,
        ];
    }

    /**
     * @return array{guide_count: int, host_count: int, guide_hours: float, host_hours: float, staff_hours: float, ob_hours: float}
     */
    private function emptyStaff(): array
    {
        return [
            'guide_count' => 0,
            'host_count' => 0,
            'guide_hours' => 0.0,
            'host_hours' => 0.0,
            'staff_hours' => 0.0,
            'ob_hours' => 0.0,
        ];
    }

    /**
     * @param  list<array{men_count: int, women_count: int, adult_count: int, unspecified_count: int, youth_count: int, child_count: int, people: int, revenue: float, guide_count: int, host_count: int, guide_hours: float, host_hours: float, staff_hours: float, ob_hours: float, cost: float, result: float}>  $days
     * @return array{men_count: int, women_count: int, adult_count: int, unspecified_count: int, youth_count: int, child_count: int, people: int, revenue: float, guide_count: int, host_count: int, guide_hours: float, host_hours: float, staff_hours: float, ob_hours: float, cost: float, result: float}
     */
    private function sumDays(array $days): array
    {
        $totals = [
            'men_count' => 0,
            'women_count' => 0,
            'adult_count' => 0,
            'unspecified_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'people' => 0,
            'revenue' => 0.0,
            'guide_count' => 0,
            'host_count' => 0,
            'guide_hours' => 0.0,
            'host_hours' => 0.0,
            'staff_hours' => 0.0,
            'ob_hours' => 0.0,
            'cost' => 0.0,
            'result' => 0.0,
        ];

        foreach ($days as $day) {
            $totals['men_count'] += $day['men_count'];
            $totals['women_count'] += $day['women_count'];
            $totals['adult_count'] += $day['adult_count'];
            $totals['unspecified_count'] += $day['unspecified_count'];
            $totals['youth_count'] += $day['youth_count'];
            $totals['child_count'] += $day['child_count'];
            $totals['people'] += $day['people'];
            $totals['revenue'] += $day['revenue'];
            $totals['guide_count'] += $day['guide_count'];
            $totals['host_count'] += $day['host_count'];
            $totals['guide_hours'] += $day['guide_hours'];
            $totals['host_hours'] += $day['host_hours'];
            $totals['staff_hours'] += $day['staff_hours'];
            $totals['ob_hours'] += $day['ob_hours'];
            $totals['cost'] += $day['cost'];
            $totals['result'] += $day['result'];
        }

        $totals['revenue'] = round($totals['revenue'], 2);
        $totals['guide_hours'] = round($totals['guide_hours'], 2);
        $totals['host_hours'] = round($totals['host_hours'], 2);
        $totals['staff_hours'] = round($totals['staff_hours'], 2);
        $totals['ob_hours'] = round($totals['ob_hours'], 2);
        $totals['cost'] = round($totals['cost'], 2);
        $totals['result'] = round($totals['result'], 2);

        return $totals;
    }
}
