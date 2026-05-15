<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Roles;

class TimeEntryPolicy
{
    public function manage(User $user): bool
    {
        $activeRole = session('active_role');

        return $activeRole === Roles::ADMIN && $user->canActivateRole(Roles::ADMIN);
    }

    public function track(User $user): bool
    {
        $activeRole = session('active_role');

        if (! is_string($activeRole)) {
            return false;
        }

        return in_array($activeRole, [Roles::GUIDE, Roles::HOST, Roles::ADMIN], true)
            && $user->canActivateRole($activeRole);
    }

    public function viewAny(User $user): bool
    {
        return $this->track($user) || $this->manage($user);
    }

    public function view(User $user, TimeEntry $timeEntry): bool
    {
        return $this->manage($user) || $timeEntry->user_id === $user->id;
    }

    public function update(User $user, TimeEntry $timeEntry): bool
    {
        return $timeEntry->user_id === $user->id && $timeEntry->isEditableByUser();
    }

    public function submit(User $user, TimeEntry $timeEntry): bool
    {
        return $timeEntry->user_id === $user->id;
    }

    public function clock(User $user): bool
    {
        return $this->track($user);
    }

    public function approve(User $user, TimeEntry $timeEntry): bool
    {
        return $this->manage($user);
    }

    public function correct(User $user, TimeEntry $timeEntry): bool
    {
        return $this->manage($user);
    }
}
