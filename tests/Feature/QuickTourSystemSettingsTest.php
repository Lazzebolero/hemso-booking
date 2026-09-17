<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Setting;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class QuickTourSystemSettingsTest extends TestCase
{
    public function test_quick_tour_uses_default_capacity_from_settings(): void
    {
        $this->setSetting('default_tour_capacity', '40');

        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 5,
                'language_ids' => [],
            ])
            ->assertRedirect(route('guide.dashboard'));

        $tour = Tour::query()->latest('id')->first();

        $this->assertNotNull($tour);
        $this->assertSame(40, $tour->max_participants);
    }

    private function setSetting(string $key, string $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
