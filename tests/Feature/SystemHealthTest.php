<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SystemHealthTest extends TestCase
{
    public function test_admin_can_view_system_health_page(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.system-health.index'))
            ->assertOk()
            ->assertSee('staff-page-stack', false)
            ->assertSee('Systemhälsa', false)
            ->assertSee('Migrationer', false)
            ->assertSee('visitor_dogs', false)
            ->assertSee('HTTP /up', false)
            ->assertSee('Jobbkö', false)
            ->assertSee('deploy:smoke', false)
            ->assertSee('Uppdatera', false)
            ->assertSee('href="/admin/system-logs"', false)
            ->assertDontSee('href="http://localhost/admin', false)
            ->assertDontSee('action="http://localhost/logout"', false)
            ->assertSee('Miljö &amp; version', false)
            ->assertSee('health-card-summary', false)
            ->assertSee('health-detail-table', false);
    }

    public function test_host_cannot_access_system_health(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get('/host/system-health')
            ->assertNotFound();
    }

    public function test_system_health_route_is_not_registered_for_host(): void
    {
        $this->assertFalse(Route::has('host.system-health.index'));
    }
}
