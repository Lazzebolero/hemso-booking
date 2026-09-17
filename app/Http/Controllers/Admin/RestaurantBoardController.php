<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\WorkShift;
use App\Services\FerryScheduleService;
use App\Services\TourCoGuideService;
use App\Services\TourDayLoadService;
use App\Support\ActiveRole;
use App\Support\FerryDayTypes;
use App\Support\FerryDirections;
use App\Support\FerryStrinningenTimetable;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class RestaurantBoardController extends Controller
{
    public function __construct(
        private TourCoGuideService $tourCoGuideService,
        private FerryScheduleService $ferrySchedule,
        private TourDayLoadService $tourDayLoad,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.restaurant-board.index', $this->boardData($request));
    }

    /**
     * @return array<string, mixed>
     */
    public function boardData(?Request $request = null): array
    {
        return $this->buildBoardData($request ?? request());
    }

    public function kiosk(Request $request): View
    {
        return view('admin.restaurant-board.kiosk', $this->boardData($request));
    }

    public function poll(Request $request): JsonResponse
    {
        $data = $this->boardData($request);

        return response()->json([
            'now_label' => $data['nowLabel'],
            'html' => view('admin.restaurant-board.partials.kiosk-live', $data)->render(),
        ]);
    }

    public function statistik(Request $request): View
    {
        return view('admin.restaurant-board.kiosk', $this->boardData($request));
    }

    public function ferryTimetable(Request $request): View
    {
        return view('admin.restaurant-board.ferry-timetable', $this->ferryTimetableData($request));
    }

    /**
     * @return array<string, mixed>
     */
    public function ferryTimetableData(Request $request): array
    {
        $dayType = $request->string('day_type')->toString();
        if (! in_array($dayType, FerryDayTypes::all(), true) && $dayType !== FerryDayTypes::WEEKEND) {
            $dayType = FerryDayTypes::forDate(now());
        } else {
            $dayType = FerryDayTypes::normalize($dayType);
        }

        return [
            'ferrySnapshot' => $this->ferrySchedule->daySnapshot(now(), FerryDirections::TO_ISLAND),
            'dayType' => $dayType,
            'departures' => FerryStrinningenTimetable::departuresFor($dayType),
            'timetableRevision' => FerryStrinningenTimetable::revision(),
            'nowLabel' => now()->format('Y-m-d H:i'),
            'backUrl' => $this->ferryTimetableBackUrl($request),
        ];
    }

    protected function buildBoardData(Request $request): array
    {
        $now = now();
        $today = $now->toDateString();
        $aheadDays = $this->resolveAheadDays($request);
        $aheadEndDate = $now->copy()->addDays($aheadDays)->toDateString();

        $todayTours = $this->tourDayLoad->todayTours($today)
            ->map(fn (Tour $tour) => $this->decorateTodayTour($tour));

        $ongoingTours = $this->tourDayLoad->ongoing($todayTours)
            ->map(fn (Tour $tour) => $this->decorateOngoingTour($tour));

        $upcomingToursToday = $this->tourDayLoad->upcomingToday($todayTours, $now)
            ->map(fn (Tour $tour) => $this->decorateUpcomingTour($tour));

        $upcomingToursAhead = $this->tourDayLoad->plannedAhead($today, $aheadEndDate)
            ->map(fn (Tour $tour) => $this->decorateUpcomingTour($tour));

        $totalOngoingGuests = (int) $ongoingTours->sum('booked_people_count');
        $totalUpcomingGuests = (int) $upcomingToursToday->sum('booked_people_count')
            + (int) $upcomingToursAhead->sum('booked_people_count');

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

        $todayShifts = $this->tourDayLoad->constrainToCalendarDay(
            WorkShift::query()->with('user'),
            'shift_date',
            $today,
        )
            ->where('shift_role', 'restaurant')
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('start_time')
            ->get();

        $restaurantFunctions = WorkShift::restaurantFunctions();

        $todayStaffByFunction = $todayShifts
            ->groupBy(fn ($shift) => $shift->shift_function ?: 'ovrigt')
            ->sortKeys();

        $totalTodayVisitors = $this->countTodayVisitors($today);

        return [
            'ongoingTours' => $ongoingTours,
            'upcomingToursToday' => $upcomingToursToday,
            'upcomingToursAhead' => $upcomingToursAhead,
            'aheadDays' => $aheadDays,
            'aheadEndDate' => $aheadEndDate,
            'todayTours' => $todayTours,
            'totalOngoingGuests' => $totalOngoingGuests,
            'totalUpcomingGuests' => $totalUpcomingGuests,
            'totalTodayVisitors' => $totalTodayVisitors,
            'ongoingParticipantBreakdown' => $ongoingParticipantBreakdown,
            'todayShifts' => $todayShifts,
            'todayStaffByFunction' => $todayStaffByFunction,
            'restaurantFunctions' => $restaurantFunctions,
            'nowLabel' => $now->format('Y-m-d H:i'),
            'ferrySnapshot' => $this->ferrySchedule->daySnapshot($now, FerryDirections::TO_ISLAND),
            'ferryTimetableUrl' => $this->ferryTimetableUrlForRequest($request),
            'boardPollUrl' => $this->boardPollUrl($request),
            'restaurantBoardKioskRoute' => $this->kioskRouteName($request),
        ];
    }

    protected function ferryTimetableUrlForRequest(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant-statistics.')) {
            return route('restaurant-statistics.ferry-timetable');
        }

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant-statistik.')) {
            return route('restaurant-statistik.ferry-timetable');
        }

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant.')) {
            return route('restaurant.ferry-timetable');
        }

        return route(ActiveRole::routeName('restaurant-board.ferry-timetable'));
    }

    protected function boardPollUrl(Request $request): string
    {
        $query = ['ahead_days' => $this->resolveAheadDays($request)];
        $routeName = $request->route()?->getName();

        $name = match (true) {
            is_string($routeName) && str_starts_with($routeName, 'restaurant-statistik.') => 'restaurant-statistik.poll',
            is_string($routeName) && str_starts_with($routeName, 'restaurant.') => 'restaurant.poll',
            is_string($routeName) && str_starts_with($routeName, 'host.') => 'host.restaurant-board.poll',
            default => ActiveRole::routeName('restaurant-board.poll'),
        };

        if (! Route::has($name)) {
            return '';
        }

        return route($name, $query);
    }

    protected function kioskRouteName(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant-statistik.')) {
            return 'restaurant-statistik.dashboard';
        }

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant.')) {
            return 'restaurant.kiosk';
        }

        if (is_string($routeName) && str_starts_with($routeName, 'host.')) {
            return 'host.restaurant-board.kiosk';
        }

        return ActiveRole::routeName('restaurant-board.kiosk');
    }

    protected function ferryTimetableBackUrl(Request $request): string
    {
        $routeName = $request->route()?->getName();

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant-statistics.')) {
            return route('restaurant-statistics.dashboard');
        }

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant-statistik.')) {
            return route('restaurant-statistik.dashboard');
        }

        if (is_string($routeName) && str_starts_with($routeName, 'restaurant.')) {
            return route('restaurant.kiosk');
        }

        return route(ActiveRole::routeName('restaurant-board.kiosk'));
    }

    protected function countTodayVisitors(string $today): int
    {
        return (int) Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', function ($query) use ($today) {
                $this->tourDayLoad->constrainToCalendarDay($query, 'tour_date', $today)
                    ->whereNotIn('status', ['cancelled', 'started']);
            })
            ->sum('total_count');
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

        if (! empty($tour->started_at) && ! empty($tour->start_time) && ! empty($tour->end_time)) {
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

        return $this->tourCoGuideService->decorateTourGuideDisplay($tour);
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

        if (! empty($tour->tour_date) && ! empty($tour->start_time)) {
            try {
                $startAt = Carbon::parse($tour->tour_date.' '.$tour->start_time);
                $tour->time_until_start = $this->formatUntilStart(
                    (int) now()->diffInMinutes($startAt, false)
                );
            } catch (\Throwable $e) {
                $tour->time_until_start = '-';
            }
        }

        if (! empty($tour->start_time) && ! empty($tour->end_time)) {
            try {
                $tour->estimated_end_time = substr($this->timeFromString($tour->end_time)->format('H:i:s'), 0, 5);
            } catch (\Throwable $e) {
                $tour->estimated_end_time = '-';
            }
        }

        return $this->tourCoGuideService->decorateTourGuideDisplay($tour);
    }

    protected function decorateTodayTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();

        return $this->tourCoGuideService->decorateTourGuideDisplay($tour);
    }

    protected function timeFromString(string $time): Carbon
    {
        $normalized = strlen($time) === 5 ? $time.':00' : $time;

        return Carbon::createFromFormat('H:i:s', $normalized);
    }

    protected function formatRemainingMinutes(int $minutes): string
    {
        if ($minutes > 60) {
            $hours = floor($minutes / 60);
            $restMinutes = $minutes % 60;

            return $restMinutes > 0
                ? $hours.'h '.$restMinutes.' min kvar'
                : $hours.'h kvar';
        }

        if ($minutes > 0) {
            return $minutes.' min kvar';
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
                ? $hours.'h '.$restMinutes.' min'
                : $hours.'h';
        }

        if ($minutes > 0) {
            return $minutes.' min';
        }

        if ($minutes === 0) {
            return 'Nu';
        }

        return 'Påbörjad';
    }

    private function resolveAheadDays(Request $request): int
    {
        $aheadDays = (int) $request->query('ahead_days', 7);

        return in_array($aheadDays, [7, 30], true) ? $aheadDays : 7;
    }
}
