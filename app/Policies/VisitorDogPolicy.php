<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;

class VisitorDogPolicy
{
    public function manage(User $user): bool
    {
        $activeRole = session('active_role');

        if (! is_string($activeRole)) {
            return false;
        }

        return in_array($activeRole, [Roles::ADMIN, Roles::HOST], true)
            && $user->canActivateRole($activeRole);
    }

    public function register(User $user): bool
    {
        $activeRole = session('active_role');

        if (! is_string($activeRole)) {
            return false;
        }

        return in_array($activeRole, [Roles::GUIDE, Roles::HOST], true)
            && $user->canActivateRole($activeRole);
    }

    public function viewAny(User $user): bool
    {
        return $this->manage($user) || $this->register($user);
    }

    public function view(User $user, VisitorDog $visitorDog): bool
    {
        return $this->manage($user) || $this->ownsRegistration($user, $visitorDog);
    }

    public function create(User $user): bool
    {
        return $this->register($user);
    }

    public function update(User $user, VisitorDog $visitorDog): bool
    {
        return $this->view($user, $visitorDog);
    }

    public function delete(User $user, VisitorDog $visitorDog): bool
    {
        return $this->view($user, $visitorDog);
    }

    private function ownsRegistration(User $user, VisitorDog $visitorDog): bool
    {
        if ($visitorDog->registered_by !== $user->id) {
            return false;
        }

        return $this->register($user);
    }
}
