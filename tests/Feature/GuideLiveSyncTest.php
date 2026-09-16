<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class GuideLiveSyncTest extends TestCase
{
    public function test_guide_dashboard_includes_silent_live_sync_only_on_dashboard(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('guide-live-sync.js', false)
            ->assertSee('name="guide-live-sync" content="dashboard"', false);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertDontSee('guide-live-sync.js', false);
    }

    public function test_guide_tour_show_includes_photos_section(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $tour->update(['status' => 'started', 'started_at' => now()]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertSee('Bilder från turen', false)
            ->assertSee('Ladda upp bild', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tourForGuide(User $guide): Tour
    {
        return Tour::query()->create([
            'title' => 'Guide live sync test',
            'description' => 'Testtur.',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
        ]);
    }
}
