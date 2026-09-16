<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class DeployCheckTest extends TestCase
{
    public function test_admin_can_view_deploy_check_json(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.deploy-check'))
            ->assertOk()
            ->assertJsonStructure([
                'routes_cached',
                'daily_guide_route_registered',
                'controller_class_exists',
                'view_exists',
                'files',
                'next_steps',
            ])
            ->assertJson([
                'daily_guide_route_registered' => true,
                'controller_class_exists' => true,
                'view_exists' => true,
            ]);
    }

    public function test_guest_cannot_view_deploy_check(): void
    {
        $this->get(route('admin.deploy-check'))
            ->assertRedirect();
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
