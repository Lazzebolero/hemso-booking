<?php

namespace Tests\Feature;

use App\Models\DailyGuideOrder;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkShift;
use App\Support\Roles;
use Tests\TestCase;

class DailyGuideOrderTest extends TestCase
{
    public function test_admin_can_view_daily_guide_orders_page(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.daily-guide-orders.index'))
            ->assertOk()
            ->assertSee('Dagens guider', false);
    }

    public function test_orders_are_initialized_from_guide_shifts_by_start_time(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        $earlyGuide = $this->userWithRole(Roles::GUIDE, 'Tidig Guide');
        $lateGuide = $this->userWithRole(Roles::GUIDE, 'Sen Guide');

        WorkShift::query()->create([
            'user_id' => $lateGuide->id,
            'shift_date' => $date,
            'start_time' => '09:00:00',
            'shift_role' => Roles::GUIDE,
            'status' => 'planned',
        ]);

        WorkShift::query()->create([
            'user_id' => $earlyGuide->id,
            'shift_date' => $date,
            'start_time' => '08:00:00',
            'shift_role' => Roles::GUIDE,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.daily-guide-orders.index', ['date' => $date]))
            ->assertOk()
            ->assertSee('Tidig Guide', false)
            ->assertSee('Sen Guide', false);

        $earlyOrder = DailyGuideOrder::query()
            ->forDate($date)
            ->where('user_id', $earlyGuide->id)
            ->first();

        $lateOrder = DailyGuideOrder::query()
            ->forDate($date)
            ->where('user_id', $lateGuide->id)
            ->first();

        $this->assertNotNull($earlyOrder);
        $this->assertSame(1, $earlyOrder->sort_order);
        $this->assertSame(DailyGuideOrder::SOURCE_SCHEDULE, $earlyOrder->source);

        $this->assertNotNull($lateOrder);
        $this->assertSame(2, $lateOrder->sort_order);
        $this->assertSame(DailyGuideOrder::SOURCE_SCHEDULE, $lateOrder->source);
    }

    public function test_admin_can_add_manual_guide_and_move_order(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        $firstGuide = $this->userWithRole(Roles::GUIDE, 'Guide Ett');
        $secondGuide = $this->userWithRole(Roles::GUIDE, 'Guide Två');

        DailyGuideOrder::query()->create([
            'guide_date' => $date,
            'user_id' => $firstGuide->id,
            'sort_order' => 1,
            'source' => DailyGuideOrder::SOURCE_SCHEDULE,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.daily-guide-orders.store'), [
                'date' => $date,
                'user_id' => $secondGuide->id,
            ])
            ->assertRedirect(route('admin.daily-guide-orders.index', ['date' => $date]))
            ->assertSessionHas('success');

        $manualOrder = DailyGuideOrder::query()
            ->forDate($date)
            ->where('user_id', $secondGuide->id)
            ->first();

        $this->assertNotNull($manualOrder);
        $this->assertSame(2, $manualOrder->sort_order);
        $this->assertSame(DailyGuideOrder::SOURCE_MANUAL, $manualOrder->source);

        $secondOrder = DailyGuideOrder::query()
            ->forDate($date)
            ->where('user_id', $secondGuide->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.daily-guide-orders.move', $secondOrder), [
                'direction' => 'up',
            ])
            ->assertRedirect(route('admin.daily-guide-orders.index', ['date' => $date]));

        $this->assertSame(1, DailyGuideOrder::query()->forDate($date)->where('user_id', $secondGuide->id)->value('sort_order'));
        $this->assertSame(2, DailyGuideOrder::query()->forDate($date)->where('user_id', $firstGuide->id)->value('sort_order'));
    }

    public function test_sync_from_schedule_keeps_manual_guides_after_scheduled_ones(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $date = now()->toDateString();

        $scheduledGuide = $this->userWithRole(Roles::GUIDE, 'Schemaguide');
        $manualGuide = $this->userWithRole(Roles::GUIDE, 'Manuell Guide');

        WorkShift::query()->create([
            'user_id' => $scheduledGuide->id,
            'shift_date' => $date,
            'start_time' => '08:00:00',
            'shift_role' => Roles::GUIDE,
            'status' => 'planned',
        ]);

        DailyGuideOrder::query()->create([
            'guide_date' => $date,
            'user_id' => $manualGuide->id,
            'sort_order' => 1,
            'source' => DailyGuideOrder::SOURCE_MANUAL,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.daily-guide-orders.sync-schedule'), [
                'date' => $date,
            ])
            ->assertRedirect(route('admin.daily-guide-orders.index', ['date' => $date]));

        $this->assertSame(1, DailyGuideOrder::query()->forDate($date)->where('user_id', $scheduledGuide->id)->value('sort_order'));
        $this->assertSame(DailyGuideOrder::SOURCE_SCHEDULE, DailyGuideOrder::query()->forDate($date)->where('user_id', $scheduledGuide->id)->value('source'));
        $this->assertSame(2, DailyGuideOrder::query()->forDate($date)->where('user_id', $manualGuide->id)->value('sort_order'));
        $this->assertSame(DailyGuideOrder::SOURCE_MANUAL, DailyGuideOrder::query()->forDate($date)->where('user_id', $manualGuide->id)->value('source'));
    }

    public function test_host_can_view_daily_guide_orders_page(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.daily-guide-orders.index'))
            ->assertOk()
            ->assertSee('Dagens guider', false);
    }

    private function userWithRole(string $roleSlug, ?string $name = null): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create($name ? ['name' => $name] : []);
        $user->assignRoles([$role]);

        return $user;
    }
}
