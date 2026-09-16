<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class TourListingMealBadgeTest extends TestCase
{
    public function test_admin_tour_index_shows_meal_badge_for_tour_with_meal(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $tour = Tour::query()->create([
            'title' => 'Matmärkt tur i listan',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'max_participants' => 20,
            'status' => 'planned',
            'default_includes_meal' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index'))
            ->assertOk()
            ->assertSee($tour->title, false)
            ->assertSee('Med mat', false)
            ->assertSee('tour-meal-badge', false);
    }

    public function test_admin_tour_index_hides_meal_badge_when_tour_has_no_meal(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Tur utan mat i listan',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '15:00',
            'end_time' => '16:00',
            'max_participants' => 20,
            'status' => 'planned',
            'default_includes_meal' => false,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index'));

        $response->assertOk()
            ->assertSee('Tur utan mat i listan', false)
            ->assertDontSee('Med mat', false);
    }

    public function test_admin_dashboard_shows_meal_badge_for_today_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Dagens matmärkta tur',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
            'default_includes_meal' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dagens matmärkta tur', false)
            ->assertSee('Med mat', false);
    }

    public function test_guide_dashboard_shows_meal_badge_for_assigned_today_tour(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Guide matmärkt tur']);

        Tour::query()->create([
            'title' => 'Guide matmärkt tur',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
            'default_includes_meal' => true,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Guide matmärkt tur', false)
            ->assertSee('Med mat', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
