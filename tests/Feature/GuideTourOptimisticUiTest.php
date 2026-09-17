<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class GuideTourOptimisticUiTest extends TestCase
{
    public function test_guide_tour_show_includes_optimistic_ui_hooks(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertSee('data-guide-tour-root', false)
            ->assertSee('data-guide-tour-status-badge', false)
            ->assertSee('data-guide-tour-sync-pending', false)
            ->assertSee('Synkas när nätet är tillbaka', false)
            ->assertSee('data-guide-tour-template="start"', false)
            ->assertSee('data-guide-tour-template="complete"', false)
            ->assertSee('data-start-url="'.route('guide.tours.start', $tour).'"', false)
            ->assertSee('data-complete-url="'.route('guide.tours.complete', $tour).'"', false)
            ->assertSee('guide-tour-optimistic-ui.js', false)
            ->assertDontSee('setTimeout(function () {', false);
    }

    public function test_guide_tour_optimistic_ui_script_is_served(): void
    {
        $path = resource_path('js/guide-tour-optimistic-ui.js');
        $this->assertFileExists($path);

        $content = (string) file_get_contents($path);

        $this->assertStringContainsString('offline-queued', $content);
        $this->assertStringContainsString('sessionStorage', $content);
        $this->assertStringContainsString('hemso-guide-tour-pending:', $content);
        $this->assertStringContainsString('restorePendingState', $content);

        $this->get('/js/guide-tour-optimistic-ui.js')
            ->assertOk()
            ->assertSee('restorePendingState', false);
    }

    public function test_public_guide_tour_ui_matches_source_file(): void
    {
        $source = (string) file_get_contents(resource_path('js/guide-tour-optimistic-ui.js'));
        $public = (string) file_get_contents(public_path('js/guide-tour-optimistic-ui.js'));

        $this->assertSame($source, $public);
    }

    public function test_guide_tour_ui_submits_online_with_fetch(): void
    {
        $content = (string) file_get_contents(resource_path('js/guide-tour-optimistic-ui.js'));

        $this->assertStringContainsString('handleOnlineTourSubmit', $content);
        $this->assertStringContainsString("'Accept': 'application/json'", $content);
        $this->assertStringContainsString('applyServerTourState', $content);
        $this->assertStringContainsString('applyFlushResult', $content);
        $this->assertStringContainsString('window.hemsoGuideTourUi', $content);
    }

    public function test_guide_can_start_tour_via_ajax(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.start', $tour), [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'started',
                'message' => 'Tur startad.',
            ]);

        $this->assertSame('started', $tour->fresh()->status);
    }

    public function test_guide_can_complete_tour_via_ajax(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $tour->update(['status' => 'started', 'started_at' => now()]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.complete', $tour), [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJson([
                'status' => 'completed',
                'message' => 'Tur avslutad.',
                'redirect_url' => route('guide.dashboard'),
            ]);

        $this->assertSame('completed', $tour->fresh()->status);
        $this->assertNotNull($tour->fresh()->ended_at);
    }

    public function test_guide_complete_tour_redirects_to_dashboard(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $tour->update(['status' => 'started', 'started_at' => now()]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.complete', $tour))
            ->assertRedirect(route('guide.dashboard'))
            ->assertSessionHas('success', 'Tur avslutad.');
    }

    public function test_guide_quick_tour_create_shows_selected_language_state(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('quick-tours.create'))
            ->assertOk()
            ->assertSee('Valda språk:', false)
            ->assertSee('language-chip-selected-label', false)
            ->assertSee('data-language-summary-text', false);
    }

    public function test_guide_tour_show_prioritizes_bookings_and_hides_capacity_stats(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $tour->update(['status' => 'started', 'started_at' => now()]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Skolklass 7B',
            'contact_name' => 'Läraren',
            'men_count' => 2,
            'women_count' => 3,
            'youth_count' => 10,
            'child_count' => 5,
            'total_count' => 20,
            'status' => 'confirmed',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertSee('Avsluta tur', false)
            ->assertSee('bokningar', false)
            ->assertSee('personer', false)
            ->assertSee('Skolklass 7B', false)
            ->assertDontSee('Lediga platser', false)
            ->assertDontSee('Beläggning', false)
            ->assertSeeInOrder([
                'guide-tour-header',
                'booking-mobile-section',
                'guide-tour-status-panel',
            ], false)
            ->assertSeeInOrder([
                'Bokningar',
                'Turstatus',
            ], false);
    }

    public function test_guide_ajax_complete_on_finished_tour_is_idempotent(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $tour->update(['status' => 'completed', 'ended_at' => now()]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.complete', $tour), [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Turen är redan avslutad.');
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
            'title' => 'Guide tur optimistic UI',
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
