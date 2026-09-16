<?php

namespace App\Services;

use App\Models\TourType;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TourDurationSettingsService
{
    public function defaultCapacity(): int
    {
        return max(1, (int) setting('default_tour_capacity', 25));
    }

    public function defaultTourTypeId(): ?int
    {
        $defaultId = TourType::query()
            ->where('is_default', true)
            ->value('id');

        if ($defaultId) {
            return (int) $defaultId;
        }

        $firstId = TourType::query()->orderBy('sort_order')->orderBy('name')->value('id');

        return $firstId ? (int) $firstId : null;
    }

    public function durationMinutes(?int $tourTypeId = null): int
    {
        $resolvedTourTypeId = $tourTypeId ?? $this->defaultTourTypeId();

        if ($resolvedTourTypeId) {
            $tourTypeDuration = TourType::query()
                ->whereKey($resolvedTourTypeId)
                ->value('default_duration_minutes');

            if ($tourTypeDuration) {
                return max(1, (int) $tourTypeDuration);
            }
        }

        return 80;
    }

    public function endTimeFromStart(CarbonInterface $startAt, ?int $tourTypeId = null): string
    {
        return $startAt
            ->copy()
            ->addMinutes($this->durationMinutes($tourTypeId))
            ->format('H:i:s');
    }

    public function endTimeFromStartTime(string $startTime, ?int $tourTypeId = null): string
    {
        $normalizedStart = $this->normalizeTime($startTime);

        return Carbon::createFromFormat('H:i:s', $normalizedStart)
            ->addMinutes($this->durationMinutes($tourTypeId))
            ->format('H:i:s');
    }

    public function resolveCapacityForQuickTour(int $participantCount): int
    {
        return max($this->defaultCapacity(), $participantCount);
    }

    private function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time.':00' : $time;
    }
}
