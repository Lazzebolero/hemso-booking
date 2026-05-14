<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\ActiveRoleRedirect;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HostEntryFlowTest extends TestCase
{
    public function test_host_active_role_redirect_points_to_entry_screen(): void
    {
        $this->assertSame('host.entry', ActiveRoleRedirect::routeNameFor(Roles::HOST));
    }

    public function test_active_role_redirect_always_resolves_to_registered_route(): void
    {
        foreach ([Roles::ADMIN, Roles::HOST, Roles::GUIDE, Roles::RESTAURANT, Roles::RESTAURANT_STATISTIK] as $slug) {
            $name = ActiveRoleRedirect::routeNameFor($slug);
            $this->assertTrue(Route::has($name), "Saknar rutt \"{$name}\" för roll {$slug}.");
        }
    }

    public function test_host_entry_page_ok_for_host_user(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.entry'))
            ->assertOk()
            ->assertSee('Mobil personalvy', false)
            ->assertSee('Bokning och turer', false);
    }

    public function test_central_dashboard_redirects_host_to_entry(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('dashboard'))
            ->assertRedirect(route('host.entry'));
    }

    public function test_role_select_auto_redirects_single_host_to_entry(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->get(route('role.select'))
            ->assertRedirect(route('host.entry'))
            ->assertSessionHas('active_role', Roles::HOST);
    }

    public function test_host_entry_forbidden_when_active_role_is_admin(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('host.entry'))
            ->assertForbidden();
    }
}
