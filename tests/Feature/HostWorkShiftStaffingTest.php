<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HostWorkShiftStaffingTest extends TestCase
{
    public function test_host_can_open_staffing_and_sees_no_planning_actions(): void
    {
        if (! Schema::hasTable('work_shifts')) {
            $this->markTestSkipped('work_shifts-tabellen saknas.');
        }

        $this->assertTrue(Route::has('host.work-shifts.staffing'));

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.work-shifts.staffing', ['date' => '2000-01-01']))
            ->assertOk()
            ->assertSee('Dagens personal', false)
            ->assertDontSee('Entrévärd · Personalvy', false)
            ->assertDontSee('Dagvy', false)
            ->assertDontSee('Personvy', false)
            ->assertDontSee('Redigera', false);
    }
}
