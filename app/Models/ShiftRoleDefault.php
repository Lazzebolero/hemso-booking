<?php

namespace App\Models;

use App\Support\Roles;
use App\Support\ShiftDefaultTimes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ShiftRoleDefault extends Model
{
    protected $fillable = [
        'role_slug',
        'default_start_time',
        'default_end_time',
    ];

    /**
     * @return array<string, self>
     */
    public static function keyedByRole(): array
    {
        if (! Schema::hasTable('shift_role_defaults')) {
            return [];
        }

        return static::query()
            ->get()
            ->keyBy('role_slug')
            ->all();
    }

    public static function timeRangeFor(string $roleSlug): string
    {
        $row = Schema::hasTable('shift_role_defaults')
            ? static::query()->where('role_slug', $roleSlug)->first()
            : null;

        return ShiftDefaultTimes::format(
            $row?->default_start_time,
            $row?->default_end_time,
            '10:00',
            null,
        );
    }

    /**
     * @return list<string>
     */
    public static function editableRoleSlugs(): array
    {
        return Roles::schedulePriorityRoles();
    }
}
