<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\LockedPayrollPeriod;
use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminPayrollLockExportLogTest extends TestCase
{
    private function actingAdmin(): User
    {
        $role = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    public function test_user_submit_is_blocked_when_work_date_is_locked(): void
    {
        $worker = User::factory()->create();

        LockedPayrollPeriod::factory()->create([
            'start_date' => '2024-04-01',
            'end_date' => '2024-04-30',
        ]);

        $entry = TimeEntry::factory()->draft()->create([
            'user_id' => $worker->id,
            'work_date' => '2024-04-10',
            'start_at' => Carbon::parse('2024-04-10 08:00'),
            'end_at' => Carbon::parse('2024-04-10 16:00'),
        ]);

        $this->actingAs($worker)
            ->patch(route('time.submit', $entry))
            ->assertForbidden();
    }

    public function test_user_clock_in_is_blocked_on_locked_work_date(): void
    {
        $worker = User::factory()->create();

        LockedPayrollPeriod::factory()->create([
            'start_date' => Carbon::now()->subDay()->toDateString(),
            'end_date' => Carbon::now()->addDay()->toDateString(),
        ]);

        $this->actingAs($worker)
            ->post(route('time.clock-in'))
            ->assertForbidden();
    }

    public function test_payroll_pdf_all_shows_confirmation_when_period_not_pdf_ready(): void
    {
        $admin = $this->actingAdmin();
        $worker = User::factory()->create();

        $start = Carbon::parse('2024-04-02 08:00');
        $end = Carbon::parse('2024-04-02 10:00');

        TimeEntry::query()->create([
            'user_id' => $worker->id,
            'work_date' => '2024-04-02',
            'clock_in_at_original' => $start,
            'clock_out_at_original' => $end,
            'start_at' => $start,
            'end_at' => $end,
            'break_minutes' => 0,
            'status' => TimeEntry::STATUS_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.payroll-pdf.all', [
                'period' => 'custom',
                'from' => '2024-03-21',
                'to' => '2024-04-20',
            ]))
            ->assertOk()
            ->assertSee('Kontrollera innan nedladdning', false);
    }

    public function test_payroll_pdf_all_with_ack_logs_activity(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.payroll-pdf.all', [
                'period' => 'custom',
                'from' => '2024-03-21',
                'to' => '2024-04-20',
                'ack' => 1,
            ]))
            ->assertOk();

        $this->assertTrue(
            ActivityLog::query()
                ->where('entity_type', 'payroll_export')
                ->where('action', 'payroll_pdf_all')
                ->exists()
        );
    }

    public function test_csv_entries_export_logs_activity(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.export.entries-csv', [
                'period' => 'custom',
                'from' => '2024-03-21',
                'to' => '2024-04-20',
            ]))
            ->assertOk();

        $this->assertTrue(
            ActivityLog::query()
                ->where('entity_type', 'payroll_export')
                ->where('action', 'payroll_csv_entries')
                ->exists()
        );
    }

    public function test_admin_can_create_and_delete_payroll_lock(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.time.payroll-locks.store'), [
                'start_date' => '2024-02-01',
                'end_date' => '2024-02-28',
            ])
            ->assertRedirect(route('admin.time.payroll-locks.index'))
            ->assertSessionHas('success');

        $lock = LockedPayrollPeriod::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.time.payroll-locks.destroy', $lock))
            ->assertRedirect(route('admin.time.payroll-locks.index'));

        $this->assertSame(0, LockedPayrollPeriod::query()->count());
    }
}
