<?php

namespace App\Services;

use App\Models\RestaurantFunction;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkShiftImportService
{
    /**
     * @return array{
     *     ready: list<array{
     *         user_id: int,
     *         shift_date: string,
     *         start_time: string,
     *         end_time: ?string,
     *         shift_role: string,
     *         shift_function: ?string,
     *         status: string,
     *         notes: ?string,
     *         person_name: string
     *     }>,
     *     errors: list<string>,
     *     skipped: int
     * }
     */
    public function preview(Collection $rows): array
    {
        $ready = [];
        $errors = [];
        $skipped = 0;
        $seenInFile = [];

        $users = User::query()
            ->with('roles')
            ->where('is_active', true)
            ->withoutProductionRoles()
            ->get()
            ->keyBy(fn (User $user) => mb_strtolower($user->email));

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $values = $this->normalizeRow(collect($row)->toArray());

            if ($this->rowIsEmpty($values)) {
                continue;
            }

            try {
                $parsed = $this->parseRow($values, $users);
            } catch (\InvalidArgumentException $exception) {
                $errors[] = "Rad {$rowNumber}: ".$exception->getMessage();

                continue;
            }

            $fileKey = $this->dedupKey($parsed);

            if (isset($seenInFile[$fileKey])) {
                $skipped++;

                continue;
            }

            $seenInFile[$fileKey] = true;

            if ($this->shiftExists($parsed)) {
                $skipped++;

                continue;
            }

            $ready[] = $parsed;
        }

        return [
            'ready' => $ready,
            'errors' => $errors,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  list<array{
     *     user_id: int,
     *     shift_date: string,
     *     start_time: string,
     *     end_time: ?string,
     *     shift_role: string,
     *     shift_function: ?string,
     *     status: string,
     *     notes: ?string,
     *     person_name: string
     * }>  $ready
     */
    public function commit(array $ready, User $recordedBy): int
    {
        return (int) DB::transaction(function () use ($ready, $recordedBy) {
            $created = 0;

            foreach ($ready as $row) {
                if ($this->shiftExists($row)) {
                    continue;
                }

                WorkShift::query()->create([
                    'user_id' => $row['user_id'],
                    'shift_date' => $row['shift_date'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'shift_role' => $row['shift_role'],
                    'shift_function' => $row['shift_function'],
                    'status' => $row['status'],
                    'notes' => $row['notes'],
                    'created_by' => $recordedBy->id,
                    'updated_by' => $recordedBy->id,
                ]);

                $created++;
            }

            return $created;
        });
    }

    /**
     * @param  array<int|string, mixed>  $values
     * @return array<string, mixed>
     */
    private function normalizeRow(array $values): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (is_int($key)) {
                $normalized[(string) $key] = $value;

                continue;
            }

            $normalized[Str::slug(trim((string) $key), '_')] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function rowIsEmpty(array $values): bool
    {
        return collect($values)->every(function (mixed $value): bool {
            if ($value === null) {
                return true;
            }

            if (is_string($value)) {
                return trim($value) === '';
            }

            return false;
        });
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  Collection<string, User>  $users
     * @return array{
     *     user_id: int,
     *     shift_date: string,
     *     start_time: string,
     *     end_time: ?string,
     *     shift_role: string,
     *     shift_function: ?string,
     *     status: string,
     *     notes: ?string,
     *     person_name: string
     * }
     */
    private function parseRow(array $values, Collection $users): array
    {
        $email = mb_strtolower(trim((string) $this->value($values, ['e_post', 'epost', 'email'])));

        if ($email === '') {
            throw new \InvalidArgumentException('E-post saknas.');
        }

        $user = $users->get($email);

        if (! $user) {
            throw new \InvalidArgumentException('Ingen aktiv Hemsö-person med e-post '.$email.'.');
        }

        $shiftRole = $this->parseRole($this->value($values, ['roll', 'shift_role', 'role']));

        if (! $user->hasRole($shiftRole)) {
            throw new \InvalidArgumentException($user->name.' har inte rollen '.$this->roleLabel($shiftRole).'.');
        }

        $date = $this->parseDate($this->value($values, ['datum', 'shift_date', 'date']));
        $startTime = $this->parseTime($this->value($values, ['starttid', 'start_time', 'start']), true);
        $endTime = $this->parseTime($this->value($values, ['sluttid', 'end_time', 'slut']), false);
        $status = $this->parseStatus($this->value($values, ['status']));
        $function = $this->parseFunction($shiftRole, $this->value($values, ['funktion', 'shift_function', 'function']));
        $notes = $this->nullableString($this->value($values, ['anteckning', 'notes', 'notering']));

        return [
            'user_id' => $user->id,
            'shift_date' => $date,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'shift_role' => $shiftRole,
            'shift_function' => $function,
            'status' => $status,
            'notes' => $notes,
            'person_name' => $user->name,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  list<string>  $keys
     */
    private function value(array $values, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null && $values[$key] !== '') {
                return $values[$key];
            }
        }

        return null;
    }

    private function parseRole(mixed $value): string
    {
        $normalized = Str::lower(trim((string) $value));
        $normalized = str_replace(['/', '_'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $role = match ($normalized) {
            'admin' => Roles::ADMIN,
            'host', 'vard', 'värd' => Roles::HOST,
            'guide' => Roles::GUIDE,
            'elev', 'trainee', 'trainee elev', 'trainee / elev' => Roles::ELEV,
            'restaurant', 'restaurang' => Roles::RESTAURANT,
            default => null,
        };

        if ($role === null) {
            throw new \InvalidArgumentException('Ogiltig roll. Använd Admin, Värd, Guide, Trainee / elev eller Restaurang.');
        }

        return $role;
    }

    private function parseDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        if (is_numeric($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateString();
        }

        $string = trim((string) $value);

        if ($string === '') {
            throw new \InvalidArgumentException('Datum saknas.');
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $string)) {
            return Carbon::parse($string)->toDateString();
        }

        if (preg_match('/^(\d{1,2})[-.\/](\d{1,2})[-.\/](\d{4})$/', $string, $matches)) {
            return Carbon::create((int) $matches[3], (int) $matches[2], (int) $matches[1])->toDateString();
        }

        try {
            return Carbon::parse($string)->toDateString();
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Ogiltigt datum.');
        }
    }

    private function parseTime(mixed $value, bool $required): ?string
    {
        if ($value === null || $value === '') {
            if ($required) {
                throw new \InvalidArgumentException('Starttid saknas.');
            }

            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        if (is_numeric($value) && (float) $value < 1) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('H:i');
        }

        $string = trim((string) $value);

        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $string, $matches)) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        throw new \InvalidArgumentException('Ogiltig tid. Använd t.ex. 09:00.');
    }

    private function parseStatus(mixed $value): string
    {
        $normalized = Str::lower(trim((string) $value));

        if ($normalized === '') {
            return 'planned';
        }

        return match ($normalized) {
            'planned', 'planerat' => 'planned',
            'confirmed', 'bekraftat', 'bekräftat' => 'confirmed',
            'changed', 'andrat', 'ändrat' => 'changed',
            'cancelled', 'installt', 'inställt' => 'cancelled',
            default => throw new \InvalidArgumentException('Ogiltig status. Använd Planerat, Bekräftat, Ändrat eller Inställt.'),
        };
    }

    private function parseFunction(string $shiftRole, mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($shiftRole !== Roles::RESTAURANT) {
            return null;
        }

        if ($raw === '') {
            throw new \InvalidArgumentException('Funktion krävs för restaurangpass.');
        }

        $options = RestaurantFunction::activeOptions();
        $slug = Str::slug($raw, '_');

        if (array_key_exists($raw, $options)) {
            return $raw;
        }

        if (array_key_exists($slug, $options)) {
            return $slug;
        }

        foreach ($options as $optionSlug => $label) {
            if (Str::lower($label) === Str::lower($raw) || $optionSlug === $slug) {
                return $optionSlug;
            }
        }

        throw new \InvalidArgumentException('Ogiltig restaurangfunktion.');
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * @param  array{user_id: int, shift_date: string, start_time: string, shift_role: string, shift_function: ?string}  $row
     */
    private function dedupKey(array $row): string
    {
        return implode('|', [
            $row['user_id'],
            $row['shift_date'],
            $row['shift_role'],
            $row['start_time'],
            $row['shift_function'] ?? '',
        ]);
    }

    /**
     * @param  array{user_id: int, shift_date: string, start_time: string, shift_role: string, shift_function: ?string}  $row
     */
    private function shiftExists(array $row): bool
    {
        return WorkShift::query()
            ->where('user_id', $row['user_id'])
            ->whereDate('shift_date', $row['shift_date'])
            ->where('shift_role', $row['shift_role'])
            ->where(function ($query) use ($row) {
                $query->where('start_time', $row['start_time'])
                    ->orWhere('start_time', $row['start_time'].':00');
            })
            ->when(
                $row['shift_function'] === null,
                fn ($query) => $query->whereNull('shift_function'),
                fn ($query) => $query->where('shift_function', $row['shift_function']),
            )
            ->exists();
    }

    private function roleLabel(string $slug): string
    {
        return Roles::labels()[$slug] ?? $slug;
    }
}
