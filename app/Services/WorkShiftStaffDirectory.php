<?php

namespace App\Services;

use App\Models\RestaurantFunction;
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

    /**
     * @return Collection<int, User>
     */
    public function forGuideSheet(): Collection
    {
        return $this->forTemplate()
            ->filter(fn (User $user) => $user->hasAnyRole(Roles::schedulePriorityRoles()))
            ->values();
    }

    /**
     * @return Collection<int, User>
     */
    public function forKitchenSheet(): Collection
    {
        return $this->forTemplate()
            ->filter(fn (User $user) => $user->hasRole(Roles::RESTAURANT))
            ->values();
    }

    public function defaultShiftRole(User $user, string $sheet = ''): string
    {
        if ($sheet === 'Kök' && $user->hasRole(Roles::RESTAURANT)) {
            return Roles::RESTAURANT;
        }

        foreach ([...Roles::schedulePriorityRoles(), Roles::RESTAURANT] as $slug) {
            if ($user->hasRole($slug)) {
                return $slug;
            }
        }

        return Roles::GUIDE;
    }

    public function defaultFunction(User $user, string $sheet = ''): ?string
    {
        if (! $user->hasRole(Roles::RESTAURANT)) {
            return null;
        }

        if ($sheet !== 'Kök' && $user->hasAnyRole(Roles::schedulePriorityRoles())) {
            return null;
        }

        $options = RestaurantFunction::activeOptions();

        foreach (['kock', 'kok'] as $slug) {
            if (array_key_exists($slug, $options)) {
                return $slug;
            }
        }

        return array_key_first($options);
    }

    public function defaultTimeRange(User $user, string $sheet = ''): string
    {
        return $this->defaultFunction($user, $sheet) ? '10:00-16:00' : '10:00';
    }

    /**
     * @return list<string>
     */
    public function scheduleRoleSlugs(User $user): array
    {
        return array_values(array_filter(
            Roles::scheduleStaffRoles(),
            fn (string $slug) => $user->hasRole($slug),
        ));
    }

    /**
     * Val i Excel-listan för rollcellen.
     *
     * @return list<string>
     */
    public function roleChoices(User $user, string $sheet): array
    {
        if ($sheet === 'Kök') {
            return array_values(RestaurantFunction::activeOptions());
        }

        $choices = [];

        foreach ($this->scheduleRoleSlugs($user) as $slug) {
            $choices[] = Roles::labels()[$slug] ?? $slug;
        }

        if ($user->hasRole(Roles::RESTAURANT)) {
            foreach (RestaurantFunction::activeOptions() as $label) {
                if (! in_array($label, $choices, true)) {
                    $choices[] = $label;
                }
            }
        }

        return $choices;
    }
}
