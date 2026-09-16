<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Carbon\Carbon;
use Tests\TestCase;

class StaffScheduleMultiRoleTest extends TestCase
{
    public function test_staff_schedule_shows_all_roles_for_user(): void
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $restaurantRole = Role::query()->where('slug', Roles::RESTAURANT)->firstOrFail();

        $user = User::factory()->create();
        $user->assignRoles([$guideRole, $restaurantRole]);

        $date = '2026-07-03';
        $startOfWeek = Carbon::parse($date)->startOfWeek();

        WorkShift::query()->create([
            'user_id' => $user->id,
            'shift_date' => $startOfWeek->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'shift_role' => Roles::GUIDE,
            'status' => 'planned',
        ]);

        WorkShift::query()->create([
            'user_id' => $user->id,
            'shift_date' => $startOfWeek->copy()->addDay()->toDateString(),
            'start_time' => '13:00',
            'end_time' => '17:00',
            'shift_role' => Roles::RESTAURANT,
            'shift_function' => 'kassa',
            'status' => 'planned',
        ]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('staff.schedule', ['date' => $date]))
            ->assertOk()
            ->assertSee('Guide', false)
            ->assertSee('Restaurang', false)
            ->assertSee('09:00', false)
            ->assertSee('13:00', false)
            ->assertSee('Kassa', false);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('staff.schedule', ['date' => $date]))
            ->assertOk()
            ->assertSee('Guide', false)
            ->assertSee('Restaurang', false);
    }
}
