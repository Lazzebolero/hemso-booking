<?php

namespace App\Services;

use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Collection;

class WorkShiftStaffDirectory
{
    /**
     * Aktiva Hemsö-personer som kan schemaläggas den här perioden.
     *
     * @return Collection<int, User>
     */
    public function forTemplate(): Collection
    {
        return User::query()
            ->with('roles')
            ->where('is_active', true)
            ->withoutProductionRoles()
            ->whereHas('roles', fn ($query) => $query->whereIn('slug', Roles::scheduleStaffRoles()))
            ->orderBy('name')
            ->get()
            ->sortBy(function (User $user): string {
                $group = $user->hasAnyRole(Roles::schedulePriorityRoles()) ? '0' : '1';

                return $group.'-'.mb_strtolower($user->name);
            })
            ->values();
    }

    public function templateGroupLabel(User $user): string
    {
        return $user->hasAnyRole(Roles::schedulePriorityRoles())
            ? 'Admin/värd/guide'
            : 'Restaurang';
    }
}
