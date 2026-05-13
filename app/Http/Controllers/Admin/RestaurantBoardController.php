<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\WorkShift;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class RestaurantBoardController extends Controller
{
    public function index(): View
    {
        return view('admin.restaurant-board.index', $this->buildBoardData());
    }

    public function kiosk(): View
    {
        return view('admin.restaurant-board.kiosk', $this->buildBoardData());
    }

    public function statistik(): View
    {
        return view('admin.restaurant-board.kiosk', $this->buildBoardData());
    }

    protected function buildBoardData(): array
    {
        $now = now();
        $today = $now->toDateString();

        $ongoingTours = Tour::with([
                'guide',
                'tourType',
                'bookings.languages',
            ])
            ->whereDate('tour_date', $today)
            ->where('status', 'started')
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateOngoingTour($tour));

        $upcomingTours = Tour::with([
                'guide',
                'tourType',
                'bookings.languages',
            ])
            ->whereDate('tour_date', $today)
            ->where('status', 'planned')
            ->whereTime('start_time', '>=', $now->format('H:i:s'))
            ->orderBy('start_time')
            ->get()
            ->map(fn (Tour $tour) => $this->decorateUpcomingTour($tour));

        $totalOngoingGuests = (int) $ongoingTours->sum('booked_people_count');
        $totalUpcomingGuests = (int) $upcomingTours->sum('booked_people_count');

        $ongoingParticipantBreakdown = [
            'men' => 0,
            'women' => 0,
            'youth' => 0,
            'children' => 0,
        ];

        foreach ($ongoingTours as $tour) {
            $ongoingParticipantBreakdown['men'] += (int) ($tour->men_count_total ?? 0);
            $ongoingParticipantBreakdown['women'] += (int) ($tour->women_count_total ?? 0);
            $ongoingParticipantBreakdown['youth'] += (int) ($tour->youth_count_total ?? 0);
            $ongoingParticipantBreakdown['children'] += (int) ($tour->child_count_total ?? 0);
        }

        $todayShifts = WorkShift::query()
            ->with('user')
            ->whereDate('shift_date', $today)
            ->where('shift_role', 'restaurant')
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        $restaurantFunctions = method_exists(WorkShift::class, 'restaurantFunctions')
            ? WorkShift::restaurantFunctions()
            : [
                'kock' => 'Kock',
                'kallskank' => 'Kallskänk',
                'kassa' => 'Kassa',
                'disk' => 'Disk',
                'glassbar' => 'Glassbar',
                'servering' => 'Servering',
            ];

        $todayStaffByFunction = $todayShifts
            ->groupBy(fn ($shift) => $shift->shift_function ?: 'ovrigt')
            ->sortKeys();

        return [
            'ongoingTours' => $ongoingTours,
            'upcomingTours' => $upcomingTours,
            'totalOngoingGuests' => $totalOngoingGuests,
            'totalUpcomingGuests' => $totalUpcomingGuests,
            'ongoingParticipantBreakdown' => $ongoingParticipantBreakdown,
            'todayShifts' => $todayShifts,
            'todayStaffByFunction' => $todayStaffByFunction,
            'restaurantFunctions' => $restaurantFunctions,
            'nowLabel' => $now->format('Y-m-d H:i'),
        ];
    }

    protected function decorateOngoingTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();

        $tour->men_count_total = (int) $activeBookings->sum('men_count');
        $tour->women_count_total = (int) $activeBookings->sum('women_count');
        $tour->youth_count_total = (int) $activeBookings->sum('youth_count');
        $tour->child_count_total = (int) $activeBookings->sum('child_count');

        $tour->estimated_end_time = '-';
        $tour->remaining_to_end = '-';

        if (!empty($tour->started_at) && !empty($tour->start_time) && !empty($tour->end_time)) {
            try {
                $plannedStart = $this->timeFromString($tour->start_time);
                $plannedEnd = $this->timeFromString($tour->end_time);

                $durationMinutes = $plannedStart->diffInMinutes($plannedEnd, false);

                if ($durationMinutes > 0) {
                    $actualEndAt = Carbon::parse($tour->started_at)->addMinutes($durationMinutes);

                    $tour->estimated_end_time = $actualEndAt->format('H:i');
                    $tour->remaining_to_end = $this->formatRemainingMinutes(
                        (int) now()->diffInMinutes($actualEndAt, false)
                    );
                }
            } catch (\Throwable $e) {
                $tour->estimated_end_time = '-';
                $tour->remaining_to_end = '-';
            }
        }

        return $tour;
    }

    protected function decorateUpcomingTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();

        $tour->estimated_end_time = '-';
        $tour->time_until_start = '-';

        if (!empty($tour->tour_date) && !empty($tour->start_time)) {
            try {
                $startAt = Carbon::parse($tour->tour_date . ' ' . $tour->start_time);
                $tour->time_until_start = $this->formatUntilStart(
                    (int) now()->diffInMinutes($startAt, false)
                );
            } catch (\Throwable $e) {
                $tour->time_until_start = '-';
            }
        }

        if (!empty($tour->start_time) && !empty($tour->end_time)) {
            try {
                $tour->estimated_end_time = substr($this->timeFromString($tour->end_time)->format('H:i:s'), 0, 5);
            } catch (\Throwable $e) {
                $tour->estimated_end_time = '-';
            }
        }

        return $tour;
    }

    protected function timeFromString(string $time): Carbon
    {
        $normalized = strlen($time) === 5 ? $time . ':00' : $time;

        return Carbon::createFromFormat('H:i:s', $normalized);
    }

    protected function formatRemainingMinutes(int $minutes): string
    {
        if ($minutes > 60) {
            $hours = floor($minutes / 60);
            $restMinutes = $minutes % 60;

            return $restMinutes > 0
                ? $hours . 'h ' . $restMinutes . ' min kvar'
                : $hours . 'h kvar';
        }

        if ($minutes > 0) {
            return $minutes . ' min kvar';
        }

        if ($minutes === 0) {
            return 'slutar nu';
        }

        return 'borde vara klar';
    }

    protected function formatUntilStart(int $minutes): string
    {
        if ($minutes > 60) {
            $hours = floor($minutes / 60);
            $restMinutes = $minutes % 60;

            return $restMinutes > 0
                ? $hours . 'h ' . $restMinutes . ' min'
                : $hours . 'h';
        }

        if ($minutes > 0) {
            return $minutes . ' min';
        }

        if ($minutes === 0) {
            return 'Nu';
        }

        return 'Påbörjad';
    }
}