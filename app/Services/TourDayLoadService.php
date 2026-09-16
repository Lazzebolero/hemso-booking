<?php

namespace App\Services;

use App\Models\Tour;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TourDayLoadService
{
    public function __construct(
        private TourCoGuideService $tourCoGuideService,
    ) {}

    /**
     * Inclusive calendar day. Half-open range works for DATE and DATETIME on SQLite and MySQL.
     */
    public function constrainToCalendarDay(Builder $query, string $column, string $date): Builder
    {
        return $query
            ->where($column, '>=', $date)
            ->where($column, '<', $this->nextCalendarDay($date));
    }

    /**
     * Inclusive calendar-day range. Half-open so DATE and DATETIME match on SQLite and MySQL.
     */
    public function constrainToCalendarRange(Builder $query, string $column, Carbon|string $from, Carbon|string $toInclusive): Builder
    {
        $fromDate = $from instanceof Carbon ? $from->toDateString() : $from;
        $toDate = $toInclusive instanceof Carbon ? $toInclusive->toDateString() : $toInclusive;

        return $query
            ->where($column, '>=', $fromDate)
            ->where($column, '<', $this->nextCalendarDay($toDate));
    }

    /**
     * @return Collection<int, Tour>
     */
    public function todayTours(string $today): Collection
    {
        return $this->constrainToCalendarDay(
            Tour::query()->with($this->tourCoGuideService->tourDisplayRelations()),
            'tour_date',
            $today,
        )
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @return Collection<int, Tour>
     */
    public function plannedAhead(string $today, string $aheadEndDate): Collection
    {
        return Tour::query()
            ->with($this->tourCoGuideService->tourDisplayRelations())
            ->where('status', 'planned')
            ->where('tour_date', '>=', $this->nextCalendarDay($today))
            ->where('tour_date', '<', $this->nextCalendarDay($aheadEndDate))
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @return Collection<int, Tour>
     */
    public function lateUnstartedBefore(string $today, int $limit = 10): Collection
    {
        return Tour::query()
            ->with($this->tourCoGuideService->tourDisplayRelations())
            ->where('status', 'planned')
            ->where('tour_date', '<', $today)
            ->orderByDesc('tour_date')
            ->orderBy('start_time')
            ->take($limit)
            ->get();
    }

    public function nextCalendarDay(string $date): string
    {
        return Carbon::parse($date)->addDay()->toDateString();
    }

    /**
     * @param  Collection<int, Tour>  $todayTours
     * @return Collection<int, Tour>
     */
    public function ongoing(Collection $todayTours): Collection
    {
        return $todayTours->where('status', 'started')->values();
    }

    /**
     * @param  Collection<int, Tour>  $todayTours
     * @return Collection<int, Tour>
     */
    public function upcomingToday(Collection $todayTours, Carbon $now): Collection
    {
        $nowTime = $now->format('H:i:s');

        return $todayTours
            ->filter(fn (Tour $tour): bool => $tour->status === 'planned' && $this->startTime($tour) >= $nowTime)
            ->values();
    }

    /**
     * @param  Collection<int, Tour>  $todayTours
     * @return Collection<int, Tour>
     */
    public function lateToday(Collection $todayTours, string $lateTime): Collection
    {
        return $todayTours
            ->filter(fn (Tour $tour): bool => $tour->status === 'planned' && $this->startTime($tour) < $lateTime)
            ->values();
    }

    public function startTime(Tour $tour): string
    {
        $start = (string) $tour->start_time;

        if (strlen($start) >= 8) {
            return substr($start, 0, 8);
        }

        if (strlen($start) === 5) {
            return $start.':00';
        }

        return $start;
    }
}
