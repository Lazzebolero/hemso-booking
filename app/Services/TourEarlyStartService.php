<?php

namespace App\Services;

use App\Models\Tour;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class TourEarlyStartService
{
    public const GRACE_MINUTES = 15;

    public function scheduledStartAt(Tour $tour): ?CarbonInterface
    {
        if ($tour->tour_date === null || blank($tour->start_time)) {
            return null;
        }

        return Carbon::parse($tour->tour_date->format('Y-m-d').' '.substr((string) $tour->start_time, 0, 8));
    }

    public function minutesUntilScheduledStart(Tour $tour, ?CarbonInterface $now = null): ?int
    {
        $scheduledStart = $this->scheduledStartAt($tour);

        if ($scheduledStart === null) {
            return null;
        }

        $now ??= now();

        return (int) $now->diffInMinutes($scheduledStart, false);
    }

    public function requiresConfirmation(Tour $tour, ?CarbonInterface $now = null): bool
    {
        $minutesUntilStart = $this->minutesUntilScheduledStart($tour, $now);

        if ($minutesUntilStart === null) {
            return false;
        }

        return $minutesUntilStart > self::GRACE_MINUTES;
    }

    public function confirmationMessage(Tour $tour, ?CarbonInterface $now = null): string
    {
        $tour->loadMissing('guide');

        $scheduledStart = $this->scheduledStartAt($tour);
        $minutesUntilStart = max(0, (int) ($this->minutesUntilScheduledStart($tour, $now) ?? 0));
        $startTime = $scheduledStart?->format('H:i') ?? '—';
        $guideName = $tour->guide?->name ?? 'Ej tilldelad';

        return sprintf(
            "Denna tur (%s) startar kl %s (om %s).\nHuvudguide: %s.\n\nVill du verkligen starta den nu?",
            $tour->title,
            $startTime,
            $this->humanizeRemainingMinutes($minutesUntilStart),
            $guideName,
        );
    }

    public function validationErrorMessage(Tour $tour, ?CarbonInterface $now = null): string
    {
        $scheduledStart = $this->scheduledStartAt($tour);
        $startTime = $scheduledStart?->format('H:i') ?? '—';

        return sprintf(
            'Turen "%s" startar först kl %s. Bekräfta tidig start innan du fortsätter.',
            $tour->title,
            $startTime,
        );
    }

    public function humanizeRemainingMinutes(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes.' minuter';
        }

        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;
        $hourLabel = $hours === 1 ? '1 timme' : $hours.' timmar';

        if ($rest === 0) {
            return $hourLabel;
        }

        return $hourLabel.' '.$rest.' minuter';
    }
}
