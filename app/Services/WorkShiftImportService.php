<?php

namespace App\Services;

use App\Models\RestaurantFunction;
use App\Models\ShiftRoleDefault;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
     *     changes: list<array<string, mixed>>,
     *     removals: list<array<string, mixed>>,
     *     errors: list<string>,
     *     skipped: int
     * }
     */
    public function preview(Collection $rows): array
    {
        $seenInFile = [];
        $collected = $this->collectListAssignments($rows, $seenInFile);

        return $this->classifyAgainstDatabase(
            $collected['assignments'],
            $collected['errors'],
            $collected['skipped'],
        );
    }

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
     *     changes: list<array<string, mixed>>,
     *     removals: list<array<string, mixed>>,
     *     errors: list<string>,
     *     skipped: int
     * }
     */
    public function previewUploaded(string $path): array
    {
        $spreadsheet = IOFactory::load($path);

        $assignments = [];
        $errors = [];
        $skipped = 0;
        $seenInFile = [];
        $coveredDays = [];
        $erroredDays = [];

        $usersById = User::query()
            ->with('roles')
            ->where('is_active', true)
            ->withoutProductionRoles()
            ->get()
            ->keyBy('id');

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $title = $sheet->getTitle();

            if (in_array(Str::lower(trim($title)), ['instruktion', 'listor'], true)) {
                continue;
            }

            $rows = $sheet->toArray(null, true, false, false);

            if ($this->isGridRows($rows)) {
                $part = $this->collectGridAssignments($title, $rows, $usersById, $seenInFile);
                $assignments = array_merge($assignments, $part['assignments']);
                $errors = array_merge($errors, $part['errors']);
                $skipped += $part['skipped'];
                $coveredDays = array_merge($coveredDays, $part['covered']);
                $erroredDays = array_merge($erroredDays, $part['errored']);

                continue;
            }

            if ($this->isListRows($rows)) {
                $part = $this->collectListAssignments($this->listRowsToCollection($rows), $seenInFile);
                $assignments = array_merge($assignments, $part['assignments']);
                $errors = array_merge($errors, $part['errors']);
                $skipped += $part['skipped'];
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $this->classifyAgainstDatabase($assignments, $errors, $skipped, $coveredDays, $erroredDays);
    }

    /**
     * @param  list<array<string, mixed>>  $ready
     * @param  list<array<string, mixed>>  $updates
     * @param  list<array<string, mixed>>  $removals
     * @return array{created: int, updated: int, deleted: int}
     */
    public function commit(array $ready, array $updates, User $recordedBy, array $removals = []): array
    {
        return DB::transaction(function () use ($ready, $updates, $recordedBy, $removals) {
            $created = 0;
            $updated = 0;
            $deleted = 0;

            foreach ($ready as $row) {
                if ($this->activeShiftForDay($row['user_id'], $row['shift_date'])) {
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

            foreach ($updates as $row) {
                $shift = WorkShift::query()
                    ->whereKey($row['work_shift_id'])
                    ->where('user_id', $row['user_id'])
                    ->whereDate('shift_date', $row['shift_date'])
                    ->first();

                if (! $shift) {
                    continue;
                }

                $shift->update([
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'shift_role' => $row['shift_role'],
                    'shift_function' => $row['shift_function'],
                    'status' => 'changed',
                    'updated_by' => $recordedBy->id,
                ]);

                $updated++;
            }

            foreach ($removals as $row) {
                $deleted += WorkShift::query()
                    ->whereKey($row['work_shift_id'])
                    ->where('user_id', $row['user_id'])
                    ->whereDate('shift_date', $row['shift_date'])
                    ->where(function ($query) {
                        $query->whereNull('status')->orWhere('status', '!=', 'cancelled');
                    })
                    ->delete();
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'deleted' => $deleted,
            ];
        });
    }

    /**
     * @param  array<string, array{fingerprint: string, location: string}>  $seenInFile
     * @return array{assignments: list<array<string, mixed>>, errors: list<string>, skipped: int}
     */
    private function collectListAssignments(Collection $rows, array &$seenInFile): array
    {
        $assignments = [];
        $errors = [];
        $skipped = 0;

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

            $location = 'Rad '.$rowNumber;

            try {
                $parsed = $this->parseRow($values, $users);
            } catch (\InvalidArgumentException $exception) {
                $errors[] = $location.': '.$exception->getMessage();

                continue;
            }

            $result = $this->registerInFile($parsed, $location, $seenInFile);

            if ($result === 'skip') {
                $skipped++;

                continue;
            }

            if ($result !== 'keep') {
                $errors[] = $location.': '.$result;

                continue;
            }

            $assignments[] = $parsed;
        }

        return [
            'assignments' => $assignments,
            'errors' => $errors,
            'skipped' => $skipped,
        ];
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

        foreach ($this->functionAliases($raw) as $candidate) {
            if (array_key_exists($candidate, $options)) {
                return $candidate;
            }
        }

        throw new \InvalidArgumentException('Ogiltig restaurangfunktion.');
    }

    /**
     * @return list<string>
     */
    private function functionAliases(string $raw): array
    {
        $normalized = Str::lower(trim($raw));

        return match ($normalized) {
            'kök', 'kok', 'kock' => ['kok', 'kock'],
            'buffé', 'buffe' => ['buffe'],
            'glassbaren', 'glassbar', 'glass' => ['glassbar'],
            default => [],
        };
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     */
    private function isGridRows(array $rows): bool
    {
        foreach ($rows as $row) {
            if (Str::lower($this->cellString($row[0] ?? null)) === WorkShiftGridBuilder::ID_ROW_LABEL) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     */
    private function isListRows(array $rows): bool
    {
        foreach ($rows as $row) {
            $headers = collect($row)
                ->map(fn ($value) => Str::slug(trim((string) $value), '_'))
                ->filter()
                ->all();

            if ($headers === []) {
                continue;
            }

            $hasDate = in_array('datum', $headers, true) || in_array('date', $headers, true);
            $hasEmail = in_array('e_post', $headers, true) || in_array('epost', $headers, true) || in_array('email', $headers, true);

            return $hasDate && $hasEmail;
        }

        return false;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function listRowsToCollection(array $rows): Collection
    {
        $header = null;
        $data = collect();

        foreach ($rows as $row) {
            $values = $this->normalizeRow(is_array($row) ? $row : []);

            if ($this->rowIsEmpty($values)) {
                continue;
            }

            if ($header === null) {
                $header = [];

                foreach (array_values($row) as $index => $value) {
                    $header[$index] = Str::slug(trim((string) $value), '_');
                }

                continue;
            }

            $assoc = [];

            foreach ($header as $index => $key) {
                if ($key === '') {
                    continue;
                }

                $assoc[$key] = array_values($row)[$index] ?? null;
            }

            $data->push($assoc);
        }

        return $data;
    }

    /**
     * @param  list<array<int, mixed>>  $rows
     * @param  array<string, array{fingerprint: string, location: string}>  $seenInFile
     * @return array{
     *     assignments: list<array<string, mixed>>,
     *     errors: list<string>,
     *     skipped: int,
     *     covered: array<string, string>,
     *     errored: array<string, true>
     * }
     */
    private function collectGridAssignments(string $sheetTitle, array $rows, Collection $usersById, array &$seenInFile): array
    {
        $assignments = [];
        $errors = [];
        $skipped = 0;
        $covered = [];
        $errored = [];
        $idRow = $roleRow = $functionRow = $timeRow = null;

        foreach ($rows as $row) {
            $label = Str::lower($this->cellString($row[0] ?? null));

            if ($label === WorkShiftGridBuilder::ID_ROW_LABEL) {
                $idRow = $row;
            } elseif ($label === WorkShiftGridBuilder::ROLE_ROW_LABEL) {
                $roleRow = $row;
            } elseif ($label === WorkShiftGridBuilder::FUNCTION_ROW_LABEL) {
                $functionRow = $row;
            } elseif ($label === WorkShiftGridBuilder::TIME_ROW_LABEL) {
                $timeRow = $row;
            }
        }

        if ($idRow === null) {
            return [
                'assignments' => [],
                'errors' => ["Fliken {$sheetTitle} saknar id-rad. Ladda ner en ny mall."],
                'skipped' => 0,
                'covered' => [],
                'errored' => [],
            ];
        }

        $columns = $this->gridColumns($idRow, $roleRow, $functionRow, $timeRow);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 1;
            $label = $this->cellString($row[0] ?? null);

            if ($this->isGridMetaLabel($label) || $this->isGridStructureRow($label)) {
                continue;
            }

            try {
                $date = $this->parseGridDate($label);
            } catch (\InvalidArgumentException) {
                continue;
            }

            foreach ($columns as $column) {
                $key = $column['user_id'].'|'.$date;
                $user = $usersById->get($column['user_id']);
                $covered[$key] = $user?->name ?? '#'.$column['user_id'];

                $timeCell = $row[$column['time_col']] ?? null;
                $roleCell = $column['role_col'] !== null
                    ? ($row[$column['role_col']] ?? null)
                    : null;

                if (! $this->gridCellsHaveContent($timeCell, $roleCell)) {
                    continue;
                }

                $location = "{$sheetTitle} rad {$rowNumber}";

                try {
                    $parsed = $this->parseGridAssignment($timeCell, $roleCell, $column, $date, $usersById);
                } catch (\InvalidArgumentException $exception) {
                    $errored[$key] = true;
                    $errors[] = $location.': '.$exception->getMessage();

                    continue;
                }

                if ($parsed === null) {
                    continue;
                }

                $result = $this->registerInFile($parsed, $location, $seenInFile);

                if ($result === 'skip') {
                    $skipped++;

                    continue;
                }

                if ($result !== 'keep') {
                    $errored[$key] = true;
                    $errors[] = $location.': '.$result;

                    continue;
                }

                $assignments[] = $parsed;
            }
        }

        return [
            'assignments' => $assignments,
            'errors' => $errors,
            'skipped' => $skipped,
            'covered' => $covered,
            'errored' => $errored,
        ];
    }

    /**
     * @param  array<int, mixed>|null  $idRow
     * @param  array<int, mixed>|null  $roleRow
     * @param  array<int, mixed>|null  $functionRow
     * @param  array<int, mixed>|null  $timeRow
     * @return list<array{user_id: int, time_col: int, role_col: ?int, role: string, function: string, time: string}>
     */
    private function gridColumns(?array $idRow, ?array $roleRow, ?array $functionRow, ?array $timeRow): array
    {
        $columns = [];
        $idRow = $idRow ?? [];
        $count = count($idRow);

        for ($col = 1; $col < $count; $col++) {
            $userId = (int) $this->cellString($idRow[$col] ?? null);

            if ($userId <= 0) {
                continue;
            }

            $nextId = (int) $this->cellString($idRow[$col + 1] ?? null);
            $isPair = $nextId <= 0;

            $columns[] = [
                'user_id' => $userId,
                'time_col' => $col,
                'role_col' => $isPair ? $col + 1 : null,
                'role' => $this->cellString($roleRow[$col] ?? null),
                'function' => $this->cellString($functionRow[$col] ?? null),
                'time' => $this->cellString($timeRow[$col] ?? null),
            ];

            if ($isPair) {
                $col++;
            }
        }

        return $columns;
    }

    private function gridCellsHaveContent(mixed $timeCell, mixed $roleCell): bool
    {
        if ($this->cellIsFilled($timeCell)) {
            return true;
        }

        $roleText = $this->cellString($roleCell);

        return $roleText !== ''
            && ! $this->isSkippedGridCell($roleText)
            && $this->looksLikeFunction($roleText);
    }

    private function cellIsFilled(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if (is_numeric($value) && (float) $value > 0) {
            return true;
        }

        return $this->cellString($value) !== '';
    }

    /**
     * @param  array{user_id: int, time_col: int, role_col: ?int, role: string, function: string, time: string}  $column
     * @param  Collection<int, User>  $usersById
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
     * }|null
     */
    private function parseGridAssignment(mixed $timeCell, mixed $roleCell, array $column, string $date, Collection $usersById): ?array
    {
        $roleText = $this->cellString($roleCell);

        if ($this->cellIsFilled($timeCell) && $this->isSkippedGridCell($this->cellString($timeCell))) {
            return null;
        }

        if ($roleText !== '' && $this->isSkippedGridCell($roleText)) {
            return null;
        }

        if (! $this->cellIsFilled($timeCell) && ! $this->looksLikeFunction($roleText)) {
            return null;
        }

        $parsedCell = $this->cellIsFilled($timeCell)
            ? $this->parseGridCell($timeCell)
            : ['start' => null, 'end' => null, 'function' => null, 'role' => null];

        if ($parsedCell === null) {
            return null;
        }

        $user = $usersById->get($column['user_id']);

        if (! $user) {
            throw new \InvalidArgumentException('Ingen aktiv Hemsö-person med id '.$column['user_id'].'.');
        }

        $dayChoice = $this->parseDayRoleChoice($roleText);
        $shiftRole = $dayChoice['role'] ?? $parsedCell['role'] ?? null;

        if (is_string($shiftRole) && $shiftRole !== '' && ! in_array($shiftRole, Roles::scheduleStaffRoles(), true)) {
            $shiftRole = $this->parseRole($shiftRole);
        }

        $functionValue = $dayChoice['function']
            ?? $parsedCell['function']
            ?? ($column['function'] !== '' ? $column['function'] : null);

        if ($functionValue !== null && $functionValue !== '') {
            $shiftRole = Roles::RESTAURANT;
        }

        if (! is_string($shiftRole) || $shiftRole === '') {
            $shiftRole = $this->resolveGridRole($user, $column['role']);
        }

        $function = $this->parseFunction($shiftRole, $functionValue);

        $times = $this->parseTimeRange(
            $parsedCell['start'] ?? null,
            $parsedCell['end'] ?? null,
            $this->defaultTimeForAssignment($shiftRole, $function, $column['time']),
        );

        if (! $user->hasRole($shiftRole)) {
            throw new \InvalidArgumentException($user->name.' har inte rollen '.$this->roleLabel($shiftRole).'.');
        }

        return [
            'user_id' => $user->id,
            'shift_date' => $date,
            'start_time' => $times['start'],
            'end_time' => $times['end'],
            'shift_role' => $shiftRole,
            'shift_function' => $function,
            'status' => 'planned',
            'notes' => null,
            'person_name' => $user->name,
        ];
    }

    /**
     * @return array{role: ?string, function: ?string}
     */
    private function parseDayRoleChoice(string $text): array
    {
        if ($text === '') {
            return ['role' => null, 'function' => null];
        }

        if ($this->looksLikeFunction($text)) {
            return ['role' => Roles::RESTAURANT, 'function' => $text];
        }

        return ['role' => $this->parseRole($text), 'function' => null];
    }

    private function resolveGridRole(User $user, string $columnDefault): string
    {
        $owned = array_values(array_filter(
            Roles::scheduleStaffRoles(),
            fn (string $slug) => $user->hasRole($slug),
        ));

        if (count($owned) === 1) {
            return $owned[0];
        }

        if ($columnDefault !== '') {
            try {
                $fallback = $this->parseRole($columnDefault);

                if ($user->hasRole($fallback) && count($owned) <= 1) {
                    return $fallback;
                }
            } catch (\InvalidArgumentException) {
            }
        }

        throw new \InvalidArgumentException('Välj roll för '.$user->name.'.');
    }

    /**
     * @return array{start: ?string, end: ?string, function: ?string, role: ?string}|null
     */
    private function parseGridCell(mixed $value): ?array
    {
        if ($value instanceof \DateTimeInterface) {
            return [
                'start' => Carbon::instance($value)->format('H:i'),
                'end' => null,
                'function' => null,
                'role' => null,
            ];
        }

        if (is_numeric($value) && (float) $value > 0 && (float) $value < 1.5) {
            return [
                'start' => Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->format('H:i'),
                'end' => null,
                'function' => null,
                'role' => null,
            ];
        }

        $text = trim(str_replace(['–', '—'], '-', $this->cellString($value)));

        if ($text === '' || $this->isSkippedGridCell($text)) {
            return null;
        }

        if (preg_match('/^(\d{1,2})[.:](\d{2})(?:\s*-\s*(\d{1,2})[.:](\d{2}))?(?:\s+(.+))?$/u', $text, $matches)) {
            $start = sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
            $end = ($matches[3] ?? '') !== ''
                ? sprintf('%02d:%02d', (int) $matches[3], (int) $matches[4])
                : null;
            $rest = trim((string) ($matches[5] ?? ''));

            return [
                'start' => $start,
                'end' => $end,
                'function' => $rest !== '' && $this->looksLikeFunction($rest) ? $rest : null,
                'role' => $rest !== '' && ! $this->looksLikeFunction($rest) ? $rest : null,
            ];
        }

        if ($this->looksLikeFunction($text)) {
            return [
                'start' => null,
                'end' => null,
                'function' => $text,
                'role' => null,
            ];
        }

        try {
            return [
                'start' => null,
                'end' => null,
                'function' => null,
                'role' => $this->parseRole($text),
            ];
        } catch (\InvalidArgumentException) {
            throw new \InvalidArgumentException('Ogiltigt värde "'.$text.'".');
        }
    }

    /**
     * @return array{start: string, end: ?string}
     */
    private function parseTimeRange(?string $start, ?string $end, string $columnDefault): array
    {
        if ($start !== null) {
            return ['start' => $start, 'end' => $end];
        }

        $default = trim(str_replace(['–', '—'], '-', $columnDefault));

        if (preg_match('/^(\d{1,2})[.:](\d{2})(?:\s*-\s*(\d{1,2})[.:](\d{2}))?$/', $default, $matches)) {
            return [
                'start' => sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]),
                'end' => ($matches[3] ?? '') !== ''
                    ? sprintf('%02d:%02d', (int) $matches[3], (int) $matches[4])
                    : null,
            ];
        }

        throw new \InvalidArgumentException('Starttid saknas.');
    }

    private function defaultTimeForAssignment(string $shiftRole, ?string $function, string $columnDefault): string
    {
        if (is_string($function) && $function !== '') {
            return RestaurantFunction::timeRangeFor($function);
        }

        $columnDefault = trim(str_replace(['–', '—'], '-', $columnDefault));

        if ($columnDefault !== '') {
            return $columnDefault;
        }

        return ShiftRoleDefault::timeRangeFor($shiftRole);
    }

    private function parseGridDate(string $label): string
    {
        if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $label, $matches)) {
            return $matches[1];
        }

        throw new \InvalidArgumentException('Datum saknas.');
    }

    private function isGridMetaLabel(string $label): bool
    {
        return in_array(Str::lower($label), [
            '',
            WorkShiftGridBuilder::ID_ROW_LABEL,
            WorkShiftGridBuilder::ROLE_ROW_LABEL,
            WorkShiftGridBuilder::FUNCTION_ROW_LABEL,
            WorkShiftGridBuilder::TIME_ROW_LABEL,
        ], true);
    }

    private function isGridStructureRow(string $label): bool
    {
        $upper = mb_strtoupper($label);

        if (preg_match('/^V:\s*\d+/i', $label)) {
            return true;
        }

        return in_array($upper, [
            'JANUARI', 'FEBRUARI', 'MARS', 'APRIL', 'MAJ', 'JUNI',
            'JULI', 'AUGUSTI', 'SEPTEMBER', 'OKTOBER', 'NOVEMBER', 'DECEMBER',
            'GUIDER', 'KÖK', 'KOK', 'LISTOR',
        ], true);
    }

    private function isSkippedGridCell(string $text): bool
    {
        $normalized = Str::lower($text);
        $normalized = str_replace(['é', 'ä', 'ö', 'å'], ['e', 'a', 'o', 'a'], $normalized);

        foreach (['utbild', 'ubild', 'sjuk', 'slutar', 'borjar', 'stangt', 'midsommar'] as $token) {
            if ($normalized === $token || str_starts_with($normalized, $token)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeFunction(string $value): bool
    {
        try {
            $this->parseFunction(Roles::RESTAURANT, $value);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    private function cellString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('H:i');
        }

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     * @param  list<string>  $errors
     * @param  array<string, string>  $coveredDays
     * @param  array<string, true>  $erroredDays
     * @return array{
     *     ready: list<array<string, mixed>>,
     *     changes: list<array<string, mixed>>,
     *     removals: list<array<string, mixed>>,
     *     errors: list<string>,
     *     skipped: int
     * }
     */
    private function classifyAgainstDatabase(
        array $assignments,
        array $errors,
        int $skipped,
        array $coveredDays = [],
        array $erroredDays = [],
    ): array {
        $ready = [];
        $changes = [];
        $removals = [];
        $assignmentKeys = [];

        foreach ($assignments as $parsed) {
            $assignmentKeys[$this->dayKey($parsed)] = true;
        }

        $userIds = array_values(array_unique(array_merge(
            array_map(fn (array $row) => $row['user_id'], $assignments),
            array_map(fn (string $key) => (int) explode('|', $key, 2)[0], array_keys($coveredDays)),
        )));
        $dates = array_values(array_unique(array_merge(
            array_map(fn (array $row) => $row['shift_date'], $assignments),
            array_map(fn (string $key) => explode('|', $key, 2)[1] ?? '', array_keys($coveredDays)),
        )));
        $dates = array_values(array_filter($dates));

        if ($userIds === [] || $dates === []) {
            return [
                'ready' => [],
                'changes' => [],
                'removals' => [],
                'errors' => $errors,
                'skipped' => $skipped,
            ];
        }

        $existing = WorkShift::query()
            ->where(function ($query) {
                $query->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->whereIn('user_id', $userIds)
            ->whereDate('shift_date', '>=', min($dates))
            ->whereDate('shift_date', '<=', max($dates))
            ->orderBy('id')
            ->get()
            ->groupBy(fn (WorkShift $shift) => $shift->user_id.'|'.$shift->shift_date->toDateString());

        foreach ($assignments as $parsed) {
            $shift = $existing->get($this->dayKey($parsed))?->first();

            if (! $shift) {
                $ready[] = $parsed;

                continue;
            }

            if ($this->sameAssignment($shift, $parsed)) {
                $skipped++;

                continue;
            }

            $changes[] = array_merge($parsed, [
                'work_shift_id' => $shift->id,
                'current_start_time' => $this->timeKey($shift->start_time),
                'current_end_time' => $this->timeKey($shift->end_time),
                'current_shift_role' => $shift->shift_role,
                'current_shift_function' => $shift->shift_function,
            ]);
        }

        foreach ($coveredDays as $key => $personName) {
            if (isset($assignmentKeys[$key]) || isset($erroredDays[$key])) {
                continue;
            }

            $shift = $existing->get($key)?->first();

            if (! $shift) {
                continue;
            }

            $removals[] = [
                'work_shift_id' => $shift->id,
                'user_id' => $shift->user_id,
                'shift_date' => $shift->shift_date->toDateString(),
                'person_name' => $personName,
                'current_start_time' => $this->timeKey($shift->start_time),
                'current_end_time' => $this->timeKey($shift->end_time),
                'current_shift_role' => $shift->shift_role,
                'current_shift_function' => $shift->shift_function,
            ];
        }

        return [
            'ready' => $ready,
            'changes' => $changes,
            'removals' => $removals,
            'errors' => $errors,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<string, array{fingerprint: string, location: string}>  $seenInFile
     */
    private function registerInFile(array $parsed, string $location, array &$seenInFile): string
    {
        $key = $this->dayKey($parsed);
        $fingerprint = $this->assignmentFingerprint($parsed);

        if (! isset($seenInFile[$key])) {
            $seenInFile[$key] = [
                'fingerprint' => $fingerprint,
                'location' => $location,
            ];

            return 'keep';
        }

        if ($seenInFile[$key]['fingerprint'] === $fingerprint) {
            return 'skip';
        }

        return $parsed['person_name'].' finns redan '.$parsed['shift_date'].' i filen ('.$seenInFile[$key]['location'].'). En person kan bara ha ett pass per dag.';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function dayKey(array $row): string
    {
        return $row['user_id'].'|'.$row['shift_date'];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function assignmentFingerprint(array $row): string
    {
        return implode('|', [
            $row['shift_role'],
            $this->timeKey($row['start_time'] ?? null),
            $this->timeKey($row['end_time'] ?? null),
            $row['shift_function'] ?? '',
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function sameAssignment(WorkShift $shift, array $row): bool
    {
        return $this->assignmentFingerprint([
            'shift_role' => $shift->shift_role,
            'start_time' => $shift->start_time,
            'end_time' => $shift->end_time,
            'shift_function' => $shift->shift_function,
        ]) === $this->assignmentFingerprint($row);
    }

    private function timeKey(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time)->format('H:i');
        }

        $string = trim((string) $time);

        if ($string === '') {
            return '';
        }

        return substr($string, 0, 5);
    }

    private function activeShiftForDay(int $userId, string $date): bool
    {
        return WorkShift::query()
            ->where('user_id', $userId)
            ->whereDate('shift_date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->exists();
    }

    private function roleLabel(string $slug): string
    {
        return Roles::labels()[$slug] ?? $slug;
    }
}
