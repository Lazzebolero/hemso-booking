<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class GuideOfflinePrepTest extends TestCase
{
    public function test_guide_offline_prep_script_is_served(): void
    {
        $this->get('/js/guide-offline-prep.js')
            ->assertOk()
            ->assertSee('warm-html-cache', false)
            ->assertSee('guide-warm-tour-id', false);
    }

    public function test_guide_layout_exposes_offline_shell_urls_meta(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('name="guide-offline-shell-urls"', false)
            ->assertSee(route('messages.index'), false)
            ->assertSee('guide-offline-prep.js', false);
    }

    public function test_guide_dashboard_exposes_ongoing_tour_url_meta_for_offline_prep(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Pågående offline turtyp']);

        $tour = Tour::query()->create([
            'title' => 'Pågående offline tur',
            'tour_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_participants' => 20,
            'status' => 'started',
            'started_at' => now(),
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('name="guide-offline-ongoing-tour-url"', false)
            ->assertSee(route('guide.tours.show', $tour), false);
    }

    public function test_quick_tour_redirect_includes_warm_tour_id_for_offline_prep(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 5,
                'language_ids' => [],
            ])
            ->assertRedirect(route('guide.dashboard'));

        $tour = Tour::query()->latest('id')->firstOrFail();

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('name="guide-warm-tour-id"', false)
            ->assertSee((string) $tour->id, false)
            ->assertSee('name="guide-offline-ongoing-tour-url"', false)
            ->assertSee(route('guide.tours.show', $tour), false);

        $this->followingRedirects()
            ->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 6,
                'language_ids' => [],
            ])
            ->assertOk()
            ->assertSee('name="guide-warm-tour-id"', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
