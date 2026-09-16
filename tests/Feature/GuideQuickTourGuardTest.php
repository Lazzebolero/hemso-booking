<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GuideQuickTourGuardTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guide_cannot_start_quick_tour_when_assigned_tour_is_due(): void
    {
        Carbon::setTestNow('2026-06-17 13:10:00');

        $guide = $this->guideUser();
        $this->assignedTour($guide, '13:10:00', '14:25:00');

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 5,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quick_tour');

        $this->assertSame(0, Tour::query()->where('title', 'like', 'Snabbtur%')->count());
    }

    public function test_guide_cannot_start_quick_tour_when_upcoming_tour_would_overlap(): void
    {
        Carbon::setTestNow('2026-06-17 12:00:00');

        $guide = $this->guideUser();
        $this->assignedTour($guide, '13:10:00', '14:25:00');

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 4,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quick_tour');

        $this->assertSame(0, Tour::query()->where('title', 'like', 'Snabbtur%')->count());
    }

    public function test_guide_can_start_quick_tour_when_next_assigned_tour_is_later(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $guide = $this->guideUser();
        $this->assignedTour($guide, '15:00:00', '16:15:00');

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 3,
            ])
            ->assertRedirect(route('guide.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame(1, Tour::query()->where('title', 'like', 'Snabbtur%')->count());
    }

    public function test_guide_cannot_start_quick_tour_when_already_on_ongoing_tour(): void
    {
        Carbon::setTestNow('2026-06-17 13:30:00');

        $guide = $this->guideUser();

        Tour::query()->create([
            'title' => 'Pågående tur',
            'tour_date' => '2026-06-17',
            'start_time' => '13:00:00',
            'end_time' => '14:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now(),
            'guide_id' => $guide->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quick_tour');
    }

    public function test_quick_tour_create_page_shows_block_message_for_due_assigned_tour(): void
    {
        Carbon::setTestNow('2026-06-17 13:10:00');

        $guide = $this->guideUser();
        $tour = $this->assignedTour($guide, '13:10:00', '14:25:00');

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('quick-tours.create'))
            ->assertOk()
            ->assertSee('planerad tur', false)
            ->assertSee('13:10', false)
            ->assertSee(route('guide.tours.show', $tour), false);
    }

    private function guideUser(): User
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $guide = User::factory()->create(['name' => 'Testguide']);
        $guide->assignRoles([$guideRole]);

        return $guide;
    }

    private function assignedTour(User $guide, string $startTime, string $endTime): Tour
    {
        return Tour::query()->create([
            'title' => 'Planerad tur',
            'tour_date' => now()->toDateString(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $guide->id,
        ]);
    }
}
