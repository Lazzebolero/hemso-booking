<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class HostDesktopLayoutTest extends TestCase
{
    public function test_host_visitor_dogs_overview_uses_desktop_sidebar_with_single_link(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.visitor-dogs.index'));

        $response->assertOk()
            ->assertSee('Besökshundar', false)
            ->assertSee(route('host.visitor-dogs.index', absolute: false), false)
            ->assertDontSee('Mobil personalvy', false)
            ->assertDontSee('Byt arbetsyta', false)
            ->assertDontSee('Mina registreringar', false)
            ->assertDontSee('restaurant-mobile-header', false);
    }

    public function test_host_time_reporting_from_topbar_uses_desktop_layout(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('time.index'));

        $response->assertOk()
            ->assertSee('Tidrapportering', false)
            ->assertSee('topbar', false)
            ->assertDontSee('restaurant-mobile-header', false);
    }
}
