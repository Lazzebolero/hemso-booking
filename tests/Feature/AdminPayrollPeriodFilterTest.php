<?php

namespace Tests\Feature;

use App\Exports\TimeEntriesPeriodExport;
use App\Models\Role;
use App\Models\TimeEntry;
use App\Models\User;
use App\Services\PayrollPeriodService;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AdminPayrollPeriodFilterTest extends TestCase
{
    private function actingAdmin(): User
    {
        $role = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    public function test_custom_period_with_end_before_start_redirects_to_time_index_with_errors(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.index', [
                'period' => 'custom',
                'from' => '2024-06-10',
                'to' => '2024-06-01',
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('from');
    }

    public function test_custom_period_longer_than_400_days_is_rejected(): void
    {
        $admin = $this->actingAdmin();
        $from = Carbon::parse('2024-01-01')->toDateString();
        $to = Carbon::parse('2024-01-01')->addDays(400)->toDateString();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.index', [
                'period' => 'custom',
                'from' => $from,
                'to' => $to,
            ]))
            ->assertRedirect()
            ->assertSessionHasErrors('to');
    }

    public function test_invalid_user_id_filter_redirects_with_errors(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.index', ['user_id' => 999_999_999]))
            ->assertRedirect()
            ->assertSessionHasErrors('user_id');
    }

    public function test_excel_export_respects_user_filter(): void
    {
        Excel::fake();

        $admin = $this->actingAdmin();
        $other = User::factory()->create();
        $from = Carbon::parse('2024-03-21')->startOfDay();
        $to = Carbon::parse('2024-04-20')->endOfDay();

        TimeEntry::factory()->approved()->create([
            'user_id' => $admin->id,
            'work_date' => '2024-04-01',
            'start_at' => Carbon::parse('2024-04-01 08:00'),
            'end_at' => Carbon::parse('2024-04-01 10:00'),
        ]);

        TimeEntry::factory()->approved()->create([
            'user_id' => $other->id,
            'work_date' => '2024-04-02',
            'start_at' => Carbon::parse('2024-04-02 08:00'),
            'end_at' => Carbon::parse('2024-04-02 10:00'),
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.time.export', [
                'period' => 'custom',
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'user_id' => $admin->id,
            ]))
            ->assertOk();

        $expectedFilename = 'tidrapport_'.PayrollPeriodService::custom(
            $from->toDateString(),
            $to->toDateString()
        )['file_label'].'.xlsx';

        Excel::assertDownloaded($expectedFilename);
    }

    public function test_time_entries_period_export_detail_sheet_respects_user_filter(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $from = Carbon::parse('2024-03-21')->startOfDay();
        $to = Carbon::parse('2024-04-20')->endOfDay();

        TimeEntry::factory()->approved()->create([
            'user_id' => $userA->id,
            'work_date' => '2024-04-01',
            'start_at' => Carbon::parse('2024-04-01 08:00'),
            'end_at' => Carbon::parse('2024-04-01 10:00'),
        ]);

        TimeEntry::factory()->approved()->create([
            'user_id' => $userB->id,
            'work_date' => '2024-04-02',
            'start_at' => Carbon::parse('2024-04-02 08:00'),
            'end_at' => Carbon::parse('2024-04-02 10:00'),
        ]);

        $export = new TimeEntriesPeriodExport($from, $to, $userA->id, null);
        $sheets = $export->sheets();
        $detailRows = $sheets[0]->collection();

        $this->assertCount(1, $detailRows);
        $this->assertSame($userA->name, $detailRows->first()[0]);
    }
}
