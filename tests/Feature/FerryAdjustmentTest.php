<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class FerryAdjustmentTest extends TestCase
{
    public function test_admin_can_view_ferry_adjustment_page(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.ferry-adjustments.index'))
            ->assertOk()
            ->assertSee('Färjekorrigering', false)
            ->assertSee('Justera starttider', false);
    }

    public function test_admin_can_shift_planned_tour_forward_by_ten_minutes(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        $tour = Tour::query()->create([
            'title' => 'Morgontur',
            'tour_date' => $date,
            'start_time' => '11:00:00',
            'end_time' => '12:20:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.ferry-adjustments.shift', $tour), [
                'date' => $date,
                'minutes' => 10,
            ])
            ->assertRedirect(route('admin.ferry-adjustments.index', ['date' => $date]))
            ->assertSessionHas('success');

        $tour->refresh();

        $this->assertSame('11:10:00', $tour->start_time);
        $this->assertSame('12:30:00', $tour->end_time);
        $this->assertSame('11:00:00', $tour->original_start_time);
        $this->assertSame('12:20:00', $tour->original_end_time);
        $this->assertNotNull($tour->ferry_adjusted_at);

        $this->assertDatabaseHas('activity_logs', [
            'entity_type' => 'tour',
            'entity_id' => $tour->id,
            'action' => 'ferry_adjusted',
        ]);
    }

    public function test_admin_can_shift_tour_back_and_reset_to_original_time(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        $tour = Tour::query()->create([
            'title' => 'Eftermiddagstur',
            'tour_date' => $date,
            'start_time' => '13:30:00',
            'end_time' => '14:50:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.ferry-adjustments.shift', $tour), [
                'date' => $date,
                'minutes' => 10,
            ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.ferry-adjustments.shift', $tour), [
                'date' => $date,
                'minutes' => -10,
            ]);

        $tour->refresh();

        $this->assertSame('13:30:00', $tour->start_time);
        $this->assertSame('14:50:00', $tour->end_time);
        $this->assertSame('13:30:00', $tour->original_start_time);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.ferry-adjustments.reset', $tour), [
                'date' => $date,
            ])
            ->assertRedirect(route('admin.ferry-adjustments.index', ['date' => $date]));

        $tour->refresh();

        $this->assertSame('13:30:00', $tour->start_time);
        $this->assertSame('14:50:00', $tour->end_time);
        $this->assertNull($tour->original_start_time);
        $this->assertNull($tour->original_end_time);
        $this->assertNull($tour->ferry_adjusted_at);

        $this->assertSame(1, ActivityLog::query()
            ->where('entity_type', 'tour')
            ->where('entity_id', $tour->id)
            ->where('action', 'ferry_reset')
            ->count());
    }

    public function test_started_tour_cannot_be_ferry_adjusted(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        $tour = Tour::query()->create([
            'title' => 'Pågående tur',
            'tour_date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '11:20:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.ferry-adjustments.shift', $tour), [
                'date' => $date,
                'minutes' => 10,
            ])
            ->assertRedirect(route('admin.ferry-adjustments.index', ['date' => $date]))
            ->assertSessionHasErrors('tour');

        $tour->refresh();

        $this->assertSame('10:00:00', $tour->start_time);
        $this->assertNull($tour->original_start_time);
    }

    public function test_host_can_use_ferry_adjustment_routes(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $date = now()->toDateString();

        $tour = Tour::query()->create([
            'title' => 'Värdtest',
            'tour_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:20:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.ferry-adjustments.index'))
            ->assertOk();

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('host.ferry-adjustments.shift', $tour), [
                'date' => $date,
                'minutes' => 10,
            ])
            ->assertRedirect(route('host.ferry-adjustments.index', ['date' => $date]));

        $this->assertSame('09:10:00', $tour->fresh()->start_time);
    }

    public function test_dashboard_shows_ferry_adjusted_badge(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        Tour::query()->create([
            'title' => 'Korrigerad tur',
            'tour_date' => $date,
            'start_time' => '11:10:00',
            'end_time' => '12:30:00',
            'original_start_time' => '11:00:00',
            'original_end_time' => '12:20:00',
            'ferry_adjusted_at' => now(),
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Färjekorrigerad', false);
    }

    private function userWithRole(string $roleSlug, ?string $name = null): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create($name ? ['name' => $name] : []);
        $user->assignRoles([$role]);

        return $user;
    }
}
