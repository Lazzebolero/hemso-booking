<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class TimeEntryPolicyTest extends TestCase
{
    public function test_user_can_update_own_editable_entry(): void
    {
        session(['active_role' => Roles::GUIDE]);

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $entry = TimeEntry::factory()->draft()->create([
            'user_id' => $user->id,
        ]);

        $this->assertTrue($user->can('update', $entry));
        $this->assertTrue($user->can('submit', $entry));
    }

    public function test_user_cannot_update_submitted_entry(): void
    {
        session(['active_role' => Roles::GUIDE]);

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $entry = TimeEntry::factory()->create([
            'user_id' => $user->id,
            'status' => TimeEntry::STATUS_SUBMITTED,
        ]);

        $this->assertFalse($user->can('update', $entry));
        $this->assertTrue($user->can('submit', $entry));
    }

    public function test_user_cannot_access_another_users_entry(): void
    {
        session(['active_role' => Roles::GUIDE]);

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $entry = TimeEntry::factory()->draft()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertFalse($user->can('view', $entry));
        $this->assertFalse($user->can('update', $entry));
    }

    public function test_admin_can_approve_and_view_any_entry_when_active_as_admin(): void
    {
        session(['active_role' => Roles::ADMIN]);

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $entry = TimeEntry::factory()->create([
            'user_id' => User::factory()->create()->id,
            'status' => TimeEntry::STATUS_SUBMITTED,
        ]);

        $this->assertTrue($admin->can('viewAny', TimeEntry::class));
        $this->assertTrue($admin->can('view', $entry));
        $this->assertTrue($admin->can('approve', $entry));
        $this->assertTrue($admin->can('correct', $entry));
        $this->assertFalse($admin->can('update', $entry));
    }

    public function test_staff_can_clock_when_active_role_allows_time_tracking(): void
    {
        session(['active_role' => Roles::HOST]);

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $host = User::factory()->create();
        $host->assignRoles([$hostRole]);

        $this->assertTrue($host->can('clock', TimeEntry::class));
    }
}
