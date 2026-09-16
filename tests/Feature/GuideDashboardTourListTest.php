<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class GuideDashboardTourListTest extends TestCase
{
    public function test_guide_dashboard_does_not_show_completed_tours_or_dagens_turer_section(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $completedType = TourType::query()->create(['name' => 'Avslutad turtyp dashboard']);
        $plannedType = TourType::query()->create(['name' => 'Planerad turtyp dashboard']);
        $date = now()->toDateString();

        Tour::query()->create([
            'title' => 'Avslutad tur idag',
            'tour_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_participants' => 20,
            'status' => 'completed',
            'guide_id' => $guide->id,
            'tour_type_id' => $completedType->id,
        ]);

        Tour::query()->create([
            'title' => 'Planerad tur idag',
            'tour_date' => $date,
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $plannedType->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Nästa tur', false)
            ->assertSee('Kommande turer', false)
            ->assertSee('Planerad turtyp dashboard', false)
            ->assertDontSee('Dagens turer', false)
            ->assertDontSee('Avslutad turtyp dashboard', false);
    }

    public function test_guide_dashboard_lists_later_upcoming_tours_without_duplicating_next_tour(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $firstType = TourType::query()->create(['name' => 'Första kommande turtyp']);
        $secondType = TourType::query()->create(['name' => 'Andra kommande turtyp']);
        $date = now()->addDay()->toDateString();

        Tour::query()->create([
            'title' => 'Första kommande',
            'tour_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $firstType->id,
        ]);

        Tour::query()->create([
            'title' => 'Andra kommande',
            'tour_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $secondType->id,
        ]);

        $response = $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Första kommande turtyp', false)
            ->assertSee('Andra kommande turtyp', false);

        $this->assertSame(1, substr_count($response->getContent(), 'Första kommande turtyp'));
    }

    public function test_guide_dashboard_shows_planned_tour_after_scheduled_start_time(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Sen start turtyp']);
        $date = now()->toDateString();

        Tour::query()->create([
            'title' => 'Planerad tur med passerad starttid',
            'tour_date' => $date,
            'start_time' => now()->subMinutes(15)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Tur att starta', false)
            ->assertSee('Sen start turtyp', false)
            ->assertSee('Planerad starttid har passerat', false);
    }

    public function test_guide_dashboard_exposes_offline_tour_warm_urls_meta(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Offline warm turtyp']);
        $date = now()->addDay()->toDateString();

        $tour = Tour::query()->create([
            'title' => 'Offline warm tur',
            'tour_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('name="guide-offline-tour-urls"', false)
            ->assertSee(route('guide.tours.show', $tour), false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
