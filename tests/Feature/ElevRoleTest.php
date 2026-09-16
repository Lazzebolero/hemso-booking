<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class ElevRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_elev_user_cannot_log_in(): void
    {
        $elev = $this->userWithRole(Roles::ELEV);

        $this->post(route('login'), [
            'email' => $elev->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_admin_can_create_schedule_only_elev_without_password(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.users.store'), [
                'name' => 'Ny trainee',
                'email' => 'trainee@hemso.test',
                'roles' => [Roles::ELEV],
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'trainee@hemso.test')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->isElev());
        $this->assertTrue($user->isScheduleOnlyUser());
        $this->assertFalse($user->is_active);
        $this->assertSame([], $user->loginRoleSlugs());
    }

    public function test_elev_can_be_scheduled_on_work_shift(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $elev = $this->userWithRole(Roles::ELEV);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.work-shifts.store'), [
                'user_id' => $elev->id,
                'shift_date' => now()->addDay()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '17:00',
                'shift_role' => Roles::ELEV,
                'status' => 'planned',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('work_shifts', [
            'user_id' => $elev->id,
            'shift_role' => Roles::ELEV,
        ]);
    }

    public function test_work_shift_form_includes_elev_role(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.work-shifts.index'))
            ->assertOk()
            ->assertSee('Trainee / elev', false)
            ->assertSee('value="elev"', false);
    }

    public function test_admin_can_promote_elev_to_guide_on_user_page(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $elev = $this->userWithRole(Roles::ELEV);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.users.update', $elev), [
                'name' => $elev->name,
                'email' => $elev->email,
                'roles' => [Roles::ELEV, Roles::GUIDE],
                'is_active' => '1',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertRedirect(route('admin.users.index'));

        $elev->refresh();

        $this->assertTrue($elev->hasRole(Roles::GUIDE));
        $this->assertTrue($elev->hasRole(Roles::ELEV));
        $this->assertTrue($elev->canUseApplication());
        $this->assertContains(Roles::GUIDE, $elev->loginRoleSlugs());
    }

    public function test_existing_elev_shift_stays_after_promotion_to_guide(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $elev = $this->userWithRole(Roles::ELEV);

        WorkShift::query()->create([
            'user_id' => $elev->id,
            'shift_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'shift_role' => Roles::ELEV,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.users.update', $elev), [
                'name' => $elev->name,
                'email' => $elev->email,
                'roles' => [Roles::GUIDE],
                'is_active' => '1',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('work_shifts', [
            'user_id' => $elev->id,
            'shift_role' => Roles::ELEV,
        ]);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create([
            'is_active' => $roleSlug !== Roles::ELEV,
        ]);
        $user->assignRoles([$role]);

        return $user;
    }
}
