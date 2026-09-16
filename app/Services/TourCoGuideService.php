<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TourCoGuideService
{
    public const ROLE_ASSISTANT = 'assistant';

    public const ROLE_TRAINEE = 'trainee';

    /**
     * @return list<string>
     */
    public function traineeEligibleRoleSlugs(): array
    {
        return [
            Roles::ELEV,
            Roles::HOST,
            Roles::RESTAURANT,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function roleLabels(): array
    {
        return [
            self::ROLE_ASSISTANT => 'Assistent',
            self::ROLE_TRAINEE => 'Trainee',
        ];
    }

    /**
     * @param  list<int|string>  $assistantIds
     * @param  list<int|string>  $traineeIds
     * @param  array<int|string, mixed>  $notesByUserId
     */
    public function sync(Tour $tour, array $assistantIds, array $traineeIds, array $notesByUserId = []): void
    {
        $leadId = $tour->guide_id ? (int) $tour->guide_id : null;
        $assistantIds = $this->normalizeIds($assistantIds, $leadId);
        $traineeIds = $this->normalizeIds($traineeIds, $leadId);
        $notesByUserId = $this->normalizeNotes($notesByUserId);

        $sync = [];

        foreach ($assistantIds as $userId) {
            $sync[$userId] = [
                'role' => self::ROLE_ASSISTANT,
                'notes' => $notesByUserId[$userId] ?? null,
            ];
        }

        foreach ($traineeIds as $userId) {
            if (isset($sync[$userId])) {
                continue;
            }

            $sync[$userId] = [
                'role' => self::ROLE_TRAINEE,
                'notes' => $notesByUserId[$userId] ?? null,
            ];
        }

        $tour->coGuides()->sync($sync);
    }

    public function validateCoGuideSelection(Request $request, ?int $leadGuideId): void
    {
        $assistantIds = $this->normalizeIds($request->input('assistant_guide_ids', []));
        $traineeIds = $this->normalizeIds($request->input('trainee_guide_ids', []));

        $overlap = array_values(array_intersect($assistantIds, $traineeIds));

        if ($overlap !== []) {
            throw ValidationException::withMessages([
                'assistant_guide_ids' => 'Samma guide kan inte vara både assistent och trainee.',
            ]);
        }

        if ($leadGuideId !== null) {
            $includesLead = in_array($leadGuideId, array_merge($assistantIds, $traineeIds), true);

            if ($includesLead) {
                throw ValidationException::withMessages([
                    'assistant_guide_ids' => 'Huvudguiden kan inte läggas till som medguide.',
                ]);
            }
        }

        $allIds = array_merge($assistantIds, $traineeIds);

        if ($allIds === []) {
            return;
        }

        if ($assistantIds !== []) {
            $validAssistantCount = User::query()
                ->whereIn('id', $assistantIds)
                ->whereHas('roles', fn ($query) => $query->where('slug', Roles::GUIDE))
                ->count();

            if ($validAssistantCount !== count($assistantIds)) {
                throw ValidationException::withMessages([
                    'assistant_guide_ids' => 'Assistenter måste vara guider.',
                ]);
            }
        }

        if ($traineeIds !== []) {
            $validTraineeCount = $this->eligibleTraineeQuery(
                User::query()->whereIn('id', $traineeIds)
            )->count();

            if ($validTraineeCount !== count($traineeIds)) {
                throw ValidationException::withMessages([
                    'trainee_guide_ids' => 'Trainees måste vara elev, värd eller restaurang utan guide-roll.',
                ]);
            }
        }

        foreach ($this->normalizeNotes($request->input('co_guide_notes', [])) as $userId => $note) {
            if (! in_array($userId, $allIds, true)) {
                continue;
            }

            if (mb_strlen($note) > 255) {
                throw ValidationException::withMessages([
                    'co_guide_notes.'.$userId => 'Anteckningen får vara högst 255 tecken.',
                ]);
            }
        }
    }

    /**
     * @param  list<int|string>|null  $excludeUserId
     * @return list<int>
     */
    public function normalizeIds(array $ids, int|array|null $excludeUserId = null): array
    {
        $exclude = collect(is_array($excludeUserId) ? $excludeUserId : [$excludeUserId])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->reject(fn (int $id) => in_array($id, $exclude, true))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string, mixed>  $notesByUserId
     * @return array<int, string>
     */
    public function normalizeNotes(array $notesByUserId): array
    {
        return collect($notesByUserId)
            ->mapWithKeys(fn ($note, $userId) => [(int) $userId => $this->normalizeNote($note)])
            ->filter(fn (?string $note) => $note !== null)
            ->all();
    }

    public function normalizeNote(mixed $note): ?string
    {
        if (! is_string($note)) {
            return null;
        }

        $note = trim($note);

        return $note === '' ? null : $note;
    }

    public function coGuideSummary(Tour $tour): string
    {
        if (! Schema::hasTable('tour_guide')) {
            return '';
        }

        $tour->loadMissing('coGuides');

        if ($tour->coGuides->isEmpty()) {
            return '';
        }

        return $tour->coGuides
            ->map(fn (User $guide) => $this->formatCoGuideLabel($guide))
            ->implode(', ');
    }

    public function formatCoGuideLabel(User $guide): string
    {
        $roleLabel = $this->roleLabels()[$guide->pivot->role] ?? $guide->pivot->role;

        if ($guide->pivot->role === self::ROLE_TRAINEE) {
            $roleLabel = match (true) {
                $guide->isElev() => 'Elev',
                $guide->isHost() => 'Värd',
                $guide->isRestaurant() => 'Restaurang',
                default => $roleLabel,
            };
        }

        $label = $guide->name.' ('.$roleLabel.')';

        if (! empty($guide->pivot->notes)) {
            $label .= ' — '.$guide->pivot->notes;
        }

        return $label;
    }

    public function compactCoGuideSummary(Tour $tour): string
    {
        if (! Schema::hasTable('tour_guide')) {
            return '';
        }

        $tour->loadMissing('coGuides');

        return $tour->coGuides
            ->map(fn (User $guide) => $this->formatCoGuideLabel($guide))
            ->implode(', ');
    }

    public function decorateTourGuideDisplay(Tour $tour): Tour
    {
        $summary = $this->compactCoGuideSummary($tour);

        $tour->display_guide_name = $tour->guide?->name ?? 'Ej tilldelad';
        $tour->display_co_guide_summary = filled($summary) ? $summary : null;

        return $tour;
    }

    /**
     * @param  list<string>  $base
     * @return list<string>
     */
    public function tourDisplayRelations(array $base = ['guide', 'tourType', 'bookings.languages', 'coGuides']): array
    {
        if (! Schema::hasTable('tour_guide')) {
            $base = array_values(array_diff($base, ['coGuides']));
        }

        return $base;
    }

    /**
     * @return Collection<int, Tour>
     */
    public function dashboardToursForCoGuide(int $userId, ?CarbonInterface $at = null): Collection
    {
        if (! Schema::hasTable('tour_guide')) {
            return collect();
        }

        $at ??= now();
        $today = $at->toDateString();
        $roleLabels = $this->roleLabels();

        return Tour::query()
            ->with(['guide', 'tourType', 'coGuides'])
            ->whereHas('coGuides', fn ($query) => $query->where('users.id', $userId))
            ->where(function ($query) use ($today) {
                $query->where('status', 'started')
                    ->orWhere(function ($plannedQuery) use ($today) {
                        $plannedQuery->where('status', 'planned')
                            ->whereDate('tour_date', '>=', $today);
                    });
            })
            ->orderBy('tour_date')
            ->orderBy('start_time')
            ->get()
            ->map(function (Tour $tour) use ($userId, $roleLabels) {
                $assignment = $tour->coGuides->firstWhere('id', $userId);
                $role = $assignment?->pivot?->role;

                $tour->co_guide_role = $role;
                $tour->co_guide_role_label = $roleLabels[$role] ?? $role;
                $tour->co_guide_notes = $assignment?->pivot?->notes;

                return $tour;
            });
    }

    /**
     * @return Collection<int, User>
     */
    public function traineeCandidatesForDate(CarbonInterface|string|null $date = null): Collection
    {
        $dateString = $date ? (string) $date : null;

        return $this->eligibleTraineeQuery(User::query())
            ->with(['roles', 'workShifts' => function ($query) use ($dateString) {
                if ($dateString) {
                    $query->whereDate('shift_date', $dateString);
                }

                $query->whereIn('shift_role', $this->traineeEligibleRoleSlugs())
                    ->whereNotIn('status', ['cancelled'])
                    ->orderBy('start_time');
            }])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function eligibleTraineeQuery($query)
    {
        return $query
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->whereIn('slug', $this->traineeEligibleRoleSlugs()))
            ->whereDoesntHave('roles', fn ($roleQuery) => $roleQuery->where('slug', Roles::GUIDE));
    }
}
