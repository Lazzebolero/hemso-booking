<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class RoleSelectionTest extends TestCase
{
    public function test_user_can_select_admin_role(): void
    {
        $user = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($user)
            ->post(route('role.store'), ['role' => Roles::ADMIN])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(Roles::ADMIN, session('active_role'));
    }

    public function test_user_can_select_host_role(): void
    {
        $user = $this->userWithRole(Roles::HOST);

        $this->actingAs($user)
            ->post(route('role.store'), ['role' => Roles::HOST])
            ->assertRedirect(route('host.entry'));

        $this->assertSame(Roles::HOST, session('active_role'));
    }

    public function test_selected_admin_role_can_open_dashboard(): void
    {
        $user = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_selected_host_role_can_open_entry_page(): void
    {
        $user = $this->userWithRole(Roles::HOST);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.entry'))
            ->assertOk();
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
