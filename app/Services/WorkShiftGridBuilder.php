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

    public function __construct(
        private WorkShiftStaffDirectory $directory,
    ) {}

    /**
     * @param  Collection<int, User>  $staff
     * @return list<list<string>>
     */
    public function rows(string $title, Collection $staff, Carbon $from, Carbon $to): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->startOfDay();

        $rows = [
            [$title, $from->toDateString(), $to->toDateString()],
            array_merge([''], $staff->map(fn (User $user) => $user->name)->all()),
            array_merge([self::ID_ROW_LABEL], $staff->map(fn (User $user) => (string) $user->id)->all()),
            array_merge([self::ROLE_ROW_LABEL], $staff->map(fn (User $user) => Roles::labels()[$this->directory->defaultShiftRole($user)] ?? '')->all()),
            array_merge([self::FUNCTION_ROW_LABEL], $staff->map(function (User $user) {
                $slug = $this->directory->defaultFunction($user);

                return $slug ? (RestaurantFunction::label($slug) ?: $slug) : '';
            })->all()),
            array_merge([self::TIME_ROW_LABEL], $staff->map(fn (User $user) => $this->directory->defaultTimeRange($user))->all()),
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
            }

            $rows[] = $dayRow;
            $cursor->addDay();
        }

        return $rows;
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
