<?php

namespace App\Services;

use App\Events\OpeningDeviationCreated;
use App\Models\OpeningCheck;
use App\Models\OpeningDeviation;
use App\Models\User;
use App\Support\OpeningCheckpoints;
use App\Support\OpeningCheckTables;
use Carbon\CarbonInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningCheckService
{
    public function tablesExist(): bool
    {
        return OpeningCheckTables::exist();
    }

    public function forDate(CarbonInterface|string $date): ?OpeningCheck
    {
        if (! OpeningCheckTables::exist()) {
            return null;
        }

        return OpeningCheck::query()
            ->forDate($date)
            ->first();
    }

    public function isCompletedOn(CarbonInterface|string $date): bool
    {
        $check = $this->forDate($date);

        return $check !== null && $check->isCompleted();
    }

    public function openDeviationCount(): int
    {
        if (! OpeningCheckTables::exist()) {
            return 0;
        }

        return OpeningDeviation::query()->open()->count();
    }

    public function ensureForToday(User $user): OpeningCheck
    {
        if (! OpeningCheckTables::exist()) {
            throw ValidationException::withMessages([
                'opening_check' => 'Databasen saknar tabeller för öppningskontroll. Kör väntande migrationer under Systemhälsa.',
            ]);
        }
        $day = now()->toDateString();

        try {
            return DB::transaction(function () use ($user, $day): OpeningCheck {
                $existing = OpeningCheck::query()
                    ->forDate($day)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                return OpeningCheck::query()->create([
                    'check_date' => $day,
                    'opened_by' => $user->id,
                    'started_at' => now(),
                    'status' => OpeningCheck::STATUS_IN_PROGRESS,
                    'confirmed' => false,
                    'items' => [],
                ]);
            });
        } catch (UniqueConstraintViolationException) {
            return OpeningCheck::query()->forDate($day)->firstOrFail();
        }
    }

    /**
     * @param  array<string, mixed>  $outcomes
     * @param  array<string, mixed>  $deviationPayloads
     */
    public function save(
        OpeningCheck $check,
        User $user,
        array $outcomes,
        ?string $visitorOpensAt,
        bool $confirmed,
        bool $complete,
        array $deviationPayloads = [],
    ): OpeningCheck {
        if ($check->isCompleted() && $complete) {
            return $check;
        }

        $normalizedItems = $this->normalizeOutcomes($outcomes);

        foreach ($normalizedItems as $key => $outcome) {
            if ($outcome === OpeningCheckpoints::OUTCOME_DEVIATION) {
                $this->createDeviationFromPayloadIfNeeded(
                    $check,
                    $user,
                    $key,
                    is_array($deviationPayloads[$key] ?? null) ? $deviationPayloads[$key] : [],
                    $complete,
                );
            }
        }

        $check->fill([
            'visitor_opens_at' => $this->normalizeTime($visitorOpensAt),
            'items' => $normalizedItems,
        ]);

        if ($check->opened_by === null) {
            $check->opened_by = $user->id;
        }

        if ($check->started_at === null) {
            $check->started_at = now();
        }

        if ($complete) {
            $this->assertCanComplete($check, $confirmed);
            $check->confirmed = true;
            $check->completed_at = now();
            $check->status = OpeningCheck::STATUS_COMPLETED;
        } else {
            $check->confirmed = $confirmed;
        }

        $check->save();

        return $check->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function addDeviation(OpeningCheck $check, User $user, array $payload, ?string $checkpointKey = null): OpeningDeviation
    {
        $description = trim((string) ($payload['description'] ?? ''));

        if ($description === '') {
            throw ValidationException::withMessages([
                'description' => 'Beskriv avvikelsen.',
            ]);
        }

        return $this->storeDeviation($check, $user, $checkpointKey, $payload, alreadyResolved: $this->isChecked($payload['already_resolved'] ?? false));
    }

    public function resolve(OpeningDeviation $deviation, User $user, string $note): OpeningDeviation
    {
        if ($deviation->isResolved()) {
            return $deviation;
        }

        $trimmed = trim($note);

        $deviation->fill([
            'status' => OpeningDeviation::STATUS_RESOLVED,
            'resolved_by' => $user->id,
            'resolved_at' => now(),
            'resolution_note' => $trimmed !== '' ? $trimmed : 'Åtgärdad.',
        ]);
        $deviation->save();

        return $deviation;
    }

    /**
     * @param  array<string, mixed>  $outcomes
     * @return array<string, string>
     */
    private function normalizeOutcomes(array $outcomes): array
    {
        $normalized = [];

        foreach (OpeningCheckpoints::keys() as $key) {
            $value = $outcomes[$key] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            if (! in_array($value, OpeningCheckpoints::outcomes(), true)) {
                continue;
            }

            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private function normalizeTime(?string $time): ?string
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        try {
            $parsed = Carbon::createFromFormat('H:i', substr(trim($time), 0, 5));
        } catch (\Throwable) {
            return null;
        }

        return $parsed ? $parsed->format('H:i:s') : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function createDeviationFromPayloadIfNeeded(
        OpeningCheck $check,
        User $user,
        string $checkpointKey,
        array $payload,
        bool $complete,
    ): void {
        $exists = $check->deviations()
            ->where('checkpoint_key', $checkpointKey)
            ->exists();

        if ($exists) {
            return;
        }

        $description = trim((string) ($payload['description'] ?? ''));

        if ($description === '') {
            if ($complete) {
                throw ValidationException::withMessages([
                    'deviations.'.$checkpointKey.'.description' => 'Fyll i avvikelserapporten för punkten som har avvikelse.',
                ]);
            }

            return;
        }

        $this->storeDeviation(
            $check,
            $user,
            $checkpointKey,
            $payload,
            alreadyResolved: $this->isChecked($payload['already_resolved'] ?? false),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function storeDeviation(
        OpeningCheck $check,
        User $user,
        ?string $checkpointKey,
        array $payload,
        bool $alreadyResolved,
    ): OpeningDeviation {
        $immediateAction = $this->nullableString($payload['immediate_action'] ?? null);

        $deviation = $check->deviations()->create([
            'checkpoint_key' => $checkpointKey,
            'occurred_at' => now(),
            'location' => $this->nullableString($payload['location'] ?? null),
            'description' => trim((string) ($payload['description'] ?? '')),
            'immediate_action' => $immediateAction,
            'informed_person' => $this->nullableString($payload['informed_person'] ?? null),
            'decision_before_opening' => $this->nullableString($payload['decision_before_opening'] ?? null),
            'status' => $alreadyResolved ? OpeningDeviation::STATUS_RESOLVED : OpeningDeviation::STATUS_OPEN,
            'reported_by' => $user->id,
            'resolved_by' => $alreadyResolved ? $user->id : null,
            'resolved_at' => $alreadyResolved ? now() : null,
            'resolution_note' => $alreadyResolved
                ? ($immediateAction ?: 'Åtgärdad vid kontroll.')
                : null,
        ]);

        OpeningDeviationCreated::dispatch($deviation);

        return $deviation;
    }

    private function assertCanComplete(OpeningCheck $check, bool $confirmed): void
    {
        $missing = $check->unansweredCheckpointKeys();

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'items' => 'Alla kontrollpunkter måste fyllas i innan kontrollen kan slutföras.',
            ]);
        }

        if (blank($check->visitor_opens_at)) {
            throw ValidationException::withMessages([
                'visitor_opens_at' => 'Ange när anläggningen öppnas för besökare.',
            ]);
        }

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'confirmed' => 'Bekräfta att du har genomfört kontrollen.',
            ]);
        }

        $check->loadMissing('deviations');

        foreach (OpeningCheckpoints::keys() as $key) {
            if ($check->outcomeFor($key) !== OpeningCheckpoints::OUTCOME_DEVIATION) {
                continue;
            }

            $hasDeviation = $check->deviations->contains(
                fn (OpeningDeviation $deviation): bool => $deviation->checkpoint_key === $key
            );

            if (! $hasDeviation) {
                throw ValidationException::withMessages([
                    'deviations.'.$key.'.description' => 'Fyll i avvikelserapporten för punkten som har avvikelse.',
                ]);
            }
        }
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function isChecked(mixed $value): bool
    {
        return $value === true || $value === 1 || $value === '1' || $value === 'on';
    }
}
