<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourType;
use Illuminate\Support\Carbon;

class TourAutoCompleteService
{
    public function autoCompleteDueTours(): int
    {
        $completed = 0;

        Tour::query()
            ->with('tourType')
            ->where('status', 'started')
            ->whereNotNull('started_at')
            ->whereNotNull('end_time')
            ->whereNull('auto_completed_at')
            ->orderBy('id')
            ->lazyById()
            ->each(function (Tour $tour) use (&$completed) {
                if (! $this->shouldAutoComplete($tour)) {
                    return;
                }

                $this->completeTour($tour);
                $completed++;
            });

        return $completed;
    }

    public function shouldAutoComplete(Tour $tour): bool
    {
        if ($tour->status !== 'started' || $tour->started_at === null) {
            return false;
        }

        if ($this->isExtended($tour)) {
            return false;
        }

        $tourType = $this->resolveTourType($tour);

        if ($tourType === null || ! $tourType->auto_complete_enabled) {
            return false;
        }

        $autoCompleteAt = $this->autoCompleteAt($tour, $tourType);

        if ($autoCompleteAt === null) {
            return false;
        }

        return now()->greaterThanOrEqualTo($autoCompleteAt);
    }

    public function autoCompleteAt(Tour $tour, ?TourType $tourType = null): ?Carbon
    {
        $plannedEnd = $this->plannedEndAt($tour);

        if ($plannedEnd === null) {
            return null;
        }

        $tourType ??= $this->resolveTourType($tour);

        if ($tourType === null || ! $tourType->auto_complete_enabled) {
            return null;
        }

        return $plannedEnd->copy()->addMinutes(max(0, (int) $tourType->auto_complete_grace_minutes));
    }

    public function isExtended(Tour $tour): bool
    {
        if ($tour->baseline_end_time === null) {
            return false;
        }

        return strcmp(
            $this->normalizeTime((string) $tour->end_time),
            $this->normalizeTime((string) $tour->baseline_end_time),
        ) > 0;
    }

    public function captureBaselineEndTime(?string $endTime): ?string
    {
        if ($endTime === null || $endTime === '') {
            return null;
        }

        return $this->normalizeTime($endTime);
    }

    private function completeTour(Tour $tour): void
    {
        $old = $tour->toArray();

        $tour->update([
            'status' => 'completed',
            'ended_at' => now(),
            'auto_completed_at' => now(),
        ]);

        if (class_exists(LogService::class)) {
            LogService::log(
                'tour',
                $tour->id,
                'auto_completed',
                $old,
                $tour->fresh()->toArray(),
                'Avslutade tur automatiskt'
            );
        }
    }

    private function resolveTourType(Tour $tour): ?TourType
    {
        if ($tour->relationLoaded('tourType') && $tour->tourType !== null) {
            return $tour->tourType;
        }

        if ($tour->tour_type_id === null) {
            return null;
        }

        return TourType::query()->find($tour->tour_type_id);
    }

    private function plannedEndAt(Tour $tour): ?Carbon
    {
        if ($tour->tour_date === null || empty($tour->end_time)) {
            return null;
        }

        return Carbon::parse($tour->tour_date->format('Y-m-d').' '.$this->normalizeTime((string) $tour->end_time));
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
