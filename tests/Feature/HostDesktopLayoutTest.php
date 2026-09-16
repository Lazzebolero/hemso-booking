<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class HostDesktopLayoutTest extends TestCase
{
    public function test_host_visitor_dogs_overview_includes_mobile_nav_on_small_screens(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.visitor-dogs.index'));

        $response->assertOk()
            ->assertSee('Besökshundar', false)
            ->assertSee(route('host.visitor-dogs.index'), false)
            ->assertSee('restaurant-mobile-header d-lg-none', false)
            ->assertSee('Entrévärd · Bokning', false);
    }

    public function test_host_time_reporting_uses_staff_mobile_shell_not_desktop_sidebar(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('time.index'));

        $response->assertOk()
            ->assertSee('Tidrapportering', false)
            ->assertSee('restaurant-mobile-header', false)
            ->assertDontSee('restaurant-mobile-header d-lg-none', false)
            ->assertDontSee('Boknings- och guidesystem', false);
    }
}
