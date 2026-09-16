<?php

namespace App\Services;

use App\Models\Tour;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class TourFerryAdjustmentService
{
    public function __construct(
        private TourDurationSettingsService $tourDurationSettingsService,
        private TourAutoCompleteService $tourAutoCompleteService,
    ) {}

    /**
     * @return Collection<int, Tour>
     */
    public function toursForDate(string $date): Collection
    {
        return Tour::query()
            ->with([
                'guide',
                'tourType',
                'bookings' => fn ($query) => $query->with('languages'),
            ])
            ->withSum([
                'bookings as booked_people_count' => fn ($query) => $query
                    ->where('is_waitlist', false)
                    ->whereNotIn('status', ['cancelled']),
            ], 'total_count')
            ->whereDate('tour_date', $date)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();
    }

    public function canAdjust(Tour $tour): bool
    {
        return $tour->status === 'planned' && ! empty($tour->start_time);
    }

    public function adjust(Tour $tour, int $minutes, ?int $actorId = null): Tour
    {
        $this->ensureAdjustable($tour);

        if ($minutes === 0) {
            throw new InvalidArgumentException('Minuter måste vara skilda från noll.');
        }

        $old = [
            'start_time' => $tour->start_time,
            'end_time' => $tour->end_time,
            'original_start_time' => $tour->original_start_time,
            'original_end_time' => $tour->original_end_time,
        ];

        if ($tour->original_start_time === null) {
            $tour->original_start_time = $tour->start_time;
            $tour->original_end_time = $this->resolvePlannedEndTime($tour, (string) $tour->start_time);
        }

        $newStart = $this->shiftTime((string) $tour->start_time, $minutes);
        $newEnd = $this->shiftPlannedEndTime($tour, $newStart, $minutes);
        $title = $this->syncBatchGeneratedTitle($tour, $newStart);
        $fill = [
            'start_time' => $newStart,
            'end_time' => $newEnd,
            'ferry_adjusted_at' => now(),
            'updated_by' => $actorId,
        ];

        if ($this->shouldSyncBaselineEndTime($tour)) {
            $fill['baseline_end_time'] = $this->tourAutoCompleteService->captureBaselineEndTime($newEnd);
        }

        $tour->fill($fill);

        if ($title !== null) {
            $tour->title = $title;
        }

        $tour->save();

        LogService::log(
            'tour',
            $tour->id,
            'ferry_adjusted',
            $old,
            [
                'start_time' => $tour->start_time,
                'end_time' => $tour->end_time,
                'minutes' => $minutes,
            ],
            sprintf(
                'Färjekorrigering: %s–%s → %s–%s (%+d min)',
                $this->formatTimeForLog($old['start_time']),
                $this->formatTimeForLog($old['end_time']),
                $this->formatTimeForLog($tour->start_time),
                $this->formatTimeForLog($tour->end_time),
                $minutes
            )
        );

        return $tour->fresh();
    }

    public function reset(Tour $tour, ?int $actorId = null): Tour
    {
        if ($tour->original_start_time === null) {
            throw new InvalidArgumentException('Turen har ingen sparad ursprungstid att återställa.');
        }

        $old = [
            'start_time' => $tour->start_time,
            'end_time' => $tour->end_time,
            'original_start_time' => $tour->original_start_time,
            'original_end_time' => $tour->original_end_time,
        ];

        $title = $this->syncBatchGeneratedTitle($tour, (string) $tour->original_start_time);
        $fill = [
            'start_time' => $tour->original_start_time,
            'end_time' => $tour->original_end_time,
            'original_start_time' => null,
            'original_end_time' => null,
            'ferry_adjusted_at' => null,
            'updated_by' => $actorId,
        ];

        if ($this->shouldSyncBaselineEndTime($tour)) {
            $fill['baseline_end_time'] = $this->tourAutoCompleteService->captureBaselineEndTime(
                (string) $tour->original_end_time
            );
        }

        $tour->fill($fill);

        if ($title !== null) {
            $tour->title = $title;
        }

        $tour->save();

        LogService::log(
            'tour',
            $tour->id,
            'ferry_reset',
            $old,
            [
                'start_time' => $tour->start_time,
                'end_time' => $tour->end_time,
            ],
            sprintf(
                'Färjekorrigering återställd: %s–%s → %s–%s',
                $this->formatTimeForLog($old['start_time']),
                $this->formatTimeForLog($old['end_time']),
                $this->formatTimeForLog($tour->start_time),
                $this->formatTimeForLog($tour->end_time),
            )
        );

        return $tour->fresh();
    }

    private function ensureAdjustable(Tour $tour): void
    {
        if ($tour->status !== 'planned') {
            throw new InvalidArgumentException('Endast planerade turer kan färjekorrigeras.');
        }

        if (empty($tour->start_time)) {
            throw new InvalidArgumentException('Turen saknar starttid.');
        }
    }

    private function shiftPlannedEndTime(Tour $tour, string $newStart, int $minutes): string
    {
        if (! empty($tour->end_time)) {
            return $this->shiftTime((string) $tour->end_time, $minutes);
        }

        return $this->tourDurationSettingsService->endTimeFromStartTime(
            $newStart,
            $tour->tour_type_id ? (int) $tour->tour_type_id : null
        );
    }

    private function resolvePlannedEndTime(Tour $tour, string $startTime): string
    {
        if (! empty($tour->end_time)) {
            return $this->normalizeTimeString((string) $tour->end_time);
        }

        return $this->tourDurationSettingsService->endTimeFromStartTime(
            $startTime,
            $tour->tour_type_id ? (int) $tour->tour_type_id : null
        );
    }

    private function shouldSyncBaselineEndTime(Tour $tour): bool
    {
        return $tour->baseline_end_time !== null && $tour->baseline_end_time !== '';
    }

    private function normalizeTimeString(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : substr($time, 0, 8);
    }

    private function shiftTime(string $time, int $minutes): string
    {
        $normalized = strlen($time) === 5 ? $time.':00' : substr($time, 0, 8);

        return Carbon::createFromFormat('H:i:s', $normalized)
            ->addMinutes($minutes)
            ->format('H:i:s');
    }

    private function formatTimeForLog(?string $time): string
    {
        if ($time === null || $time === '') {
            return '-';
        }

        return substr($time, 0, 5);
    }

    private function syncBatchGeneratedTitle(Tour $tour, string $startTime): ?string
    {
        $title = trim((string) $tour->title);

        if ($title === '') {
            return null;
        }

        $prefix = $this->batchTitlePrefix($tour);

        if ($prefix === null) {
            return null;
        }

        $date = $tour->tour_date?->format('Y-m-d') ?? (string) $tour->tour_date;

        if ($date === '') {
            return null;
        }

        $pattern = '/^'.preg_quote($prefix, '/').'\s+'.preg_quote($date, '/').'\s+\d{1,2}:\d{2}$/u';

        if (! preg_match($pattern, $title)) {
            return null;
        }

        return trim($prefix.' '.$date.' '.$this->formatTimeForLog($startTime));
    }

    private function batchTitlePrefix(Tour $tour): ?string
    {
        if ($tour->tour_type_id !== null) {
            $tourType = $tour->relationLoaded('tourType')
                ? $tour->tourType
                : $tour->tourType()->first(['id', 'name']);

            return $tourType !== null ? trim($tourType->name) : null;
        }

        return 'Tur';
    }
}
