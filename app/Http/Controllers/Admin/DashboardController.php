<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Country;
use App\Models\DailyCountryLog;
use App\Models\Production;
use App\Models\Tour;
use App\Services\FacilityReportAlertService;
use App\Services\FerryScheduleService;
use App\Services\OpeningCheckService;
use App\Services\TourDayLoadService;
use App\Services\TourWaitTimeService;
use App\Support\FerryDirections;
use App\Support\GuideShell;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function __construct(
        private TourWaitTimeService $tourWaitTimeService,
        private TourDayLoadService $tourDayLoad,
    ) {}

    public function index(Request $request)
    {
        GuideShell::clear();

        $now = now();
        $today = $now->toDateString();
        $lateTime = $now->copy()->subMinutes(10)->format('H:i:s');
        $aheadDays = $this->resolveAheadDays($request);
        $aheadEndDate = $now->copy()->addDays($aheadDays)->toDateString();
        $waitWarningMinutes = $this->tourWaitTimeService->warningMinutes();

        $todayTours = $this->tourDayLoad->todayTours($today)
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $todayTours = $this->tourWaitTimeService->attachToTours(
            $todayTours,
            $todayTours,
            $waitWarningMinutes,
        );

        $ongoingTours = $this->tourDayLoad->ongoing($todayTours);
        $upcomingToursToday = $this->tourDayLoad->upcomingToday($todayTours, $now);

        $upcomingToursToday = $this->tourWaitTimeService->attachToTours(
            $upcomingToursToday,
            $todayTours,
            $waitWarningMinutes,
        );

        $upcomingToursAhead = $this->tourDayLoad->plannedAhead($today, $aheadEndDate)
            ->map(fn (Tour $tour) => $this->decorateTour($tour));

        $lateUnstartedTours = $this->tourDayLoad->lateUnstartedBefore($today)
            ->map(fn (Tour $tour) => $this->decorateTour($tour))
            ->concat($this->tourDayLoad->lateToday($todayTours, $lateTime))
            ->sort(function (Tour $left, Tour $right): int {
                $dateCompare = strcmp(
                    $right->tour_date?->format('Y-m-d') ?? '',
                    $left->tour_date?->format('Y-m-d') ?? '',
                );

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return strcmp(
                    $this->tourDayLoad->startTime($left),
                    $this->tourDayLoad->startTime($right),
                );
            })
            ->take(10)
            ->values();

        $todayBookedPeople = $this->sumActivePeople($todayTours);
        $startedTours = $ongoingTours;
        $startedNotCompletedPeople = $this->sumActivePeople($startedTours);
        $startedToursCount = $startedTours->count();

        $totalPeopleToday = $todayBookedPeople;
        $nextTour = $upcomingToursToday->first() ?? $upcomingToursAhead->first();

        $newOpenFacilityReportsCount = 0;
        if (session('active_role') === Roles::ADMIN) {
            $newOpenFacilityReportsCount = FacilityReportAlertService::countNewOpenSinceAcknowledgmentForUser(auth()->user());
        }

        $ferrySnapshot = $this->resolveFerrySnapshot();
        $todayForeignCountries = $this->buildTodayForeignCountries($today);
        $todayLoggedCountries = $this->buildTodayLoggedCountries($today);
        $openingCheckState = $this->openingCheckDashboardState($today);
        $todayOpeningCheck = $openingCheckState['todayOpeningCheck'];
        $todayOpeningCheckCompleted = $openingCheckState['todayOpeningCheckCompleted'];
        $openOpeningDeviationCount = $openingCheckState['openOpeningDeviationCount'];
        $openingCheckTablesReady = $openingCheckState['openingCheckTablesReady'];
        $productionDashboard = $this->productionDashboardState();

        return view('admin.dashboard', compact(
            'todayTours',
            'ongoingTours',
            'upcomingToursToday',
            'upcomingToursAhead',
            'aheadDays',
            'aheadEndDate',
            'lateUnstartedTours',
            'todayBookedPeople',
            'startedNotCompletedPeople',
            'startedToursCount',
            'totalPeopleToday',
            'nextTour',
            'newOpenFacilityReportsCount',
            'ferrySnapshot',
            'todayForeignCountries',
            'todayLoggedCountries',
            'waitWarningMinutes',
            'todayOpeningCheck',
            'todayOpeningCheckCompleted',
            'openOpeningDeviationCount',
            'openingCheckTablesReady',
        ) + [
            'currentProductions' => $productionDashboard['productions'],
            'productionInsideCount' => $productionDashboard['insideCount'],
            'productionTablesReady' => $productionDashboard['tablesReady'],
        ]);
    }

    /**
     * @return array{
     *     todayOpeningCheck: mixed,
     *     todayOpeningCheckCompleted: bool,
     *     openOpeningDeviationCount: int,
     *     openingCheckTablesReady: bool
     * }
     */
    private function openingCheckDashboardState(string $today): array
    {
        $defaults = [
            'todayOpeningCheck' => null,
            'todayOpeningCheckCompleted' => false,
            'openOpeningDeviationCount' => 0,
            'openingCheckTablesReady' => false,
        ];

        try {
            if (! class_exists(OpeningCheckService::class)) {
                return $defaults;
            }

            $openingChecks = app(OpeningCheckService::class);

            if (! $openingChecks->tablesExist()) {
                return $defaults;
            }

            $check = $openingChecks->forDate($today);

            return [
                'todayOpeningCheck' => $check,
                'todayOpeningCheckCompleted' => $check?->isCompleted() ?? false,
                'openOpeningDeviationCount' => $openingChecks->openDeviationCount(),
                'openingCheckTablesReady' => true,
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }

    /**
     * @return array{productions: Collection<int, Production>, insideCount: int, tablesReady: bool}
     */
    private function productionDashboardState(): array
    {
        $defaults = [
            'productions' => collect(),
            'insideCount' => 0,
            'tablesReady' => false,
        ];

        try {
            if (! Schema::hasTable('productions') || ! Schema::hasTable('production_people')) {
                return $defaults;
            }

            $productions = Production::query()
                ->currentPeriod()
                ->withCount([
                    'people as inside_count' => fn ($query) => $query->inside(),
                ])
                ->orderBy('name')
                ->get();

            return [
                'productions' => $productions,
                'insideCount' => (int) $productions->sum('inside_count'),
                'tablesReady' => true,
            ];
        } catch (\Throwable) {
            return $defaults;
        }
    }

    /**
     * @return list<array{name: string, flag_url: string, bookings: int, people: int}>
     */
    private function buildTodayForeignCountries(string $today): array
    {
        if (! Schema::hasColumn('bookings', 'country_id') || ! Schema::hasTable('countries')) {
            return [];
        }

        $bookings = Booking::query()
            ->with('country')
            ->whereNotNull('country_id')
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', function ($query) use ($today) {
                $this->tourDayLoad->constrainToCalendarDay($query, 'tour_date', $today)
                    ->where('status', '!=', 'cancelled');
            })
            ->whereHas('country', function ($query) {
                $query->where('code', '!=', 'se');
            })
            ->get();

        return $bookings
            ->groupBy('country_id')
            ->map(function ($items) {
                $country = $items->first()->country;

                if ($country === null) {
                    return null;
                }

                return [
                    'name' => $country->name,
                    'flag_url' => $country->flagUrl(),
                    'bookings' => $items->count(),
                    'people' => (int) $items->sum('total_count'),
                ];
            })
            ->filter()
            ->sortByDesc('bookings')
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, flag_url: string, code: string}>
     */
    private function buildTodayLoggedCountries(string $today): array
    {
        if (! Schema::hasTable('daily_country_logs') || ! Schema::hasTable('daily_country_log_country')) {
            return [];
        }

        $log = $this->tourDayLoad->constrainToCalendarDay(
            DailyCountryLog::query()->with('countries'),
            'log_date',
            $today,
        )->first();

        if ($log === null) {
            return [];
        }

        return $log->countries
            ->map(fn (Country $country) => [
                'name' => $country->name,
                'flag_url' => $country->flagUrl(),
                'code' => $country->code,
            ])
            ->values()
            ->all();
    }

    private function resolveAheadDays(Request $request): int
    {
        $aheadDays = (int) $request->query('ahead_days', 7);

        return in_array($aheadDays, [7, 30], true) ? $aheadDays : 7;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveFerrySnapshot(): array
    {
        if (! class_exists(FerryScheduleService::class)
            || ! class_exists(FerryDirections::class)) {
            return [];
        }

        try {
            return app(FerryScheduleService::class)->daySnapshot(
                now(),
                FerryDirections::TO_ISLAND,
            );
        } catch (\Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * @param  Collection<int, Tour>  $tours
     */
    private function sumActivePeople($tours): int
    {
        return (int) $tours->sum(function (Tour $tour): int {
            return (int) collect($tour->bookings ?? [])
                ->whereNotIn('status', ['cancelled'])
                ->where('is_waitlist', false)
                ->sum('total_count');
        });
    }

    protected function decorateTour(Tour $tour): Tour
    {
        $activeBookings = collect($tour->bookings ?? [])
            ->whereNotIn('status', ['cancelled']);

        $tour->booked_people_count = (int) $activeBookings->sum('total_count');
        $tour->booking_groups_count = (int) $activeBookings->count();

        return $tour;
    }
}
