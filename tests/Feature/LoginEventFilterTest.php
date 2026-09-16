<?php

namespace Tests\Feature;

use App\Models\LoginEvent;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class LoginEventFilterTest extends TestCase
{
    public function test_login_events_page_shows_user_filter(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $otherUser = User::factory()->create(['name' => 'Anna Andersson']);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.login-events.index'))
            ->assertOk()
            ->assertSee('Användare', false)
            ->assertSee('Anna Andersson', false);
    }

    public function test_login_events_can_be_filtered_by_user(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $targetUser = User::factory()->create([
            'name' => 'Filtrerad Användare',
            'email' => 'filtrerad@example.com',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Annan Användare',
            'email' => 'annan@example.com',
        ]);

        LoginEvent::query()->create([
            'user_id' => $targetUser->id,
            'email' => $targetUser->email,
            'event_type' => 'login',
            'occurred_at' => now(),
        ]);

        LoginEvent::query()->create([
            'user_id' => $otherUser->id,
            'email' => $otherUser->email,
            'event_type' => 'login',
            'occurred_at' => now(),
        ]);

        LoginEvent::query()->create([
            'user_id' => null,
            'email' => $targetUser->email,
            'event_type' => 'failed',
            'occurred_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.login-events.index', ['user_id' => $targetUser->id]))
            ->assertOk()
            ->assertSee('Filtrerad Användare', false)
            ->assertSee('filtrerad@example.com', false)
            ->assertDontSee('annan@example.com', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
