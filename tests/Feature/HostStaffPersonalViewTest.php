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
            ->assertDontSee('Administrera bokningar', false)
            ->assertDontSee('Boknings- och guidesystem', false);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('staff.schedule'))
            ->assertOk()
            ->assertDontSee('Administrera bokningar', false);
    }
}
