<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TourStaffingSimulatorService
{
    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function simulate(Carbon $date, array $params = []): array
    {
        $normalized = $this->normalizeParams($params);
        $day = $date->copy()->startOfDay();

        $sameDayBookings = $this->sameDayBookings($day);
        $advance = $this->advanceBookingsSummary($day);
        $actual = $this->actualToursSummary($day);
        $slots = $this->candidateStarts($day, $normalized);
        $guides = $this->buildGuides($day, $normalized);
        $firstSlot = $slots[0] ?? Carbon::parse($day->toDateString().' '.$normalized['first_tour']);
        $lastSlot = $slots === []
            ? Carbon::parse($day->toDateString().' '.$normalized['last_tour'])
            : $slots[array_key_last($slots)]->copy();

        /** @var list<array{start: Carbon, end: Carbon, guide_index: int, people: int, capacity: int, bookings: int, is_extra_15: bool, max_guest_wait: int, people_over_max_wait: int, exceeds_max_wait: bool}> $tours */
        $tours = [];
        $unassigned = [];
        $waits = [];

        foreach ($sameDayBookings as $booking) {
            $createdAt = Carbon::parse($booking->created_at);
            $remaining = $this->bookingPeople($booking);

            while ($remaining > 0) {
                $tourIndex = $this->findOpenTourWithSpace($tours, $createdAt, $normalized['capacity']);

                if ($tourIndex === null) {
                    if (! $this->tryOpenTour($tours, $guides, $slots, $createdAt, $firstSlot, $lastSlot, $normalized)) {
                        $unassigned[] = [
                            'booking_id' => (int) $booking->id,
                            'created_at' => $createdAt->format('H:i'),
                            'people' => $remaining,
                            'reason' => 'Ingen ledig tur eller guide efter skapandetid',
                        ];
                        break;
                    }

                    $tourIndex = array_key_last($tours);
                }

                $space = $normalized['capacity'] - $tours[$tourIndex]['people'];
                $take = min($remaining, $space);

                if ($take <= 0) {
                    break;
                }

                $wait = $this->queueWaitMinutes($createdAt, $tours[$tourIndex]['start'], $firstSlot);
                $waits[] = $wait;
                $tours[$tourIndex]['people'] += $take;
                $tours[$tourIndex]['bookings']++;
                $tours[$tourIndex]['max_guest_wait'] = max($tours[$tourIndex]['max_guest_wait'], $wait);

                if ($wait > $normalized['max_wait_minutes']) {
                    $tours[$tourIndex]['exceeds_max_wait'] = true;
                    $tours[$tourIndex]['people_over_max_wait'] += $take;
                }

                $remaining -= $take;
            }
        }

        usort($tours, fn (array $a, array $b) => $a['start']->timestamp <=> $b['start']->timestamp);

        $tourRows = array_map(function (array $tour) {
            return [
                'start' => $tour['start']->format('H:i'),
                'end' => $tour['end']->format('H:i'),
                'guide_index' => $tour['guide_index'] + 1,
                'people' => $tour['people'],
                'capacity' => $tour['capacity'],
                'fill_percent' => $tour['capacity'] > 0
                    ? round(($tour['people'] / $tour['capacity']) * 100, 1)
                    : 0.0,
                'bookings' => $tour['bookings'],
                'is_extra_15' => $tour['is_extra_15'],
                'max_guest_wait' => $tour['max_guest_wait'],
                'people_over_max_wait' => $tour['people_over_max_wait'],
                'exceeds_max_wait' => $tour['exceeds_max_wait'],
            ];
        }, $tours);

        $guideRows = $this->buildGuideSummaries($tours, $normalized);
        $assignedPeople = array_sum(array_column($tourRows, 'people'));
        $unassignedPeople = array_sum(array_column($unassigned, 'people'));
        $cycleMinutes = $normalized['duration_minutes'] + $normalized['buffer_minutes'];
        $toursOverMax = count(array_filter($tourRows, fn (array $tour) => $tour['exceeds_max_wait']));
        $extra15Count = count(array_filter($tourRows, fn (array $tour) => $tour['is_extra_15']));

        return [
            'params' => $normalized,
            'demand' => [
                'bookings' => $sameDayBookings->count(),
                'people' => (int) $sameDayBookings->sum(fn (Booking $booking) => $this->bookingPeople($booking)),
            ],
            'advance_bookings' => $advance,
            'actual' => $actual,
            'tours' => $tourRows,
            'guides' => $guideRows,
            'unassigned' => $unassigned,
            'waits' => $waits,
            'metrics' => [
                'tours' => count($tourRows),
                'assigned_people' => $assignedPeople,
                'unassigned_people' => $unassignedPeople,
                'wait_avg' => $waits === [] ? null : round(array_sum($waits) / count($waits), 1),
                'wait_max' => $waits === [] ? null : max($waits),
                'wait_p90' => $this->percentile($waits, 90),
                'guide_hours' => round((count($tours) * $cycleMinutes) / 60, 2),
                'tours_over_max_wait' => $toursOverMax,
                'extra_15_tours' => $extra15Count,
                'max_wait_target' => $normalized['max_wait_minutes'],
                'guides_free_for_restaurant' => count(array_filter(
                    $guideRows,
                    fn (array $guide) => $guide['last_tour_end'] !== null
                )),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function normalizeParams(array $params): array
    {
        $guideCount = max(1, min(8, (int) ($params['guide_count'] ?? 2)));
        $sharedStart = $this->normalizeTimeString($params['guide_start'] ?? '11:00') ?? '11:00';

        $guideStarts = [];
        if (! empty($params['guide_starts']) && is_array($params['guide_starts'])) {
            foreach ($params['guide_starts'] as $start) {
                $normalized = $this->normalizeTimeString((string) $start);
                if ($normalized !== null) {
                    $guideStarts[] = $normalized;
                }
            }
        }

        while (count($guideStarts) < $guideCount) {
            $guideStarts[] = $sharedStart;
        }

        $guideStarts = array_slice($guideStarts, 0, $guideCount);

        $ferry = (int) ($params['ferry_adjustment'] ?? 0);
        if (! in_array($ferry, [-10, 0, 10], true)) {
            $ferry = 0;
        }

        $interval = (int) ($params['interval'] ?? 60);
        if (! in_array($interval, [15, 30, 60], true)) {
            $interval = 60;
        }

        $guideReduceAt = $this->normalizeTimeString($params['guide_reduce_at'] ?? null);
        $guideCountAfter = max(0, min($guideCount, (int) ($params['guide_count_after'] ?? max(1, $guideCount - 1))));

        return [
            'guide_count' => $guideCount,
            'guide_starts' => $guideStarts,
            'guide_reduce_at' => $guideReduceAt,
            'guide_count_after' => $guideCountAfter,
            'first_tour' => $this->normalizeTimeString($params['first_tour'] ?? '11:00') ?? '11:00',
            'last_tour' => $this->normalizeTimeString($params['last_tour'] ?? '16:00') ?? '16:00',
            'ferry_adjustment' => $ferry,
            'interval' => $interval,
            'max_wait_minutes' => max(0, min(180, (int) ($params['max_wait_minutes'] ?? 45))),
            'duration_minutes' => max(45, min(120, (int) ($params['duration_minutes'] ?? 75))),
            'buffer_minutes' => max(0, min(60, (int) ($params['buffer_minutes'] ?? 20))),
            'capacity' => max(1, min(50, (int) ($params['capacity'] ?? 27))),
        ];
    }

    /**
     * @return Collection<int, Booking>
     */
    private function sameDayBookings(Carbon $day): Collection
    {
        $date = $day->toDateString();

        return Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('created_at', $date)
            ->whereHas('tour', fn ($query) => $query->whereDate('tour_date', $date))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array{bookings: int, people: int}
     */
    private function advanceBookingsSummary(Carbon $day): array
    {
        $date = $day->toDateString();

        $bookings = Booking::query()
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->whereHas('tour', fn ($query) => $query->whereDate('tour_date', $date))
            ->whereDate('created_at', '<', $date)
            ->get();

        return [
            'bookings' => $bookings->count(),
            'people' => (int) $bookings->sum(fn (Booking $booking) => $this->bookingPeople($booking)),
        ];
    }

    /**
     * @return array{tours: int, people: int}
     */
    private function actualToursSummary(Carbon $day): array
    {
        $date = $day->toDateString();

        $tours = Tour::query()
            ->whereDate('tour_date', $date)
            ->with(['bookings' => function ($query) {
                $query->where('is_waitlist', false)->whereNotIn('status', ['cancelled']);
            }])
            ->get();

        $people = 0;
        foreach ($tours as $tour) {
            $people += (int) $tour->bookings->sum(fn (Booking $booking) => $this->bookingPeople($booking));
        }

        return [
            'tours' => $tours->count(),
            'people' => $people,
        ];
    }

    private function bookingPeople(Booking $booking): int
    {
        $total = (int) ($booking->total_count ?? 0);
        if ($total > 0) {
            return $total;
        }

        return max(0,
            (int) ($booking->men_count ?? 0)
            + (int) ($booking->women_count ?? 0)
            + (int) ($booking->youth_count ?? 0)
            + (int) ($booking->child_count ?? 0)
            + (int) ($booking->unspecified_count ?? 0)
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<Carbon>
     */
    private function candidateStarts(Carbon $day, array $params): array
    {
        $first = Carbon::parse($day->toDateString().' '.$params['first_tour']);
        $last = Carbon::parse($day->toDateString().' '.$params['last_tour']);

        if ($first->gt($last)) {
            return [];
        }

        $times = [];
        $cursor = $first->copy();
        while ($cursor->lte($last)) {
            $slot = $this->applyFerryToFullHour($cursor, $params['ferry_adjustment']);
            $times[$slot->format('H:i')] = $slot;
            $cursor->addMinutes($params['interval']);
        }

        ksort($times);

        return array_values($times);
    }

    private function applyFerryToFullHour(Carbon $slot, int $ferryAdjustment): Carbon
    {
        $adjusted = $slot->copy();

        if ($ferryAdjustment !== 0 && (int) $adjusted->format('i') === 0) {
            $adjusted->addMinutes($ferryAdjustment);
        }

        return $adjusted;
    }

    private function queueWaitMinutes(Carbon $createdAt, Carbon $tourStart, Carbon $firstSlot): int
    {
        $queueFrom = $createdAt->greaterThan($firstSlot) ? $createdAt->copy() : $firstSlot->copy();

        return max(0, (int) $queueFrom->diffInMinutes($tourStart, false));
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<array{index: int, available_at: Carbon}>
     */
    private function buildGuides(Carbon $day, array $params): array
    {
        $guides = [];

        foreach ($params['guide_starts'] as $index => $start) {
            $guides[] = [
                'index' => $index,
                'available_at' => Carbon::parse($day->toDateString().' '.$start),
            ];
        }

        return $guides;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon, guide_index: int, people: int, capacity: int, bookings: int, is_extra_15: bool, max_guest_wait: int, people_over_max_wait: int, exceeds_max_wait: bool}>  $tours
     */
    private function findOpenTourWithSpace(array $tours, Carbon $createdAt, int $capacity): ?int
    {
        $bestIndex = null;
        $bestStart = null;

        foreach ($tours as $index => $tour) {
            if ($tour['people'] >= $capacity) {
                continue;
            }

            if ($tour['start']->lt($createdAt)) {
                continue;
            }

            if ($bestStart === null || $tour['start']->lt($bestStart)) {
                $bestStart = $tour['start'];
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon, guide_index: int, people: int, capacity: int, bookings: int, is_extra_15: bool, max_guest_wait: int, people_over_max_wait: int, exceeds_max_wait: bool}>  $tours
     * @param  list<array{index: int, available_at: Carbon}>  $guides
     * @param  list<Carbon>  $slots
     * @param  array<string, mixed>  $params
     */
    private function tryOpenTour(
        array &$tours,
        array &$guides,
        array $slots,
        Carbon $createdAt,
        Carbon $firstSlot,
        Carbon $lastSlot,
        array $params,
    ): bool {
        $openStarts = [];
        foreach ($tours as $tour) {
            $openStarts[$tour['start']->format('H:i')] = true;
        }

        $baseSlot = $this->nextFeasibleSlot($slots, $guides, $openStarts, $createdAt, $params);
        $waitToBase = $baseSlot === null
            ? PHP_INT_MAX
            : $this->queueWaitMinutes($createdAt, $baseSlot, $firstSlot);

        $chosen = $baseSlot;
        $isExtra15 = false;

        if (
            $params['interval'] !== 15
            && $waitToBase > $params['max_wait_minutes']
        ) {
            $extra = $this->findExtra15Slot(
                $guides,
                $openStarts,
                $createdAt,
                $firstSlot,
                $lastSlot,
                $baseSlot,
                $params,
            );

            if ($extra !== null) {
                $chosen = $extra;
                $isExtra15 = true;
            }
        }

        if ($chosen === null) {
            return false;
        }

        $guideIndex = $this->pickGuide($guides, $chosen, $params);
        if ($guideIndex === null) {
            return false;
        }

        $tours[] = [
            'start' => $chosen->copy(),
            'end' => $chosen->copy()->addMinutes($params['duration_minutes']),
            'guide_index' => $guideIndex,
            'people' => 0,
            'capacity' => $params['capacity'],
            'bookings' => 0,
            'is_extra_15' => $isExtra15,
            'max_guest_wait' => 0,
            'people_over_max_wait' => 0,
            'exceeds_max_wait' => false,
        ];

        $guides[$guideIndex]['available_at'] = $chosen->copy()
            ->addMinutes($params['duration_minutes'] + $params['buffer_minutes']);

        return true;
    }

    /**
     * @param  list<Carbon>  $slots
     * @param  list<array{index: int, available_at: Carbon}>  $guides
     * @param  array<string, bool>  $openStarts
     * @param  array<string, mixed>  $params
     */
    private function nextFeasibleSlot(array $slots, array $guides, array $openStarts, Carbon $createdAt, array $params): ?Carbon
    {
        foreach ($slots as $slot) {
            if ($slot->lt($createdAt)) {
                continue;
            }

            if (isset($openStarts[$slot->format('H:i')])) {
                continue;
            }

            if ($this->pickGuide($guides, $slot, $params) === null) {
                continue;
            }

            return $slot->copy();
        }

        return null;
    }

    /**
     * @param  list<array{index: int, available_at: Carbon}>  $guides
     * @param  array<string, bool>  $openStarts
     * @param  array<string, mixed>  $params
     */
    private function findExtra15Slot(
        array $guides,
        array $openStarts,
        Carbon $createdAt,
        Carbon $firstSlot,
        Carbon $lastSlot,
        ?Carbon $baseSlot,
        array $params,
    ): ?Carbon {
        $queueFrom = $createdAt->greaterThan($firstSlot) ? $createdAt->copy() : $firstSlot->copy();
        $cursor = $this->ceilToQuarterHour($queueFrom);
        $limit = $baseSlot?->copy() ?? $lastSlot->copy();

        $best = null;
        $bestWait = null;

        while ($cursor->lt($limit)) {
            $slot = $this->applyFerryToFullHour($cursor, $params['ferry_adjustment']);

            if (
                $slot->gte($createdAt)
                && $slot->lte($lastSlot)
                && $slot->lt($limit)
                && ! isset($openStarts[$slot->format('H:i')])
                && $this->pickGuide($guides, $slot, $params) !== null
            ) {
                $wait = $this->queueWaitMinutes($createdAt, $slot, $firstSlot);

                if ($wait <= $params['max_wait_minutes'] && ($bestWait === null || $wait < $bestWait || ($wait === $bestWait && $slot->lt($best)))) {
                    $best = $slot->copy();
                    $bestWait = $wait;
                }
            }

            $cursor->addMinutes(15);
        }

        return $best;
    }

    private function ceilToQuarterHour(Carbon $time): Carbon
    {
        $t = $time->copy()->second(0)->microsecond(0);

        if ($time->second > 0 || $time->micro > 0) {
            $t->addMinute();
        }

        $mod = (int) $t->format('i') % 15;
        if ($mod !== 0) {
            $t->addMinutes(15 - $mod);
        }

        return $t;
    }

    /**
     * @param  list<array{index: int, available_at: Carbon}>  $guides
     * @param  array<string, mixed>  $params
     */
    private function pickGuide(array $guides, Carbon $slot, array $params): ?int
    {
        $eligibleCount = count($guides);

        if (is_string($params['guide_reduce_at'] ?? null) && $params['guide_reduce_at'] !== '') {
            $reduceAt = Carbon::parse($slot->toDateString().' '.$params['guide_reduce_at']);
            if ($slot->gte($reduceAt)) {
                $eligibleCount = (int) ($params['guide_count_after'] ?? 0);
            }
        }

        $bestIndex = null;
        $bestAvailable = null;

        foreach ($guides as $index => $guide) {
            if ($index >= $eligibleCount) {
                continue;
            }

            if ($guide['available_at']->gt($slot)) {
                continue;
            }

            if ($bestAvailable === null || $guide['available_at']->lt($bestAvailable)) {
                $bestAvailable = $guide['available_at'];
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon, guide_index: int, people: int, capacity: int, bookings: int, is_extra_15: bool, max_guest_wait: int, people_over_max_wait: int, exceeds_max_wait: bool}>  $tours
     * @param  array<string, mixed>  $params
     * @return list<array{guide_index: int, work_start: string, tours: int, people: int, last_tour_end: ?string, free_for_restaurant_from: ?string, available_after_reduce: bool}>
     */
    private function buildGuideSummaries(array $tours, array $params): array
    {
        $reduceAt = is_string($params['guide_reduce_at'] ?? null) ? $params['guide_reduce_at'] : null;
        $countAfter = (int) ($params['guide_count_after'] ?? count($params['guide_starts']));

        $summaries = [];

        foreach ($params['guide_starts'] as $index => $workStart) {
            $guideTours = array_values(array_filter(
                $tours,
                fn (array $tour) => $tour['guide_index'] === $index
            ));

            $lastEnd = null;
            $people = 0;

            foreach ($guideTours as $tour) {
                $people += $tour['people'];
                if ($lastEnd === null || $tour['end']->gt($lastEnd)) {
                    $lastEnd = $tour['end']->copy();
                }
            }

            $summaries[] = [
                'guide_index' => $index + 1,
                'work_start' => $workStart,
                'tours' => count($guideTours),
                'people' => $people,
                'last_tour_end' => $lastEnd?->format('H:i'),
                'free_for_restaurant_from' => $lastEnd?->format('H:i'),
                'available_after_reduce' => $reduceAt === null || $index < $countAfter,
            ];
        }

        return $summaries;
    }

    private function normalizeTimeString(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $raw) === 1) {
            [$h, $m] = array_map('intval', explode(':', $raw));

            if ($h >= 0 && $h <= 23 && $m >= 0 && $m <= 59) {
                return sprintf('%02d:%02d', $h, $m);
            }
        }

        return null;
    }

    /**
     * @param  list<int>  $values
     */
    private function percentile(array $values, int $percentile): ?float
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $count = count($values);
        $rank = ($percentile / 100) * ($count - 1);
        $low = (int) floor($rank);
        $high = (int) ceil($rank);

        if ($low === $high) {
            return round((float) $values[$low], 1);
        }

        $weight = $rank - $low;

        return round($values[$low] * (1 - $weight) + $values[$high] * $weight, 1);
    }
}
