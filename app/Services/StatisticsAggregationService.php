<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Country;
use App\Models\StatisticsDayNote;
use App\Models\Tour;
use App\Support\StatisticsQuarterHour;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StatisticsAggregationService
{
    public function __construct(
        private TourDayLoadService $tourDayLoad,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildStats(Carbon $from, Carbon $to, ?int $tourTypeId, string $tourTypeLabel): array
    {
        $tours = $this->toursWithBookingTotals($from, $to, $tourTypeId);
        $summary = $this->bookingSummary($from, $to, $tourTypeId, $tours->count());
        $scheduleTours = $this->filterScheduleStatisticsTours($tours);

        $distribution = [
            'Män' => $summary['men_count'],
            'Kvinnor' => $summary['women_count'],
            'Ungdomar' => $summary['youth_count'],
            'Barn' => $summary['child_count'],
            'Ospecificerade' => $summary['unspecified_count'],
        ];

        $tourTypeSummary = $this->buildTourTypeSummary($tours);
        $tourTypeComparison = collect($tourTypeSummary)->sortByDesc('booked_people')->values()->all();

        return [
            'summary' => $summary,
            'distribution' => $distribution,
            'timeline' => $this->buildQuarterHourTimeline($scheduleTours),
            'day_summary' => [
                'booked' => $summary['booked_people'],
                'late_cancel' => $summary['late_cancel'],
                'no_show' => $summary['no_show'],
                'cancelled' => $summary['cancelled'],
            ],
            'booked_by_weekday' => $this->buildBookedBreakdown(
                $scheduleTours->groupBy(fn (Tour $tour) => Carbon::parse($tour->tour_date)->locale('sv')->dayName)
            ),
            'booked_by_guide' => $this->buildBookedBreakdown(
                $tours->groupBy(fn (Tour $tour) => $tour->guide?->name ?: 'Ej tilldelad')
            ),
            'popular_times' => $this->buildPopularTimes($scheduleTours),
            'tour_type_label' => $tourTypeLabel,
            'tour_type_summary' => $tourTypeSummary,
            'tour_type_comparison' => $tourTypeComparison,
            'tour_type_timeline' => $this->buildQuarterHourTourTypeTimeline($scheduleTours),
            'tour_type_insights' => [
                'top_booked' => collect($tourTypeSummary)->sortByDesc('booked_people')->first(),
                'largest_average' => collect($tourTypeSummary)->sortByDesc('avg_group_size')->first(),
            ],
            'countries_breakdown' => $this->countryBreakdownForRange($from, $to, $tourTypeId),
        ];
    }

    /**
     * @return array<int, array{date: string, date_label: string, weekday: string, booked: int, tours: int, guides: int, day_note: mixed}>
     */
    public function dailyVisitorCounts(int $year, ?int $tourTypeId = null): array
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();

        $tours = $this->scopedTours($from, $to, $tourTypeId)
            ->select(['id', 'tour_date', 'guide_id'])
            ->tap(fn (Builder $query) => $this->applyScheduleStatisticsExclusionScope($query))
            ->where('status', '!=', 'cancelled')
            ->withSum($this->activeBookingSum(), 'total_count')
            ->get();

        $coGuideIdsByTourId = $this->coGuideIdsByTourId($tours->pluck('id')->all());

        $grouped = [];

        foreach ($tours as $tour) {
            if ($tour->tour_date === null) {
                continue;
            }

            $dateKey = $tour->tour_date->toDateString();

            if (! isset($grouped[$dateKey])) {
                $parsedDate = Carbon::parse($tour->tour_date)->locale('sv');

                $grouped[$dateKey] = [
                    'date' => $dateKey,
                    'date_label' => $parsedDate->translatedFormat('j F Y'),
                    'weekday' => $parsedDate->dayName,
                    'booked' => 0,
                    'tours' => 0,
                    'guide_ids' => [],
                ];
            }

            $grouped[$dateKey]['tours']++;
            $grouped[$dateKey]['booked'] += (int) ($tour->bookings_sum_total_count ?? 0);

            if ($tour->guide_id) {
                $grouped[$dateKey]['guide_ids'][(int) $tour->guide_id] = true;
            }

            foreach ($coGuideIdsByTourId[(int) $tour->id] ?? [] as $coGuideId) {
                $grouped[$dateKey]['guide_ids'][$coGuideId] = true;
            }
        }

        $dayNotes = Schema::hasTable('statistics_day_notes')
            ? StatisticsDayNote::mapForDates(array_keys($grouped))
            : [];

        return array_values(array_map(function (array $day) use ($dayNotes): array {
            $day['guides'] = count($day['guide_ids']);
            unset($day['guide_ids']);
            $day['day_note'] = $dayNotes[$day['date']] ?? null;

            return $day;
        }, $grouped));
    }

    /**
     * @return list<array{code: string, name: string, flag_url: string, bookings: int, people: int, noted_days: int}>
     */
    public function countryBreakdownForRange(Carbon $from, Carbon $to, ?int $tourTypeId): array
    {
        /** @var array<int, array{code: string, name: string, flag_url: string, bookings: int, people: int, noted_days: int}> $rows */
        $rows = [];

        if (Schema::hasColumn('bookings', 'country_id') && Schema::hasTable('countries')) {
            $bookingRows = Booking::query()
                ->join('countries', 'countries.id', '=', 'bookings.country_id')
                ->where('bookings.is_waitlist', false)
                ->whereNotIn('bookings.status', ['cancelled'])
                ->whereRaw('LOWER(countries.code) != ?', ['se'])
                ->whereHas('tour', function (Builder $query) use ($from, $to, $tourTypeId) {
                    $this->scopeTours($query, $from, $to, $tourTypeId);
                })
                ->groupBy('countries.id', 'countries.code', 'countries.name')
                ->selectRaw('countries.id as country_id, countries.code, countries.name, COUNT(*) as bookings, COALESCE(SUM(bookings.total_count), 0) as people')
                ->get();

            foreach ($bookingRows as $row) {
                $country = new Country(['code' => $row->code]);
                $id = (int) $row->country_id;

                $rows[$id] = [
                    'code' => strtolower((string) $row->code),
                    'name' => $row->name,
                    'flag_url' => $country->flagUrl(),
                    'bookings' => (int) $row->bookings,
                    'people' => (int) $row->people,
                    'noted_days' => 0,
                ];
            }
        }

        if (Schema::hasTable('daily_country_logs') && Schema::hasTable('daily_country_log_country')) {
            $logRows = DB::table('daily_country_log_country')
                ->join('daily_country_logs', 'daily_country_logs.id', '=', 'daily_country_log_country.daily_country_log_id')
                ->join('countries', 'countries.id', '=', 'daily_country_log_country.country_id')
                ->where('daily_country_logs.log_date', '>=', $from->toDateString())
                ->where('daily_country_logs.log_date', '<', $this->tourDayLoad->nextCalendarDay($to->toDateString()))
                ->whereRaw('LOWER(countries.code) != ?', ['se'])
                ->groupBy('countries.id', 'countries.code', 'countries.name')
                ->selectRaw('countries.id as country_id, countries.code, countries.name, COUNT(*) as noted_days')
                ->get();

            foreach ($logRows as $row) {
                $id = (int) $row->country_id;

                if (! isset($rows[$id])) {
                    $country = new Country(['code' => $row->code]);
                    $rows[$id] = [
                        'code' => strtolower((string) $row->code),
                        'name' => $row->name,
                        'flag_url' => $country->flagUrl(),
                        'bookings' => 0,
                        'people' => 0,
                        'noted_days' => 0,
                    ];
                }

                $rows[$id]['noted_days'] = (int) $row->noted_days;
            }
        }

        return collect($rows)
            ->sortBy([
                ['bookings', 'desc'],
                ['noted_days', 'desc'],
                ['name', 'asc'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Tour>
     */
    private function toursWithBookingTotals(Carbon $from, Carbon $to, ?int $tourTypeId): Collection
    {
        $columns = ['id', 'tour_date', 'start_time', 'guide_id', 'tour_type_id'];

        if (Schema::hasColumn('tours', 'exclude_from_schedule_statistics')) {
            $columns[] = 'exclude_from_schedule_statistics';
        }

        return $this->scopedTours($from, $to, $tourTypeId)
            ->select($columns)
            ->with(['guide:id,name', 'tourType:id,name'])
            ->withSum($this->activeBookingSum(), 'total_count')
            ->withCount($this->activeBookingSum())
            ->get();
    }

    /**
     * @return array<string, int|float>
     */
    private function bookingSummary(Carbon $from, Carbon $to, ?int $tourTypeId, int $tourCount): array
    {
        $aggregates = Booking::query()
            ->whereHas('tour', function (Builder $query) use ($from, $to, $tourTypeId) {
                $this->scopeTours($query, $from, $to, $tourTypeId);
            })
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw("SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled")
            ->selectRaw('SUM(CASE WHEN is_waitlist = 1 THEN 1 ELSE 0 END) as waitlist')
            ->selectRaw("SUM(CASE WHEN arrival_status = 'late_cancel' THEN 1 ELSE 0 END) as late_cancel")
            ->selectRaw("SUM(CASE WHEN arrival_status = 'no_show' THEN 1 ELSE 0 END) as no_show")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN total_count ELSE 0 END) as booked_people")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN men_count ELSE 0 END) as men_count")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN women_count ELSE 0 END) as women_count")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN youth_count ELSE 0 END) as youth_count")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN child_count ELSE 0 END) as child_count")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN unspecified_count ELSE 0 END) as unspecified_count")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' THEN 1 ELSE 0 END) as active_booking_count")
            ->selectRaw("SUM(CASE WHEN is_waitlist = 0 AND status != 'cancelled' AND (men_count > 0 OR women_count > 0) AND (youth_count > 0 OR child_count > 0) THEN 1 ELSE 0 END) as family_bookings_count")
            ->first();

        $activeBookingCount = (int) ($aggregates?->active_booking_count ?? 0);
        $familyBookingsCount = (int) ($aggregates?->family_bookings_count ?? 0);
        $nonSwedishLanguageBookingsCount = $this->nonSwedishLanguageBookingCount($from, $to, $tourTypeId);
        $foreignCountryBookingsCount = $this->foreignCountryBookingCount($from, $to, $tourTypeId);

        return [
            'tours' => $tourCount,
            'bookings' => (int) ($aggregates?->bookings ?? 0),
            'booked_people' => (int) ($aggregates?->booked_people ?? 0),
            'cancelled' => (int) ($aggregates?->cancelled ?? 0),
            'waitlist' => (int) ($aggregates?->waitlist ?? 0),
            'late_cancel' => (int) ($aggregates?->late_cancel ?? 0),
            'no_show' => (int) ($aggregates?->no_show ?? 0),
            'men_count' => (int) ($aggregates?->men_count ?? 0),
            'women_count' => (int) ($aggregates?->women_count ?? 0),
            'youth_count' => (int) ($aggregates?->youth_count ?? 0),
            'child_count' => (int) ($aggregates?->child_count ?? 0),
            'unspecified_count' => (int) ($aggregates?->unspecified_count ?? 0),
            'family_bookings_count' => $familyBookingsCount,
            'family_bookings_share' => $activeBookingCount > 0
                ? round(($familyBookingsCount / $activeBookingCount) * 100, 1)
                : 0,
            'non_swedish_language_bookings_count' => $nonSwedishLanguageBookingsCount,
            'non_swedish_language_bookings_share' => $activeBookingCount > 0
                ? round(($nonSwedishLanguageBookingsCount / $activeBookingCount) * 100, 1)
                : 0,
            'foreign_country_bookings_count' => $foreignCountryBookingsCount,
            'foreign_country_bookings_share' => $activeBookingCount > 0
                ? round(($foreignCountryBookingsCount / $activeBookingCount) * 100, 1)
                : 0,
        ];
    }

    private function nonSwedishLanguageBookingCount(Carbon $from, Carbon $to, ?int $tourTypeId): int
    {
        if (! Schema::hasTable('booking_language') || ! Schema::hasTable('languages')) {
            return 0;
        }

        return (int) Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', function (Builder $query) use ($from, $to, $tourTypeId) {
                $this->scopeTours($query, $from, $to, $tourTypeId);
            })
            ->whereHas('languages', fn (Builder $query) => $query->where('code', '!=', 'sv'))
            ->count();
    }

    private function foreignCountryBookingCount(Carbon $from, Carbon $to, ?int $tourTypeId): int
    {
        if (! Schema::hasColumn('bookings', 'country_id') || ! Schema::hasTable('countries')) {
            return 0;
        }

        return (int) Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', function (Builder $query) use ($from, $to, $tourTypeId) {
                $this->scopeTours($query, $from, $to, $tourTypeId);
            })
            ->whereHas('country', fn (Builder $query) => $query->whereRaw('LOWER(code) != ?', ['se']))
            ->count();
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return list<array{label: string, booked: int}>
     */
    private function buildBookedBreakdown(Collection $grouped): array
    {
        return $grouped->map(function (Collection $items, $label) {
            return [
                'label' => $label,
                'booked' => (int) $items->sum(fn (Tour $tour) => (int) ($tour->bookings_sum_total_count ?? 0)),
            ];
        })->sortByDesc('booked')->values()->all();
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return list<array{label: string, tours: int, booked_people: int, avg_group_size: float|int}>
     */
    private function buildTourTypeSummary(Collection $tours): array
    {
        return $tours
            ->groupBy(fn (Tour $tour) => $tour->tourType?->name ?? 'Ej angiven')
            ->map(function (Collection $items, $label) {
                $bookedPeople = (int) $items->sum(fn (Tour $tour) => (int) ($tour->bookings_sum_total_count ?? 0));
                $tourCount = $items->count();

                return [
                    'label' => $label,
                    'tours' => $tourCount,
                    'booked_people' => $bookedPeople,
                    'avg_group_size' => $tourCount > 0 ? round($bookedPeople / $tourCount, 1) : 0,
                ];
            })
            ->sortByDesc('booked_people')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return array{labels: array<int, string>, booked: array<int, int>, tours: array<int, int>}
     */
    private function buildQuarterHourTimeline(Collection $tours): array
    {
        $slotData = [];

        foreach ($tours as $tour) {
            if (empty($tour->start_time)) {
                continue;
            }

            $slot = StatisticsQuarterHour::slotStartFromTime((string) $tour->start_time);

            if (! isset($slotData[$slot])) {
                $slotData[$slot] = ['booked' => 0, 'tours' => 0];
            }

            $slotData[$slot]['tours']++;
            $slotData[$slot]['booked'] += (int) ($tour->bookings_sum_total_count ?? 0);
        }

        ksort($slotData);

        $labels = [];
        $booked = [];
        $tourCounts = [];

        foreach ($slotData as $slot => $data) {
            $labels[] = StatisticsQuarterHour::formatLabel($slot);
            $booked[] = $data['booked'];
            $tourCounts[] = $data['tours'];
        }

        return [
            'labels' => $labels,
            'booked' => $booked,
            'tours' => $tourCounts,
        ];
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return array{labels: array<int, string>, series: array<int, array{label: string, data: array<int, int>}>}
     */
    private function buildQuarterHourTourTypeTimeline(Collection $tours): array
    {
        $slots = $tours
            ->filter(fn (Tour $tour) => ! empty($tour->start_time))
            ->map(fn (Tour $tour) => StatisticsQuarterHour::slotStartFromTime((string) $tour->start_time))
            ->unique()
            ->sort()
            ->values()
            ->all();

        $labels = array_map(
            fn (int $slot) => StatisticsQuarterHour::formatLabel($slot),
            $slots
        );

        $tourTypes = $tours
            ->map(fn (Tour $tour) => $tour->tourType?->name ?? 'Ej angiven')
            ->unique()
            ->values();

        $series = $tourTypes->map(function (string $tourTypeName) use ($tours, $slots) {
            $data = collect($slots)->map(function (int $slot) use ($tours, $tourTypeName) {
                return (int) $tours
                    ->filter(function (Tour $tour) use ($slot, $tourTypeName) {
                        if (empty($tour->start_time)) {
                            return false;
                        }

                        $matchesSlot = StatisticsQuarterHour::slotStartFromTime((string) $tour->start_time) === $slot;
                        $matchesType = ($tour->tourType?->name ?? 'Ej angiven') === $tourTypeName;

                        return $matchesSlot && $matchesType;
                    })
                    ->sum(fn (Tour $tour) => (int) ($tour->bookings_sum_total_count ?? 0));
            })->values()->all();

            return [
                'label' => $tourTypeName,
                'data' => $data,
            ];
        })->filter(fn (array $series) => array_sum($series['data']) > 0)->values()->all();

        return [
            'labels' => $labels,
            'series' => $series,
        ];
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return array<int, array{label: string, bookings: int, booked_people: int}>
     */
    private function buildPopularTimes(Collection $tours): array
    {
        $grouped = [];

        foreach ($tours as $tour) {
            if (empty($tour->start_time)) {
                continue;
            }

            $slot = StatisticsQuarterHour::slotStartFromTime((string) $tour->start_time);

            if (! isset($grouped[$slot])) {
                $grouped[$slot] = [
                    'label' => StatisticsQuarterHour::formatLabel($slot),
                    'bookings' => 0,
                    'booked_people' => 0,
                ];
            }

            $grouped[$slot]['bookings'] += (int) ($tour->bookings_count ?? 0);
            $grouped[$slot]['booked_people'] += (int) ($tour->bookings_sum_total_count ?? 0);
        }

        return collect($grouped)
            ->sortKeys()
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return Collection<int, Tour>
     */
    private function filterScheduleStatisticsTours(Collection $tours): Collection
    {
        return $tours->reject(function (Tour $tour) {
            if (! Schema::hasColumn('tours', 'exclude_from_schedule_statistics')) {
                return false;
            }

            return (bool) $tour->exclude_from_schedule_statistics;
        });
    }

    /**
     * @return Builder<Tour>
     */
    private function scopedTours(Carbon $from, Carbon $to, ?int $tourTypeId): Builder
    {
        $query = Tour::query();
        $this->scopeTours($query, $from, $to, $tourTypeId);

        return $query;
    }

    private function scopeTours(Builder $query, Carbon $from, Carbon $to, ?int $tourTypeId): void
    {
        $this->tourDayLoad->constrainToCalendarRange($query, 'tour_date', $from, $to);

        if ($tourTypeId !== null) {
            $query->where('tour_type_id', $tourTypeId);
        }
    }

    private function applyScheduleStatisticsExclusionScope(Builder $query): void
    {
        if (Schema::hasColumn('tours', 'exclude_from_schedule_statistics')) {
            $query->where('exclude_from_schedule_statistics', false);
        }
    }

    /**
     * @return array<string, \Closure>
     */
    private function activeBookingSum(): array
    {
        return [
            'bookings' => function ($query) {
                $query->where('is_waitlist', false)
                    ->whereNotIn('status', ['cancelled']);
            },
        ];
    }

    /**
     * @param  list<int|string>  $tourIds
     * @return array<int, list<int>>
     */
    private function coGuideIdsByTourId(array $tourIds): array
    {
        if ($tourIds === [] || ! Schema::hasTable('tour_guide')) {
            return [];
        }

        $grouped = [];

        foreach (DB::table('tour_guide')->whereIn('tour_id', $tourIds)->get(['tour_id', 'user_id']) as $row) {
            $grouped[(int) $row->tour_id][] = (int) $row->user_id;
        }

        return $grouped;
    }
}
