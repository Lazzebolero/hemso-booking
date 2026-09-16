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

    public function fullUpdate(User $user, VisitorDog $visitorDog): bool
    {
        if ($this->manage($user) && $this->usesStaffManagementRoutes()) {
            return true;
        }

        return $this->ownsRegistration($user, $visitorDog);
    }

    public function completePhoto(User $user, VisitorDog $visitorDog): bool
    {
        if (! $visitorDog->needsPhoto() || ! $this->register($user)) {
            return false;
        }

        return ! $this->fullUpdate($user, $visitorDog);
    }

    public function view(User $user, VisitorDog $visitorDog): bool
    {
        return $this->fullUpdate($user, $visitorDog)
            || $this->completePhoto($user, $visitorDog);
    }

    public function create(User $user): bool
    {
        return $this->register($user);
    }

    public function createFromDashboard(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, VisitorDog $visitorDog): bool
    {
        return $this->fullUpdate($user, $visitorDog)
            || $this->completePhoto($user, $visitorDog);
    }

    public function delete(User $user, VisitorDog $visitorDog): bool
    {
        return $this->fullUpdate($user, $visitorDog);
    }

    private function ownsRegistration(User $user, VisitorDog $visitorDog): bool
    {
        if ($visitorDog->registered_by !== $user->id) {
            return false;
        }

        return $this->register($user);
    }

    private function usesStaffManagementRoutes(): bool
    {
        $routeName = request()->route()?->getName();

        if (! is_string($routeName)) {
            return false;
        }

        return str_starts_with($routeName, 'admin.visitor-dogs.')
            || str_starts_with($routeName, 'host.visitor-dogs.');
    }
}
