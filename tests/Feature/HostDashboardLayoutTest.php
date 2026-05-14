<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class HostDashboardLayoutTest extends TestCase
{
    public function test_host_dashboard_does_not_include_personal_mobile_nav_strip(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertDontSee('Entrévärd · Personalvy', false);
    }
}
