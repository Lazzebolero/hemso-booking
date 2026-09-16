<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class HostStaffPersonalViewTest extends TestCase
{
    public function test_host_can_open_staff_personal_dashboard_and_schedule(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();

        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertSee('Entrévärd · Personalvy', false)
            ->assertSee('restaurant-mobile-header', false)
            ->assertSee(route('visitor-dogs.index'), false)
            ->assertSee(route('visitor-dogs.create'), false)
            ->assertDontSee('restaurant-mobile-header d-lg-none', false)
            ->assertDontSee('Administrera bokningar', false)
            ->assertDontSee('Boknings- och guidesystem', false);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('staff.schedule'))
            ->assertOk()
            ->assertDontSee('Administrera bokningar', false);
    }

    public function test_host_time_pages_use_staff_mobile_shell_not_desktop_sidebar(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();

        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('time.index'))
            ->assertOk()
            ->assertSee('restaurant-mobile-header', false)
            ->assertDontSee('restaurant-mobile-header d-lg-none', false)
            ->assertSee('Tidrapportering', false)
            ->assertDontSee('Boknings- och guidesystem', false)
            ->assertDontSee('Administrera bokningar', false);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('time.station', ['station' => 'entrance']))
            ->assertOk()
            ->assertSee('restaurant-mobile-header', false)
            ->assertDontSee('restaurant-mobile-header d-lg-none', false)
            ->assertDontSee('Boknings- och guidesystem', false);
    }
}
