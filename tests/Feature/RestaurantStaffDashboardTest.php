<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\ActiveRoleRedirect;
use App\Support\Roles;
use Tests\TestCase;

class RestaurantStaffDashboardTest extends TestCase
{
    public function test_restaurant_active_role_redirect_points_to_staff_dashboard(): void
    {
        $this->assertSame('staff.dashboard', ActiveRoleRedirect::routeNameFor(Roles::RESTAURANT));
    }

    public function test_restaurant_staff_mobile_dashboard_shows_tours_not_shifts(): void
    {
        $restaurantRole = Role::query()->where('slug', Roles::RESTAURANT)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$restaurantRole]);

        $today = now()->toDateString();

        $ongoingTour = Tour::query()->create([
            'title' => 'Morgontur under jord',
            'tour_date' => $today,
            'start_time' => '09:00',
            'end_time' => '10:15',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now()->subMinutes(25),
        ]);

        Booking::query()->create([
            'tour_id' => $ongoingTour->id,
            'booking_name' => 'Grupp A',
            'men_count' => 8,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 8,
            'status' => 'confirmed',
        ]);

        $upcomingTour = Tour::query()->create([
            'title' => 'Eftermiddagstur',
            'tour_date' => $today,
            'start_time' => now()->addHours(2)->format('H:i'),
            'end_time' => now()->addHours(3)->format('H:i'),
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $upcomingTour->id,
            'booking_name' => 'Grupp B',
            'men_count' => 5,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 5,
            'status' => 'confirmed',
        ]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Restaurang · Personalvy', false)
            ->assertSee('restaurant-mobile-header', false)
            ->assertDontSee('restaurant-mobile-header d-lg-none', false)
            ->assertDontSee('Boknings- och guidesystem', false)
            ->assertDontSee('Administrera bokningar', false)
            ->assertSee('Pågående turer', false)
            ->assertSee('Dagens kommande turer', false)
            ->assertSee('Morgontur under jord', false)
            ->assertSee('Eftermiddagstur', false)
            ->assertSee('8 pers', false)
            ->assertSee('Start ', false)
            ->assertSee('Klar ', false)
            ->assertSee('Klar om', false)
            ->assertSee('5', false)
            ->assertSee(' pers', false)
            ->assertDontSee('Dagens pass', false)
            ->assertDontSee('Kommande pass', false)
            ->assertDontSee('Öppna helskärm', false)
            ->assertDontSee('Gäster på pågående turer', false)
            ->assertSeeInOrder([
                'Pågående turer',
                'Dagens kommande turer',
            ], false);
    }

    public function test_restaurant_staff_finds_work_shifts_in_schedule_navigation(): void
    {
        $restaurantRole = Role::query()->where('slug', Roles::RESTAURANT)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$restaurantRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('staff.schedule'))
            ->assertOk()
            ->assertSee('Mitt schema', false);
    }

    public function test_host_staff_dashboard_still_shows_work_shifts(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Dagens pass', false)
            ->assertDontSee('Dagens kommande turer', false);
    }
}
