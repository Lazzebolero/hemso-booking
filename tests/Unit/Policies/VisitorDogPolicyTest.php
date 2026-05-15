<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;
use Tests\TestCase;

class VisitorDogPolicyTest extends TestCase
{
    public function test_guide_can_view_and_update_own_registration(): void
    {
        session(['active_role' => Roles::GUIDE]);

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'registered_by' => $user->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $this->assertTrue($user->can('view', $dog));
        $this->assertTrue($user->can('update', $dog));
        $this->assertTrue($user->can('delete', $dog));
    }

    public function test_guide_cannot_view_another_users_registration(): void
    {
        session(['active_role' => Roles::GUIDE]);

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $other = User::factory()->create();
        $other->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'registered_by' => $other->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $this->assertFalse($user->can('view', $dog));
        $this->assertFalse($user->can('update', $dog));
    }

    public function test_admin_can_manage_any_registration_when_active_as_admin(): void
    {
        session(['active_role' => Roles::ADMIN]);

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $dog = VisitorDog::factory()->create();

        $this->assertTrue($admin->can('viewAny', VisitorDog::class));
        $this->assertTrue($admin->can('view', $dog));
        $this->assertTrue($admin->can('delete', $dog));
    }

    public function test_host_can_manage_registrations_when_active_as_host(): void
    {
        session(['active_role' => Roles::HOST]);

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $host = User::factory()->create();
        $host->assignRoles([$hostRole]);

        $dog = VisitorDog::factory()->create([
            'registered_by' => User::factory()->create()->id,
        ]);

        $this->assertTrue($host->can('update', $dog));
    }
}
