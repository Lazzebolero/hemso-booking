<?php

namespace App\Services;

use App\Models\Tour;
use Carbon\CarbonInterface;

class GuideQuickTourGuardService
{
    public function __construct(
        private TourDurationSettingsService $durationSettings,
    ) {}

    public function canStartQuickTour(int $guideId, ?CarbonInterface $at = null): bool
    {
        return $this->blockingTour($guideId, $at) === null;
    }

    public function blockingTour(int $guideId, ?CarbonInterface $at = null): ?Tour
    {
        $at ??= now();
        $today = $at->toDateString();
        $nowTime = $at->format('H:i:s');

        $ongoingTour = $this->assignedTourQuery($guideId)
            ->where('status', 'started')
            ->orderByDesc('started_at')
            ->orderByDesc('start_time')
            ->first();

        if ($ongoingTour !== null) {
            return $ongoingTour;
        }

        $quickTourEndTime = $this->durationSettings->endTimeFromStart($at, $this->durationSettings->defaultTourTypeId());

        return $this->assignedTourQuery($guideId)
            ->where('status', 'planned')
            ->whereDate('tour_date', $today)
            ->where(function ($query) use ($nowTime, $quickTourEndTime) {
                $query->whereTime('start_time', '<=', $nowTime)
                    ->orWhere(function ($overlapQuery) use ($nowTime, $quickTourEndTime) {
                        $overlapQuery->where('start_time', '<', $quickTourEndTime)
                            ->whereRaw('COALESCE(end_time, start_time) > ?', [$nowTime]);
                    });
            })
            ->orderBy('start_time')
            ->first();
    }

    public function blockedMessage(Tour $tour, ?CarbonInterface $at = null, ?int $guideId = null): string
    {
        $at ??= now();
        $guideId ??= (int) auth()->id();
        $isLeadGuide = (int) $tour->guide_id === $guideId;
        $title = $tour->title ?: 'Tur';
        $start = $tour->start_time ? substr((string) $tour->start_time, 0, 5) : '--:--';

        if ($tour->status === 'started') {
            if ($isLeadGuide) {
                return "Du har redan en pågående tur ({$title}). Avsluta den innan du startar en snabbtur.";
            }

            return "Du är tillagd på en pågående tur ({$title}). Snabbtur kan inte startas.";
        }

        $nowTime = $at->format('H:i:s');
        $isDue = substr((string) $tour->start_time, 0, 8) <= $nowTime;

        if ($isLeadGuide && $isDue) {
            return "Du har en planerad tur ({$title} kl {$start}) som ska startas. Starta den i stället för en snabbtur.";
        }

        if ($isLeadGuide) {
            return "Du är inbokad på en tur ({$title} kl {$start}) som krockar med en snabbtur nu. Starta den planerade turen i stället.";
        }

        if ($isDue) {
            return "Du är tillagd på en tur ({$title} kl {$start}) som ska startas. Snabbtur kan inte startas.";
        }

        return "Du är tillagd på en tur ({$title} kl {$start}) som krockar med en snabbtur nu.";
    }

    /**
     * @return array{can_start: bool, message: ?string, tour_id: ?int}
     */
    public function assess(int $guideId, ?CarbonInterface $at = null): array
    {
        $blockingTour = $this->blockingTour($guideId, $at);

        if ($blockingTour === null) {
            return [
                'can_start' => true,
                'message' => null,
                'tour_id' => null,
            ];
        }

        return [
            'can_start' => false,
            'message' => $this->blockedMessage($blockingTour, $at, $guideId),
            'tour_id' => (int) $blockingTour->guide_id === $guideId ? $blockingTour->id : null,
        ];
    }

    private function assignedTourQuery(int $guideId)
    {
        return Tour::query()
            ->where(function ($query) use ($guideId) {
                $query->where('guide_id', $guideId)
                    ->orWhereHas('coGuides', fn ($coGuideQuery) => $coGuideQuery->where('users.id', $guideId));
            });
    }
}
