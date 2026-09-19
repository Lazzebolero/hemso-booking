<?php

namespace App\Services;

use App\Models\RestaurantFunction;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class WorkShiftGridBuilder
{
    public const ID_ROW_LABEL = 'id';

    public const ROLE_ROW_LABEL = 'roll';

    public const FUNCTION_ROW_LABEL = 'funktion';

    public const TIME_ROW_LABEL = 'tid';

    public const TIME_HEADER = 'Tid';

    public const ROLE_HEADER = 'Roll';

    /**
     * Excel-rader (1-baserat) som ska vara dolda: id, standardroll, funktion, tid.
     *
     * @var list<int>
     */
    public const HIDDEN_ROWS = [4, 5, 6, 7];

    public const FIRST_DAY_ROW = 8;

    public const LIST_SHEET_TITLE = 'Listor';

    public function __construct(
        private WorkShiftStaffDirectory $directory,
    ) {}

    /**
     * @param  Collection<int, User>  $staff
     * @return array{
     *     rows: list<list<string>>,
     *     people: list<array{name: string, time_col: int, role_col: int, options: list<string>}>
     * }
     */
    public function grid(string $title, Collection $staff, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $nameRow = [''];
        $pairHeader = [''];
        $idRow = [self::ID_ROW_LABEL];
        $roleRow = [self::ROLE_ROW_LABEL];
        $functionRow = [self::FUNCTION_ROW_LABEL];
        $timeRow = [self::TIME_ROW_LABEL];
        $people = [];

        foreach ($staff as $user) {
            $timeCol = count($nameRow);
            $roleCol = $timeCol + 1;

            $nameRow[] = $user->name;
            $nameRow[] = '';
            $pairHeader[] = self::TIME_HEADER;
            $pairHeader[] = self::ROLE_HEADER;
            $idRow[] = (string) $user->id;
            $idRow[] = '';
            $roleRow[] = Roles::labels()[$this->directory->defaultShiftRole($user, $title)] ?? '';
            $roleRow[] = '';
            $functionRow[] = $this->defaultFunctionLabel($user, $title);
            $functionRow[] = '';
            $timeRow[] = $this->directory->defaultTimeRange($user, $title);
            $timeRow[] = '';

            $people[] = [
                'name' => $user->name,
                'time_col' => $timeCol,
                'role_col' => $roleCol,
                'options' => $this->directory->roleChoices($user, $title),
            ];
        }

        $shifts = $this->existingShifts($staff, $from, $to, $title);

        $rows = [
            [$title, $from->toDateString(), $to->toDateString()],
            $nameRow,
            $pairHeader,
            $idRow,
            $roleRow,
            $functionRow,
            $timeRow,
        ];

        $cursor = $from->copy();
        $currentMonth = null;
        $currentWeek = null;

        while ($cursor->lte($to)) {
            if ($currentMonth !== $cursor->month) {
                $rows[] = [$this->monthLabel($cursor->month)];
                $currentMonth = $cursor->month;
                $currentWeek = null;
            }

            $week = $cursor->isoWeek();

            if ($currentWeek !== $week) {
                $rows[] = ['V:'.$week];
                $currentWeek = $week;
            }

            $dayRow = [$cursor->toDateString().' '.$this->weekdayLabel($cursor->dayOfWeekIso)];
            $date = $cursor->toDateString();

            foreach ($staff as $user) {
                $shift = $shifts->get($user->id.'|'.$date);

                if (! $shift) {
                    $dayRow[] = '';
                    $dayRow[] = '';

                    continue;
                }

                $dayRow[] = $this->shiftTimeLabel($shift);
                $dayRow[] = $this->shiftRoleCell($shift, $title);
            }

            $rows[] = $dayRow;
            $cursor->addDay();
        }

        return [
            'rows' => $rows,
            'people' => $people,
        ];
    }

    /**
     * @param  list<array{name: string, time_col: int, role_col: int, options: list<string>}>  $guidePeople
     * @param  list<array{name: string, time_col: int, role_col: int, options: list<string>}>  $kitchenPeople
     * @return array{
     *     rows: list<list<string>>,
     *     guides: list<array{name: string, time_col: int, role_col: int, options: list<string>, list_range: ?string}>,
     *     kitchen: list<array{name: string, time_col: int, role_col: int, options: list<string>, list_range: ?string}>
     * }
     */
    public function choiceLists(array $guidePeople, array $kitchenPeople): array
    {
        $header = [];
        $columns = [];

        $guides = $this->assignListRanges($guidePeople, $header, $columns);
        $kitchen = $this->assignListRanges($kitchenPeople, $header, $columns);

        $rows = [$header === [] ? [''] : $header];
        $height = $columns === [] ? 0 : max(array_map('count', $columns));

        for ($index = 0; $index < $height; $index++) {
            $row = [];

            foreach ($columns as $column) {
                $row[] = $column[$index] ?? '';
            }

            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'guides' => $guides,
            'kitchen' => $kitchen,
        ];
    }

    /**
     * @param  list<array{name: string, time_col: int, role_col: int, options: list<string>}>  $people
     * @param  list<string>  $header
     * @param  list<list<string>>  $columns
     * @return list<array{name: string, time_col: int, role_col: int, options: list<string>, list_range: ?string}>
     */
    private function assignListRanges(array $people, array &$header, array &$columns): array
    {
        foreach ($people as $index => $person) {
            $columnIndex = count($header);
            $letter = Coordinate::stringFromColumnIndex($columnIndex + 1);
            $count = count($person['options']);
            $header[] = $person['name'];
            $columns[] = $person['options'];
            $people[$index]['list_range'] = $count === 0
                ? null
                : self::LIST_SHEET_TITLE.'!$'.$letter.'$2:$'.$letter.'$'.($count + 1);
        }

        return $people;
    }

    /**
     * @param  Collection<int, User>  $staff
     * @return Collection<string, WorkShift>
     */
    private function existingShifts(Collection $staff, Carbon $from, Carbon $to, string $sheet): Collection
    {
        if ($staff->isEmpty()) {
            return collect();
        }

        $roles = $sheet === 'Kök'
            ? [Roles::RESTAURANT]
            : Roles::schedulePriorityRoles();

        return WorkShift::query()
            ->whereNotIn('status', ['cancelled'])
            ->whereIn('user_id', $staff->pluck('id'))
            ->whereIn('shift_role', $roles)
            ->whereDate('shift_date', '>=', $from->toDateString())
            ->whereDate('shift_date', '<=', $to->toDateString())
            ->orderBy('id')
            ->get()
            ->unique(fn (WorkShift $shift) => $shift->user_id.'|'.$shift->shift_date->toDateString())
            ->keyBy(fn (WorkShift $shift) => $shift->user_id.'|'.$shift->shift_date->toDateString());
    }

    private function shiftTimeLabel(WorkShift $shift): string
    {
        $start = substr(trim((string) $shift->start_time), 0, 5);
        $end = substr(trim((string) $shift->end_time), 0, 5);

        if ($start === '') {
            return '';
        }

        return $end !== '' ? $start.'-'.$end : $start;
    }

    private function shiftRoleCell(WorkShift $shift, string $sheet): string
    {
        if ($sheet === 'Kök' && filled($shift->shift_function)) {
            return RestaurantFunction::label($shift->shift_function) ?: $shift->shift_function;
        }

        return Roles::labels()[$shift->shift_role] ?? $shift->shift_role;
    }

    private function defaultFunctionLabel(User $user, string $sheet): string
    {
        $slug = $this->directory->defaultFunction($user, $sheet);

        return $slug ? (RestaurantFunction::label($slug) ?: $slug) : '';
    }

    private function monthLabel(int $month): string
    {
        return [
            1 => 'JANUARI',
            2 => 'FEBRUARI',
            3 => 'MARS',
            4 => 'APRIL',
            5 => 'MAJ',
            6 => 'JUNI',
            7 => 'JULI',
            8 => 'AUGUSTI',
            9 => 'SEPTEMBER',
            10 => 'OKTOBER',
            11 => 'NOVEMBER',
            12 => 'DECEMBER',
        ][$month] ?? '';
    }

    private function weekdayLabel(int $isoWeekday): string
    {
        return [
            1 => 'mån',
            2 => 'tis',
            3 => 'ons',
            4 => 'tors',
            5 => 'fre',
            6 => 'lör',
            7 => 'sön',
        ][$isoWeekday] ?? '';
    }
}
