<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Tour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TourWaitTimeService
{
    public const SETTING_WARNING_MINUTES = 'tour_wait_warning_minutes';

    public const DEFAULT_WARNING_MINUTES = 45;

    public const CLASS_QUEUE = 'queue';

    public const CLASS_CHOSEN = 'chosen';

    public function __construct(
        private TourBookingSequenceService $bookingSequence,
    ) {}

    public function warningMinutes(): int
    {
        return max(0, min(180, (int) setting(self::SETTING_WARNING_MINUTES, self::DEFAULT_WARNING_MINUTES)));
    }

    /**
     * @param  Collection<int, Tour>  $dayTours
     * @param  Collection<int, Booking>|null  $bookingsOnTour
     * @param  Collection<int, Booking>|null  $allDayBookingsSorted  Alla aktiva bokningar på dagens guidade turer (för beläggnings-replay).
     * @return array{
     *     avg: float|null,
     *     max: int|null,
     *     samples: int,
     *     warn: bool,
     *     chosen_avg: float|null,
     *     chosen_max: int|null,
     *     chosen_samples: int
     * }
     */
    public function summarizeForTour(
        Tour $tour,
        Collection $dayTours,
        ?int $warningMinutes = null,
        ?Collection $bookingsOnTour = null,
        ?Collection $allDayBookingsSorted = null,
    ): array {
        if (! $this->isWaitRelevantTour($tour)) {
            return $this->emptySummary();
        }

        $warningMinutes ??= $this->warningMinutes();
        $orderedDayTours = $this->orderedWaitRelevantDayTours($dayTours, $tour);
        $firstSlot = $this->firstTourStart($orderedDayTours, $tour);
        $tourStart = $this->tourStartAt($tour);
        $tourDate = $this->tourDateString($tour);

        if ($tourStart === null || $firstSlot === null || $tourDate === null || $orderedDayTours->isEmpty()) {
            return $this->emptySummary();
        }

        $bookingsOnTour ??= $this->bookingsForTour($tour);
        $allDayBookingsSorted ??= $this->sortBookings(
            $bookingsOnTour->values()
        );

        $queueWaits = [];
        $chosenWaits = [];

        foreach ($bookingsOnTour as $booking) {
            if (! $booking instanceof Booking) {
                continue;
            }

            if (! $this->isActiveBooking($booking)) {
                continue;
            }

            $createdAt = $this->bookingCreatedAt($booking);

            if ($createdAt->timezone(config('app.timezone'))->toDateString() !== $tourDate) {
                continue;
            }

            $classification = $this->classifySameDayBooking(
                $booking,
                $tour,
                $orderedDayTours,
                $allDayBookingsSorted,
                $firstSlot,
            );

            if ($classification['class'] === self::CLASS_CHOSEN) {
                $chosenWaits[] = $classification['wait_minutes'];
            } else {
                $queueWaits[] = $classification['wait_minutes'];
            }
        }

        if ($queueWaits === [] && $chosenWaits === []) {
            return $this->emptySummary();
        }

        $queueMax = $queueWaits === [] ? null : max($queueWaits);
        $chosenMax = $chosenWaits === [] ? null : max($chosenWaits);

        return [
            'avg' => $queueWaits === [] ? null : round(array_sum($queueWaits) / count($queueWaits), 1),
            'max' => $queueMax,
            'samples' => count($queueWaits),
            'warn' => $queueMax !== null && $queueMax > $warningMinutes,
            'chosen_avg' => $chosenWaits === [] ? null : round(array_sum($chosenWaits) / count($chosenWaits), 1),
            'chosen_max' => $chosenMax,
            'chosen_samples' => count($chosenWaits),
        ];
    }

    /**
     * @param  Collection<int, Tour>  $orderedDayTours
     * @param  Collection<int, Booking>  $allDayBookingsSorted
     * @return array{class: string, wait_minutes: int, earliest_tour_id: int|null}
     */
    public function classifySameDayBooking(
        Booking $booking,
        Tour $actualTour,
        Collection $orderedDayTours,
        Collection $allDayBookingsSorted,
        Carbon $firstSlot,
    ): array {
        $createdAt = $this->bookingCreatedAt($booking);
        $actualStart = $this->tourStartAt($actualTour);
        $waitMinutes = $actualStart === null
            ? 0
            : $this->queueWaitMinutes($createdAt, $actualStart, $firstSlot);

        $earliest = $this->earliestAvailableTour(
            $booking,
            $createdAt,
            $orderedDayTours,
            $allDayBookingsSorted,
        );

        if ($earliest === null) {
            return [
                'class' => self::CLASS_QUEUE,
                'wait_minutes' => $waitMinutes,
                'earliest_tour_id' => null,
            ];
        }

        if ((int) $earliest->id === (int) $actualTour->id) {
            return [
                'class' => self::CLASS_QUEUE,
                'wait_minutes' => $waitMinutes,
                'earliest_tour_id' => (int) $earliest->id,
            ];
        }

        $earliestStart = $this->tourStartAt($earliest);

        if ($actualStart !== null && $earliestStart !== null && $actualStart->gt($earliestStart)) {
            return [
                'class' => self::CLASS_CHOSEN,
                'wait_minutes' => $waitMinutes,
                'earliest_tour_id' => (int) $earliest->id,
            ];
        }

        return [
            'class' => self::CLASS_QUEUE,
            'wait_minutes' => $waitMinutes,
            'earliest_tour_id' => (int) $earliest->id,
        ];
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @param  Collection<int, Tour>  $dayTours
     * @return Collection<int, Tour>
     */
    public function attachToTours(Collection $tours, Collection $dayTours, ?int $warningMinutes = null): Collection
    {
        $warningMinutes ??= $this->warningMinutes();
        $orderedDayTours = $this->orderedWaitRelevantDayTours($dayTours);
        $bookingsByTourId = $this->loadActiveBookingsByTourId($orderedDayTours->pluck('id'));
        $allDayBookingsSorted = $this->flattenAndSortBookings($bookingsByTourId);

        return $tours->map(function (Tour $tour) use ($orderedDayTours, $warningMinutes, $bookingsByTourId, $allDayBookingsSorted) {
            return $this->applySummary(
                $tour,
                $this->summarizeForTour(
                    $tour,
                    $orderedDayTours,
                    $warningMinutes,
                    $bookingsByTourId->get((int) $tour->id, collect()),
                    $allDayBookingsSorted,
                )
            );
        });
    }

    /**
     * @param  Collection<int, Tour>  $tours
     * @return Collection<int, Tour>
     */
    public function attachGroupedByDate(Collection $tours, ?int $warningMinutes = null): Collection
    {
        if ($tours->isEmpty()) {
            return $tours;
        }

        $warningMinutes ??= $this->warningMinutes();

        $dates = $tours
            ->map(fn (Tour $tour) => $this->tourDateString($tour))
            ->filter()
            ->unique()
            ->values();

        $dayToursByDate = Tour::query()
            ->with('tourType')
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($dates) {
                foreach ($dates as $date) {
                    $query->orWhereDate('tour_date', $date);
                }
            })
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn (Tour $tour) => $this->tourDateString($tour));

        $waitRelevantTourIds = $dayToursByDate
            ->flatten(1)
            ->filter(fn (Tour $tour) => $this->isWaitRelevantTour($tour))
            ->pluck('id')
            ->filter()
            ->values();

        $bookingsByTourId = $this->loadActiveBookingsByTourId($waitRelevantTourIds);

        foreach ($tours as $tour) {
            $date = $this->tourDateString($tour);
            $dayTours = $this->orderedWaitRelevantDayTours($dayToursByDate->get($date, collect([$tour])));
            $dayTourIds = $dayTours->pluck('id')->map(fn ($id) => (int) $id)->all();
            $dayBookingsByTourId = $bookingsByTourId
                ->filter(fn ($bookings, $tourId) => in_array((int) $tourId, $dayTourIds, true));
            $allDayBookingsSorted = $this->flattenAndSortBookings($dayBookingsByTourId);

            $this->applySummary(
                $tour,
                $this->summarizeForTour(
                    $tour,
                    $dayTours,
                    $warningMinutes,
                    $bookingsByTourId->get((int) $tour->id, collect()),
                    $allDayBookingsSorted,
                )
            );
        }

        return $tours;
    }

    /**
     * @param  Collection<int, Tour>  $orderedDayTours
     * @param  Collection<int, Booking>  $allDayBookingsSorted
     */
    private function earliestAvailableTour(
        Booking $booking,
        Carbon $createdAt,
        Collection $orderedDayTours,
        Collection $allDayBookingsSorted,
    ): ?Tour {
        $people = $this->bookingPeople($booking);

        foreach ($orderedDayTours as $candidate) {
            if (! $candidate instanceof Tour) {
                continue;
            }

            if (($candidate->status ?? null) === 'cancelled') {
                continue;
            }

            if ((bool) ($candidate->closed_for_bookings ?? false)) {
                continue;
            }

            $start = $this->tourStartAt($candidate);

            if ($start === null || $start->lt($createdAt)) {
                continue;
            }

            $capacity = max(0, (int) ($candidate->max_participants ?? 0));
            $occupied = $this->occupiedSeatsBefore($candidate, $booking, $allDayBookingsSorted);

            if ($capacity - $occupied >= $people) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, Booking>  $allDayBookingsSorted
     */
    private function occupiedSeatsBefore(
        Tour $tour,
        Booking $booking,
        Collection $allDayBookingsSorted,
    ): int {
        $occupied = 0;

        foreach ($allDayBookingsSorted as $other) {
            if (! $other instanceof Booking) {
                continue;
            }

            if ((int) $other->tour_id !== (int) $tour->id) {
                continue;
            }

            if ((int) $other->id === (int) $booking->id) {
                continue;
            }

            if (! $this->isActiveBooking($other)) {
                continue;
            }

            if ($this->bookingIsBefore($other, $booking)) {
                $occupied += $this->bookingPeople($other);
            }
        }

        return $occupied;
    }

    private function bookingIsBefore(Booking $left, Booking $right): bool
    {
        $leftAt = $this->bookingCreatedAt($left);
        $rightAt = $this->bookingCreatedAt($right);

        if ($leftAt->lt($rightAt)) {
            return true;
        }

        if ($leftAt->gt($rightAt)) {
            return false;
        }

        return (int) $left->id < (int) $right->id;
    }

    private function bookingCreatedAt(Booking $booking): Carbon
    {
        return $booking->created_at instanceof Carbon
            ? $booking->created_at->copy()
            : Carbon::parse($booking->created_at);
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

    private function isActiveBooking(Booking $booking): bool
    {
        return ! $booking->is_waitlist && ! in_array($booking->status, ['cancelled'], true);
    }

    /**
     * @param  Collection<int, Tour>|null  $dayTours
     * @return Collection<int, Tour>
     */
    private function orderedWaitRelevantDayTours(?Collection $dayTours = null, ?Tour $fallbackTour = null): Collection
    {
        $tours = ($dayTours ?? collect())
            ->filter(fn ($tour) => $tour instanceof Tour && $this->isWaitRelevantTour($tour))
            ->values();

        if ($tours->isEmpty() && $fallbackTour !== null && $this->isWaitRelevantTour($fallbackTour)) {
            $tours = collect([$fallbackTour]);
        }

        return $tours
            ->sortBy(function (Tour $tour) {
                $start = $this->tourStartAt($tour);

                return $start?->timestamp ?? PHP_INT_MAX;
            })
            ->values();
    }

    public function isWaitRelevantTour(Tour $tour): bool
    {
        if (! $this->bookingSequence->supportsBookingSequenceFilter()) {
            return false;
        }

        if ($tour->exclude_from_booking_sequence) {
            return false;
        }

        if ($tour->tour_type_id === null) {
            return false;
        }

        $tour->loadMissing('tourType');

        return (bool) $tour->tourType?->include_in_booking_sequence;
    }

    /**
     * @param  Collection<int, Tour>|null  $dayTours
     * @return Collection<int, Tour>
     */
    private function orderedDayTours(?Collection $dayTours = null, ?Tour $fallbackTour = null): Collection
    {
        return $this->orderedWaitRelevantDayTours($dayTours, $fallbackTour);
    }

    /**
     * @param  Collection<int, int|string>  $tourIds
     * @return Collection<int|string, Collection<int, Booking>>
     */
    private function loadActiveBookingsByTourId(Collection $tourIds): Collection
    {
        $ids = $tourIds->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Booking::query()
            ->whereIn('tour_id', $ids)
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Booking $booking) => (int) $booking->tour_id);
    }

    /**
     * @param  Collection<int|string, Collection<int, Booking>>  $bookingsByTourId
     * @return Collection<int, Booking>
     */
    private function flattenAndSortBookings(Collection $bookingsByTourId): Collection
    {
        return $this->sortBookings($bookingsByTourId->flatten(1)->values());
    }

    /**
     * @param  Collection<int, Booking>  $bookings
     * @return Collection<int, Booking>
     */
    private function sortBookings(Collection $bookings): Collection
    {
        return $bookings
            ->sort(function (Booking $left, Booking $right) {
                $leftAt = $this->bookingCreatedAt($left);
                $rightAt = $this->bookingCreatedAt($right);

                if ($leftAt->eq($rightAt)) {
                    return (int) $left->id <=> (int) $right->id;
                }

                return $leftAt->lt($rightAt) ? -1 : 1;
            })
            ->values();
    }

    /**
     * @return Collection<int, Booking>
     */
    private function bookingsForTour(Tour $tour): Collection
    {
        if ($tour->relationLoaded('bookings')) {
            return $tour->bookings
                ->filter(fn ($booking) => $booking instanceof Booking && $this->isActiveBooking($booking))
                ->values();
        }

        return Booking::query()
            ->where('tour_id', $tour->id)
            ->where('is_waitlist', false)
            ->whereNotIn('status', ['cancelled'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array{
     *     avg: float|null,
     *     max: int|null,
     *     samples: int,
     *     warn: bool,
     *     chosen_avg: float|null,
     *     chosen_max: int|null,
     *     chosen_samples: int
     * }  $summary
     */
    private function applySummary(Tour $tour, array $summary): Tour
    {
        $tour->setAttribute('wait_avg_minutes', $summary['avg']);
        $tour->setAttribute('wait_max_minutes', $summary['max']);
        $tour->setAttribute('wait_samples', $summary['samples']);
        $tour->setAttribute('wait_warn', $summary['warn']);
        $tour->setAttribute('wait_chosen_avg_minutes', $summary['chosen_avg']);
        $tour->setAttribute('wait_chosen_max_minutes', $summary['chosen_max']);
        $tour->setAttribute('wait_chosen_samples', $summary['chosen_samples']);
        $tour->setAttribute('wait_computed', true);

        return $tour;
    }

    private function tourDateString(Tour $tour): ?string
    {
        if (empty($tour->tour_date)) {
            return null;
        }

        return $tour->tour_date instanceof Carbon
            ? $tour->tour_date->toDateString()
            : Carbon::parse($tour->tour_date)->toDateString();
    }

    /**
     * @param  Collection<int, Tour>  $dayTours
     */
    private function firstTourStart(Collection $dayTours, Tour $fallbackTour): ?Carbon
    {
        $starts = $dayTours
            ->map(fn (Tour $tour) => $this->tourStartAt($tour))
            ->filter()
            ->sortBy(fn (Carbon $start) => $start->timestamp)
            ->values();

        if ($starts->isNotEmpty()) {
            return $starts->first();
        }

        return $this->tourStartAt($fallbackTour);
    }

    private function tourStartAt(Tour $tour): ?Carbon
    {
        if (empty($tour->tour_date) || empty($tour->start_time)) {
            return null;
        }

        $rawTime = trim((string) $tour->start_time);

        if (! preg_match('/(\d{1,2}):(\d{2})(?::(\d{2}))?/', $rawTime, $matches)) {
            return null;
        }

        $time = sprintf(
            '%02d:%02d:%02d',
            (int) $matches[1],
            (int) $matches[2],
            isset($matches[3]) ? (int) $matches[3] : 0
        );

        try {
            $date = $tour->tour_date instanceof Carbon
                ? $tour->tour_date->copy()
                : Carbon::parse($tour->tour_date);

            return $date->setTimeFromTimeString($time);
        } catch (\Throwable) {
            return null;
        }
    }

    private function queueWaitMinutes(Carbon $createdAt, Carbon $tourStart, Carbon $firstSlot): int
    {
        $queueFrom = $createdAt->greaterThan($firstSlot) ? $createdAt->copy() : $firstSlot->copy();

        return max(0, (int) round($queueFrom->diffInMinutes($tourStart, false)));
    }

    /**
     * @return array{
     *     avg: float|null,
     *     max: int|null,
     *     samples: int,
     *     warn: bool,
     *     chosen_avg: float|null,
     *     chosen_max: int|null,
     *     chosen_samples: int
     * }
     */
    private function emptySummary(): array
    {
        return [
            'avg' => null,
            'max' => null,
            'samples' => 0,
            'warn' => false,
            'chosen_avg' => null,
            'chosen_max' => null,
            'chosen_samples' => 0,
        ];
    }
}
