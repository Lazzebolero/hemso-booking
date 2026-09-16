<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class PwaSessionKeepaliveTest extends TestCase
{
    public function test_app_pulse_is_available_for_authenticated_users(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->getJson(route('app.pulse'))
            ->assertOk()
            ->assertJsonStructure([
                'server_time',
                'csrf_token',
                'urgent_messages',
                'unread_pm',
                'tours_version',
                'upcoming_tour',
            ]);
    }

    public function test_authenticated_layout_includes_session_keepalive_script(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee(route('app.pulse'), false)
            ->assertSee('js/app-pulse.js', false);
    }

    public function test_guest_layout_does_not_include_session_keepalive_script(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('js/app-pulse.js', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
