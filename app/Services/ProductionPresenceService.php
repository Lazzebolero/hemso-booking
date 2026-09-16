<?php

namespace App\Services;

use App\Models\Production;
use App\Models\ProductionPerson;
use App\Models\ProductionPresenceLog;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class ProductionPresenceService
{
    public function currentProduction(): ?Production
    {
        try {
            return Production::current();
        } catch (\Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * @return list<array{
     *     production: Production,
     *     insideCount: int,
     *     crewInside: Collection<int, ProductionPerson>,
     *     crewOutside: Collection<int, ProductionPerson>,
     *     participantsInside: Collection<int, ProductionPerson>,
     *     participantsOutside: Collection<int, ProductionPerson>,
     *     departedParticipants: Collection<int, ProductionPerson>
     * }>
     */
    public function adminPresenceOverview(): array
    {
        $productions = Production::query()
            ->currentPeriod()
            ->with(['people' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return $productions
            ->map(fn (Production $production) => $this->overviewFor($production))
            ->all();
    }

    /**
     * @return array{
     *     production: Production,
     *     insideCount: int,
     *     crewInside: Collection<int, ProductionPerson>,
     *     crewOutside: Collection<int, ProductionPerson>,
     *     participantsInside: Collection<int, ProductionPerson>,
     *     participantsOutside: Collection<int, ProductionPerson>,
     *     departedParticipants: Collection<int, ProductionPerson>
     * }
     */
    public function overviewFor(Production $production): array
    {
        $people = $production->relationLoaded('people')
            ? $production->people
            : $production->people()->orderBy('sort_order')->orderBy('name')->get();

        $crew = $people
            ->filter(fn ($person) => $person instanceof ProductionPerson && $person->canLogIn())
            ->values();
        $participants = $people
            ->filter(fn ($person) => $person instanceof ProductionPerson && $person->isParticipant())
            ->values();
        $remaining = $participants->filter(fn ($person) => ! $person->hasDeparted())->values();
        $departed = $participants->filter(fn ($person) => $person->hasDeparted())->values();
        $participantsInside = $remaining->filter(fn ($person) => $person->isInside())->values();
        $participantsOutside = $remaining->filter(fn ($person) => ! $person->isInside())->values();

        return [
            'production' => $production,
            'insideCount' => $people->filter(fn ($person) => $person instanceof ProductionPerson && $person->isInside())->count(),
            'crewInside' => $crew->filter(fn ($person) => $person->isInside())->values(),
            'crewOutside' => $crew->filter(fn ($person) => ! $person->isInside())->values(),
            'participantsInside' => $participantsInside,
            'participantsOutside' => $participantsOutside,
            'departedParticipants' => $departed,
        ];
    }

    public function adminLogs(?int $productionId = null): LengthAwarePaginator
    {
        return ProductionPresenceLog::query()
            ->with(['person', 'recorder', 'production'])
            ->when($productionId, fn ($query) => $query->where('production_id', $productionId))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();
    }

    public function personForUser(Production $production, User $user): ?ProductionPerson
    {
        return $production->people()
            ->where('user_id', $user->id)
            ->whereIn('kind', [ProductionPerson::KIND_ADMIN, ProductionPerson::KIND_STAFF])
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{
     *     production: Production,
     *     actor: ProductionPerson,
     *     insideCount: int,
     *     remainingParticipantCount: int,
     *     remainingParticipantsInside: int,
     *     groupStampCount: int,
     *     crewInside: Collection<int, ProductionPerson>,
     *     crewOutside: Collection<int, ProductionPerson>,
     *     participantsInside: Collection<int, ProductionPerson>,
     *     participantsOutside: Collection<int, ProductionPerson>,
     *     departedParticipants: Collection<int, ProductionPerson>
     * }
     */
    public function board(Production $production, ProductionPerson $actor): array
    {
        $people = $production->people()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $crew = $people
            ->filter(fn ($person) => $person instanceof ProductionPerson && $person->canLogIn())
            ->values();
        $participants = $people
            ->filter(fn ($person) => $person instanceof ProductionPerson && $person->isParticipant())
            ->values();
        $remaining = $participants->filter(fn ($person) => ! $person->hasDeparted())->values();
        $departed = $participants->filter(fn ($person) => $person->hasDeparted())->values();
        $actor = $people->first(fn ($person) => (int) $person->id === (int) $actor->id) ?? $actor;
        $participantsInside = $remaining->filter(fn ($person) => $person->isInside())->values();
        $participantsOutside = $remaining->filter(fn ($person) => ! $person->isInside())->values();

        return [
            'production' => $production,
            'actor' => $actor,
            'insideCount' => $people->filter(fn ($person) => $person instanceof ProductionPerson && $person->isInside())->count(),
            'remainingParticipantCount' => $remaining->count(),
            'remainingParticipantsInside' => $participantsInside->count(),
            'groupStampCount' => $actor->isInside()
                ? $participantsInside->count()
                : $participantsOutside->count(),
            'crewInside' => $crew->filter(fn ($person) => $person->isInside())->values(),
            'crewOutside' => $crew->filter(fn ($person) => ! $person->isInside())->values(),
            'participantsInside' => $participantsInside,
            'participantsOutside' => $participantsOutside,
            'departedParticipants' => $departed,
        ];
    }

    /**
     * @return Collection<int, ProductionPresenceLog>
     */
    public function recentLogs(Production $production, int $limit = 150): Collection
    {
        return $production->presenceLogs()
            ->with(['person', 'recorder'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function stampSelf(
        ProductionPerson $actor,
        string $direction,
        User $recordedBy,
        bool $withGroup = false,
    ): int {
        $this->assertLoginPerson($actor);

        return (int) DB::transaction(function () use ($actor, $direction, $recordedBy, $withGroup) {
            $stamped = 0;

            if ($this->applyStamp($actor, $direction, $recordedBy, $withGroup)) {
                $stamped++;
            }

            if ($withGroup) {
                $stamped += $this->stampRemainingParticipants($actor->production, $direction, $recordedBy);
            }

            return $stamped;
        });
    }

    public function stampPerson(
        ProductionPerson $person,
        string $direction,
        User $recordedBy,
    ): bool {
        if ($person->hasDeparted()) {
            throw new InvalidArgumentException('Personen har åkt ut och stämplas inte längre in eller ut.');
        }

        return (bool) DB::transaction(function () use ($person, $direction, $recordedBy) {
            return $this->applyStamp($person, $direction, $recordedBy, false);
        });
    }

    public function markDeparted(ProductionPerson $person, User $recordedBy): void
    {
        if (! $person->isParticipant()) {
            throw new InvalidArgumentException('Bara deltagare kan märkas som utresta.');
        }

        if ($person->hasDeparted()) {
            return;
        }

        DB::transaction(function () use ($person, $recordedBy) {
            if ($person->is_inside) {
                $this->applyStamp($person, ProductionPerson::DIRECTION_OUT, $recordedBy, false);
            }

            $person->update([
                'departed_at' => now(),
                'departed_on' => now()->toDateString(),
                'is_inside' => false,
            ]);
        });
    }

    public function restoreDeparted(ProductionPerson $person): void
    {
        if (! $person->isParticipant()) {
            throw new InvalidArgumentException('Bara deltagare kan återställas.');
        }

        $person->update([
            'departed_at' => null,
            'departed_on' => null,
        ]);
    }

    public function addPerson(
        Production $production,
        array $data,
        User $createdBy,
    ): ProductionPerson {
        $kind = $data['kind'];
        $name = trim((string) $data['name']);

        if ($kind === ProductionPerson::KIND_PARTICIPANT) {
            return $production->people()->create([
                'user_id' => null,
                'name' => $name,
                'kind' => ProductionPerson::KIND_PARTICIPANT,
                'sort_order' => (int) ($production->people()->max('sort_order') ?? 0) + 1,
                'created_by' => $createdBy->id,
            ]);
        }

        $role = Role::query()->where('slug', $this->roleSlugForKind($kind))->firstOrFail();

        return DB::transaction(function () use ($production, $data, $name, $kind, $role, $createdBy) {
            $user = User::query()->create([
                'name' => $name,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $user->assignRoles([$role]);

            return $production->people()->create([
                'user_id' => $user->id,
                'name' => $name,
                'kind' => $kind,
                'sort_order' => (int) ($production->people()->max('sort_order') ?? 0) + 1,
                'created_by' => $createdBy->id,
            ]);
        });
    }

    public function updatePerson(ProductionPerson $person, array $data): ProductionPerson
    {
        $kind = $data['kind'];
        $name = trim((string) $data['name']);

        return DB::transaction(function () use ($person, $data, $kind, $name) {
            if ($kind === ProductionPerson::KIND_PARTICIPANT) {
                if ($person->user) {
                    $person->user->update([
                        'name' => $name,
                        'is_active' => false,
                    ]);
                }

                $person->update([
                    'name' => $name,
                    'kind' => ProductionPerson::KIND_PARTICIPANT,
                    'user_id' => null,
                ]);

                return $person->fresh();
            }

            $role = Role::query()->where('slug', $this->roleSlugForKind($kind))->firstOrFail();

            if ($person->user) {
                $userData = [
                    'name' => $name,
                    'email' => $data['email'],
                    'is_active' => true,
                ];

                if (filled($data['password'] ?? null)) {
                    $userData['password'] = Hash::make($data['password']);
                }

                $person->user->update($userData);
                $person->user->assignRoles([$role]);
                $userId = $person->user_id;
            } else {
                if (! filled($data['password'] ?? null)) {
                    throw new InvalidArgumentException('Lösenord krävs när personen ska kunna logga in.');
                }

                $user = User::query()->create([
                    'name' => $name,
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'is_active' => true,
                ]);
                $user->assignRoles([$role]);
                $userId = $user->id;
            }

            $person->update([
                'name' => $name,
                'kind' => $kind,
                'user_id' => $userId,
            ]);

            return $person->fresh(['user']);
        });
    }

    public function removePerson(ProductionPerson $person, User $actor): void
    {
        if ($person->user_id && (int) $person->user_id === (int) $actor->id) {
            throw new InvalidArgumentException('Du kan inte ta bort dig själv.');
        }

        DB::transaction(function () use ($person) {
            $user = $person->user;
            $person->delete();

            if ($user && $this->isProductionOnlyUser($user)) {
                $user->delete();
            }
        });
    }

    /**
     * @param  list<array{kind: string, name: string, email?: string|null, password?: string|null}>  $rows
     * @return array{created: int, skipped: int}
     */
    public function importPeople(Production $production, array $rows, User $createdBy): array
    {
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $kind = $row['kind'];
            $name = trim((string) $row['name']);

            if ($name === '') {
                $skipped++;

                continue;
            }

            if ($kind === ProductionPerson::KIND_PARTICIPANT) {
                $this->addPerson($production, [
                    'kind' => $kind,
                    'name' => $name,
                ], $createdBy);
                $created++;

                continue;
            }

            $email = strtolower(trim((string) ($row['email'] ?? '')));
            $password = (string) ($row['password'] ?? '');

            if ($email === '' || $password === '') {
                $skipped++;

                continue;
            }

            if (User::query()->where('email', $email)->exists()) {
                $skipped++;

                continue;
            }

            $this->addPerson($production, [
                'kind' => $kind,
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ], $createdBy);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * @return list<array{kind: string, name: string, email?: string|null, password?: string|null}>
     */
    public function parseCsv(string $csv): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', str_getcsv($line, ','));

            if ($index === 0 && $this->looksLikeHeader($parts)) {
                continue;
            }

            $kind = $this->normalizeKind($parts[0] ?? '');
            $name = $parts[1] ?? '';

            if ($kind === null || $name === '') {
                continue;
            }

            $rows[] = [
                'kind' => $kind,
                'name' => $name,
                'email' => $parts[2] ?? null,
                'password' => $parts[3] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $parts
     */
    private function looksLikeHeader(array $parts): bool
    {
        $first = mb_strtolower($parts[0] ?? '');

        return in_array($first, ['kind', 'niva', 'nivå', 'roll', 'typ'], true);
    }

    private function normalizeKind(string $value): ?string
    {
        $value = mb_strtolower(trim($value));

        return match ($value) {
            'admin', 'produktion admin', 'produktion_admin' => ProductionPerson::KIND_ADMIN,
            'staff', 'personal', 'produktion personal', 'produktion_personal' => ProductionPerson::KIND_STAFF,
            'participant', 'deltagare' => ProductionPerson::KIND_PARTICIPANT,
            default => null,
        };
    }

    private function stampRemainingParticipants(
        Production $production,
        string $direction,
        User $recordedBy,
    ): int {
        $wantInside = $direction === ProductionPerson::DIRECTION_IN;
        $stamped = 0;

        $participants = $production->people()
            ->participants()
            ->remaining()
            ->where('is_inside', ! $wantInside)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        foreach ($participants as $participant) {
            if ($this->applyStamp($participant, $direction, $recordedBy, true)) {
                $stamped++;
            }
        }

        return $stamped;
    }

    private function applyStamp(
        ProductionPerson $person,
        string $direction,
        User $recordedBy,
        bool $withGroup,
    ): bool {
        $this->assertDirection($direction);

        if ($person->hasDeparted()) {
            return false;
        }

        $wantInside = $direction === ProductionPerson::DIRECTION_IN;

        if ($person->isInside() === $wantInside) {
            return false;
        }

        $person->update([
            'is_inside' => $wantInside,
            'last_presence_at' => now(),
        ]);

        ProductionPresenceLog::query()->create([
            'production_id' => $person->production_id,
            'production_person_id' => $person->id,
            'direction' => $direction,
            'occurred_at' => now(),
            'with_group' => $withGroup,
            'recorded_by' => $recordedBy->id,
        ]);

        return true;
    }

    private function isProductionOnlyUser(User $user): bool
    {
        $user->loadMissing('roles');

        $slugs = $user->roles
            ->pluck('slug')
            ->filter(fn ($slug) => is_string($slug) && $slug !== '')
            ->values()
            ->all();

        return $slugs !== [] && array_diff($slugs, Roles::productionLoginRoles()) === [];
    }

    private function roleSlugForKind(string $kind): string
    {
        return $kind === ProductionPerson::KIND_ADMIN
            ? Roles::PRODUKTION_ADMIN
            : Roles::PRODUKTION_PERSONAL;
    }

    private function assertLoginPerson(ProductionPerson $person): void
    {
        if (! $person->canLogIn()) {
            throw new InvalidArgumentException('Deltagare stämplar inte själva. Ta med dem som grupp.');
        }
    }

    private function assertDirection(string $direction): void
    {
        if (! in_array($direction, [ProductionPerson::DIRECTION_IN, ProductionPerson::DIRECTION_OUT], true)) {
            throw new InvalidArgumentException('Ogiltig riktning.');
        }
    }
}
