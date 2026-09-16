<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\GuideShell;
use App\Support\Roles;
use Tests\TestCase;

class GuideShellNavigationTest extends TestCase
{
    public function test_multi_role_user_keeps_guide_shell_when_opening_messages_from_guide_app(): void
    {
        $user = $this->userWithRoles([Roles::ADMIN, Roles::GUIDE]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('guide-dashboard', false);

        $this->assertSame(Roles::GUIDE, session('active_role'));
        $this->assertTrue(GuideShell::isActive());
        $this->assertSame('layouts.guide', GuideShell::layoutView());
    }

    public function test_admin_workspace_clears_guide_shell(): void
    {
        $user = $this->userWithRoles([Roles::ADMIN, Roles::GUIDE]);

        $this->actingAs($user)
            ->withSession([
                'active_role' => Roles::ADMIN,
                GuideShell::SESSION_KEY => true,
            ])
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertFalse(GuideShell::isActive());
    }

    /**
     * @param  list<string>  $roleSlugs
     */
    private function userWithRoles(array $roleSlugs): User
    {
        $roles = Role::query()->whereIn('slug', $roleSlugs)->get();
        $user = User::factory()->create();
        $user->assignRoles($roles->all());

        return $user;
    }
}
