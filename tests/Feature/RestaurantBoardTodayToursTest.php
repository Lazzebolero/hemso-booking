<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class RestaurantBoardTodayToursTest extends TestCase
{
    public function test_admin_restaurant_board_lists_todays_tours(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Morgonvisning restaurang',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:30:00',
            'end_time' => '11:50:00',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board'))
            ->assertOk()
            ->assertSee('Dagens turer', false)
            ->assertSee($tour->title, false)
            ->assertSee('Planerad', false);
    }

    public function test_admin_restaurant_board_kiosk_lists_todays_tours(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Kioskvisning idag',
            'tour_date' => now()->toDateString(),
            'start_time' => '14:00:00',
            'end_time' => '15:20:00',
            'max_participants' => 25,
            'status' => 'started',
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board.kiosk'))
            ->assertOk()
            ->assertSee('Dagens turer', false)
            ->assertSee($tour->title, false)
            ->assertSee('Startad', false);
    }

    public function test_admin_restaurant_board_shows_upcoming_tours_for_next_seven_days(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $futureTour = Tour::query()->create([
            'title' => 'Tur med mat om tre dagar',
            'tour_date' => now()->addDays(3)->toDateString(),
            'start_time' => '12:00:00',
            'end_time' => '13:20:00',
            'max_participants' => 20,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
            'default_includes_meal' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board', ['ahead_days' => 7]))
            ->assertOk()
            ->assertSee('Kommande turer idag', false)
            ->assertSee('7 dagar', false)
            ->assertSee('30 dagar', false)
            ->assertSee($futureTour->title, false)
            ->assertSee('Med mat', false);
    }

    public function test_admin_restaurant_board_kiosk_respects_thirty_day_upcoming_filter(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $farTour = Tour::query()->create([
            'title' => 'Tur om tre veckor',
            'tour_date' => now()->addDays(20)->toDateString(),
            'start_time' => '11:00:00',
            'end_time' => '12:20:00',
            'max_participants' => 18,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board.kiosk', ['ahead_days' => 30]))
            ->assertOk()
            ->assertSee($farTour->title, false)
            ->assertSee('30 dagar', false);
    }

    public function test_admin_restaurant_board_shows_ferry_status_card(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board'))
            ->assertOk()
            ->assertSee('Hemsöleden · Strinningen', false)
            ->assertSee('Senast avgått', false)
            ->assertSee('Nästa avgång', false)
            ->assertSee(route('admin.restaurant-board.ferry-timetable'), false);
    }

    public function test_admin_restaurant_board_kiosk_shows_ferry_status_card(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board.kiosk'))
            ->assertOk()
            ->assertSee('Hemsöleden · Strinningen', false)
            ->assertSee('Senast avgått', false)
            ->assertSee('Nästa avgång', false)
            ->assertSee(route('admin.restaurant-board.ferry-timetable'), false);
    }

    public function test_admin_restaurant_board_ferry_timetable_page_is_accessible(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.restaurant-board.ferry-timetable'))
            ->assertOk()
            ->assertSee('Färjetidtabell', false)
            ->assertSee('Planerad tidtabell', false)
            ->assertSee('Dagens avgångar med live-status', false)
            ->assertSee(route('admin.restaurant-board.kiosk'), false);
    }

    public function test_restaurant_statistics_ferry_timetable_does_not_require_role_system(): void
    {
        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.ferry-timetable'))
            ->assertOk()
            ->assertSee('Färjetidtabell', false)
            ->assertSee(route('restaurant-statistics.dashboard'), false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
