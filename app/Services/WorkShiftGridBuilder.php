<?php

namespace App\Services;

use App\Models\RestaurantFunction;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

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

            foreach ($staff as $user) {
                $dayRow[] = '';
                $dayRow[] = '';
            }

            $rows[] = $dayRow;
            $cursor->addDay();
        }

        return [
            'rows' => $rows,
            'people' => $people,
        ];
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
