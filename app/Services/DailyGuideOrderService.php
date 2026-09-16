<?php

namespace App\Services;

use App\Models\DailyGuideOrder;
use App\Models\Tour;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DailyGuideOrderService
{
    /**
     * @return Collection<int, DailyGuideOrder>
     */
    public function orderedForDate(string $date): Collection
    {
        return DailyGuideOrder::query()
            ->with(['user.guideLanguages'])
            ->forDate($date)
            ->ordered()
            ->get();
    }

    public function ensureInitialized(string $date, ?int $actorId = null): bool
    {
        if (DailyGuideOrder::query()->forDate($date)->exists()) {
            return false;
        }

        $this->syncFromSchedule($date, $actorId);

        return true;
    }

    public function syncFromSchedule(string $date, ?int $actorId = null): int
    {
        $scheduledGuides = $this->scheduledGuidesForDate($date);

        return DB::transaction(function () use ($date, $scheduledGuides, $actorId) {
            $manualOrders = DailyGuideOrder::query()
                ->forDate($date)
                ->where('source', DailyGuideOrder::SOURCE_MANUAL)
                ->ordered()
                ->get();

            $manualUserIds = $manualOrders
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            DailyGuideOrder::query()->forDate($date)->delete();

            $sortOrder = 1;

            foreach ($scheduledGuides as $guideShift) {
                if (in_array((int) $guideShift->user_id, $manualUserIds, true)) {
                    continue;
                }

                DailyGuideOrder::query()->create([
                    'guide_date' => $date,
                    'user_id' => $guideShift->user_id,
                    'sort_order' => $sortOrder,
                    'source' => DailyGuideOrder::SOURCE_SCHEDULE,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $sortOrder++;
            }

            foreach ($manualOrders as $manualOrder) {
                DailyGuideOrder::query()->create([
                    'guide_date' => $date,
                    'user_id' => $manualOrder->user_id,
                    'sort_order' => $sortOrder,
                    'source' => DailyGuideOrder::SOURCE_MANUAL,
                    'created_by' => $manualOrder->created_by ?? $actorId,
                    'updated_by' => $actorId,
                ]);

                $sortOrder++;
            }

            return DailyGuideOrder::query()->forDate($date)->count();
        });
    }

    public function addManualGuide(string $date, int $userId, ?int $actorId = null): DailyGuideOrder
    {
        $this->assertGuideUser($userId);

        if (DailyGuideOrder::query()->forDate($date)->where('user_id', $userId)->exists()) {
            throw new InvalidArgumentException('Guiden finns redan i dagens lista.');
        }

        $nextSortOrder = (int) DailyGuideOrder::query()
            ->forDate($date)
            ->max('sort_order') + 1;

        return DailyGuideOrder::query()->create([
            'guide_date' => $date,
            'user_id' => $userId,
            'sort_order' => max(1, $nextSortOrder),
            'source' => DailyGuideOrder::SOURCE_MANUAL,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    public function removeGuide(string $date, int $userId, ?int $actorId = null): void
    {
        DB::transaction(function () use ($date, $userId, $actorId) {
            DailyGuideOrder::query()
                ->forDate($date)
                ->where('user_id', $userId)
                ->delete();

            $this->renumberForDate($date, $actorId);
        });
    }

    /**
     * @return list<int>
     */
    public function guideIdsForDate(string $date, ?int $actorId = null): array
    {
        $this->ensureInitialized($date, $actorId);

        return $this->orderedForDate($date)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function guideIdAtRotationIndex(array $guideIds, int $index): ?int
    {
        if ($guideIds === []) {
            return null;
        }

        return $guideIds[$index % count($guideIds)];
    }

    public function rotationStartIndexForDate(string $date): int
    {
        return Tour::query()
            ->whereDate('tour_date', $date)
            ->where('status', '!=', 'cancelled')
            ->count();
    }

    /**
     * @return array{updated: int, skipped: int, tours: Collection<int, Tour>}
     */
    public function rippleGuidesAfterTour(Tour $tour, ?int $anchorGuideId, ?int $actorId = null): array
    {
        $date = $tour->tour_date?->toDateString() ?? (string) $tour->tour_date;

        $guideIds = $this->guideIdsForDate($date, $actorId);

        if ($guideIds === []) {
            return [
                'updated' => 0,
                'skipped' => 0,
                'tours' => collect(),
            ];
        }

        $tours = Tour::query()
            ->whereDate('tour_date', $date)
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        $currentIndex = $tours->search(fn (Tour $dayTour) => (int) $dayTour->id === (int) $tour->id);

        if ($currentIndex === false) {
            return [
                'updated' => 0,
                'skipped' => 0,
                'tours' => collect(),
            ];
        }

        if ($anchorGuideId === null) {
            $anchorIndex = $currentIndex % count($guideIds);
        } else {
            $anchorIndex = array_search($anchorGuideId, $guideIds, true);

            if ($anchorIndex === false) {
                return [
                    'updated' => 0,
                    'skipped' => 0,
                    'tours' => collect(),
                ];
            }
        }

        $updatedTours = collect();
        $updated = 0;
        $skipped = 0;

        for ($index = $currentIndex + 1; $index < $tours->count(); $index++) {
            $subsequentTour = $tours[$index];

            if (in_array($subsequentTour->status, ['started', 'completed'], true)) {
                $skipped++;

                continue;
            }

            $newGuideId = $this->guideIdAtRotationIndex(
                $guideIds,
                $anchorIndex + ($index - $currentIndex)
            );

            if ((int) $subsequentTour->guide_id === (int) $newGuideId) {
                continue;
            }

            $subsequentTour->update([
                'guide_id' => $newGuideId,
                'updated_by' => $actorId,
            ]);

            $updatedTours->push($subsequentTour->fresh());
            $updated++;
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'tours' => $updatedTours,
        ];
    }

    /**
     * @param  list<int>  $userIds
     */
    public function reorder(string $date, array $userIds, ?int $actorId = null): void
    {
        $existingUserIds = DailyGuideOrder::query()
            ->forDate($date)
            ->ordered()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $requestedUserIds = array_values(array_map('intval', $userIds));

        sort($existingUserIds);
        $sortedRequested = $requestedUserIds;
        sort($sortedRequested);

        if ($existingUserIds !== $sortedRequested) {
            throw new InvalidArgumentException('Ordningen måste innehålla exakt dagens guider.');
        }

        DB::transaction(function () use ($date, $requestedUserIds, $actorId) {
            $existingOrders = DailyGuideOrder::query()
                ->forDate($date)
                ->get()
                ->keyBy('user_id');

            DailyGuideOrder::query()->forDate($date)->delete();

            foreach ($requestedUserIds as $index => $userId) {
                $existingOrder = $existingOrders->get($userId);

                DailyGuideOrder::query()->create([
                    'guide_date' => $date,
                    'user_id' => $userId,
                    'sort_order' => $index + 1,
                    'source' => $existingOrder?->source ?? DailyGuideOrder::SOURCE_MANUAL,
                    'created_by' => $existingOrder?->created_by,
                    'updated_by' => $actorId,
                ]);
            }
        });
    }

    public function moveGuide(string $date, int $userId, string $direction, ?int $actorId = null): void
    {
        $orders = $this->orderedForDate($date);

        $currentIndex = $orders->search(fn (DailyGuideOrder $order) => (int) $order->user_id === $userId);

        if ($currentIndex === false) {
            throw new InvalidArgumentException('Guiden finns inte i dagens lista.');
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex < 0 || $targetIndex >= $orders->count()) {
            return;
        }

        $userIds = $orders->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        [$userIds[$currentIndex], $userIds[$targetIndex]] = [$userIds[$targetIndex], $userIds[$currentIndex]];

        $this->reorder($date, $userIds, $actorId);
    }

    /**
     * @return Collection<int, WorkShift>
     */
    public function shiftStartsForDate(string $date): Collection
    {
        return WorkShift::query()
            ->forDate($date)
            ->forRole(Roles::GUIDE)
            ->active()
            ->orderBy('start_time')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');
    }

    /**
     * @return Collection<int, User>
     */
    public function availableGuidesToAdd(string $date): Collection
    {
        $existingUserIds = DailyGuideOrder::query()
            ->forDate($date)
            ->pluck('user_id');

        return User::query()
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
            ->whereNotIn('id', $existingUserIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @return Collection<int, WorkShift>
     */
    private function scheduledGuidesForDate(string $date): Collection
    {
        return WorkShift::query()
            ->with('user')
            ->forDate($date)
            ->forRole(Roles::GUIDE)
            ->active()
            ->whereNotNull('start_time')
            ->orderBy('start_time')
            ->get()
            ->unique('user_id')
            ->values();
    }

    private function renumberForDate(string $date, ?int $actorId = null): void
    {
        $userIds = DailyGuideOrder::query()
            ->forDate($date)
            ->ordered()
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($userIds === []) {
            return;
        }

        $this->reorder($date, $userIds, $actorId);
    }

    private function assertGuideUser(int $userId): void
    {
        $isGuide = User::query()
            ->whereKey($userId)
            ->whereHas('roles', function ($query) {
                $query->where('slug', Roles::GUIDE);
            })
            ->exists();

        if (! $isGuide) {
            throw new InvalidArgumentException('Användaren är inte guide.');
        }
    }
}
