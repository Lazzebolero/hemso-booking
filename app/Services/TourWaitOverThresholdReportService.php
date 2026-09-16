<?php

namespace App\Services;

use App\Models\Tour;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TourWaitOverThresholdReportService
{
    public function __construct(
        private TourWaitTimeService $waitTime,
    ) {}

    /**
     * Listar guidade turer i perioden där max köväntan överstiger tröskeln.
     *
     * @return array{
     *     warning_minutes: int,
     *     summary: array{
     *         over_threshold_tours: int,
     *         tours_with_queue_samples: int,
     *         queue_bookings: int,
     *         max_wait_minutes: int|null,
     *         avg_max_wait_minutes: float|null
     *     },
     *     tours: list<array{
     *         id: int,
     *         date: string,
     *         start_time: string,
     *         title: string,
     *         tour_type: string|null,
     *         guide_name: string|null,
     *         wait_avg_minutes: float|null,
     *         wait_max_minutes: int|null,
     *         wait_samples: int,
     *         wait_chosen_avg_minutes: float|null,
     *         wait_chosen_max_minutes: int|null,
     *         wait_chosen_samples: int
     *     }>
     * }
     */
    public function build(Carbon $from, Carbon $to, ?int $tourTypeId = null): array
    {
        $warningMinutes = $this->waitTime->warningMinutes();
        $tours = $this->loadWaitRelevantTours($from, $to, $tourTypeId);

        $this->waitTime->attachGroupedByDate($tours, $warningMinutes);

        $withQueueSamples = $tours->filter(
            fn (Tour $tour) => (int) ($tour->wait_samples ?? 0) > 0
        );

        $flagged = $tours
            ->filter(fn (Tour $tour) => (bool) ($tour->wait_warn ?? false))
            ->sortBy([
                fn (Tour $tour) => $this->tourDateKey($tour),
                fn (Tour $tour) => (string) ($tour->start_time ?? ''),
            ])
            ->values();

        $maxWaits = $flagged
            ->map(fn (Tour $tour) => $tour->wait_max_minutes)
            ->filter(fn ($minutes) => $minutes !== null)
            ->map(fn ($minutes) => (int) $minutes)
            ->values();

        return [
            'warning_minutes' => $warningMinutes,
            'summary' => [
                'over_threshold_tours' => $flagged->count(),
                'tours_with_queue_samples' => $withQueueSamples->count(),
                'queue_bookings' => (int) $flagged->sum(fn (Tour $tour) => (int) ($tour->wait_samples ?? 0)),
                'max_wait_minutes' => $maxWaits->isEmpty() ? null : $maxWaits->max(),
                'avg_max_wait_minutes' => $maxWaits->isEmpty()
                    ? null
                    : round($maxWaits->avg(), 1),
            ],
            'tours' => $flagged->map(fn (Tour $tour) => $this->mapTourRow($tour))->all(),
        ];
    }

    /**
     * @return Collection<int, Tour>
     */
    private function loadWaitRelevantTours(Carbon $from, Carbon $to, ?int $tourTypeId): Collection
    {
        $query = Tour::query()
            ->with(['tourType', 'guide'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('tour_date', '>=', $from->toDateString())
            ->whereDate('tour_date', '<=', $to->toDateString())
            ->orderBy('tour_date')
            ->orderBy('start_time');

        if ($tourTypeId !== null) {
            $query->where('tour_type_id', $tourTypeId);
        }

        return $query
            ->get()
            ->filter(fn (Tour $tour) => $this->waitTime->isWaitRelevantTour($tour))
            ->values();
    }

    /**
     * @return array{
     *     id: int,
     *     date: string,
     *     start_time: string,
     *     title: string,
     *     tour_type: string|null,
     *     guide_name: string|null,
     *     wait_avg_minutes: float|null,
     *     wait_max_minutes: int|null,
     *     wait_samples: int,
     *     wait_chosen_avg_minutes: float|null,
     *     wait_chosen_max_minutes: int|null,
     *     wait_chosen_samples: int
     * }
     */
    private function mapTourRow(Tour $tour): array
    {
        $start = (string) ($tour->start_time ?? '');

        return [
            'id' => (int) $tour->id,
            'date' => $this->tourDateKey($tour),
            'start_time' => strlen($start) >= 5 ? substr($start, 0, 5) : $start,
            'title' => (string) ($tour->title ?? ''),
            'tour_type' => $tour->tourType?->name,
            'guide_name' => $tour->guide?->name,
            'wait_avg_minutes' => $tour->wait_avg_minutes !== null ? (float) $tour->wait_avg_minutes : null,
            'wait_max_minutes' => $tour->wait_max_minutes !== null ? (int) $tour->wait_max_minutes : null,
            'wait_samples' => (int) ($tour->wait_samples ?? 0),
            'wait_chosen_avg_minutes' => $tour->wait_chosen_avg_minutes !== null
                ? (float) $tour->wait_chosen_avg_minutes
                : null,
            'wait_chosen_max_minutes' => $tour->wait_chosen_max_minutes !== null
                ? (int) $tour->wait_chosen_max_minutes
                : null,
            'wait_chosen_samples' => (int) ($tour->wait_chosen_samples ?? 0),
        ];
    }

    private function tourDateKey(Tour $tour): string
    {
        if (empty($tour->tour_date)) {
            return '';
        }

        return $tour->tour_date instanceof Carbon
            ? $tour->tour_date->toDateString()
            : Carbon::parse($tour->tour_date)->toDateString();
    }
}
