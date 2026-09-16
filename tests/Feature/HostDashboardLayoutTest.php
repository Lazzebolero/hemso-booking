<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HostDashboardLayoutTest extends TestCase
{
    public function test_host_tour_update_redirects_to_dashboard(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $tour = Tour::query()->create([
            'title' => 'Värd tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->put(route('host.tours.update', $tour), [
                'title' => 'Uppdaterad värd tur',
                'tour_date' => $tour->tour_date->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'max_participants' => 20,
                'status' => 'planned',
            ])
            ->assertRedirect(route('host.dashboard'))
            ->assertSessionHas('success');
    }

    public function test_host_dashboard_includes_mobile_nav_for_app_with_desktop_sidebar_on_large_screens(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee('Bokningssekvens', false)
            ->assertSee('Snabbtur', false)
            ->assertSee('Ny tur', false)
            ->assertDontSee('Ny bokning', false)
            ->assertSee('restaurant-mobile-header d-lg-none', false)
            ->assertSee('Entrévärd · Bokning', false);
    }

    public function test_host_only_user_sees_dashboard_quick_actions_with_registered_routes(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->assertTrue(Route::has('host.dashboard'));
        $this->assertTrue(Route::has('host.tours.create'));
        $this->assertTrue(Route::has('host.bookings.quick-create'));
        $this->assertTrue(Route::has('quick-tours.create'));

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee(route('host.bookings.quick-create'), false)
            ->assertSee(route('host.tours.create'), false)
            ->assertSee(route('quick-tours.create'), false);
    }
}
