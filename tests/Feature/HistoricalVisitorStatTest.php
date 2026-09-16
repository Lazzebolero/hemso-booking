<?php

namespace Tests\Feature;

use App\Imports\HistoricalDailyVisitorsImport;
use App\Models\Booking;
use App\Models\HistoricalDailyVisitor;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Services\HistoricalVisitorComparisonService;
use App\Services\HistoricalVisitorForecastService;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HistoricalVisitorStatTest extends TestCase
{
    private function actingAdmin(): User
    {
        $role = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    public function test_admin_can_view_historical_visitor_statistics_page(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.historical-visitors.index'))
            ->assertOk()
            ->assertSee('Historisk besöksstatistik')
            ->assertSee('Snabbval', false)
            ->assertSee('Period', false)
            ->assertSee('Jämförelse – passerade dagar')
            ->assertSee('Prognos');
    }

    public function test_admin_can_import_csv_with_datum_and_deltagare_columns(): void
    {
        $admin = $this->actingAdmin();
        $csv = "Datum,Deltagare\n2024-05-20,38\n";
        $file = UploadedFile::fake()->createWithContent('besok.csv', $csv);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.statistics.historical-visitors.import'), ['file' => $file])
            ->assertRedirect(route('admin.statistics.historical-visitors.index'))
            ->assertSessionHas('success');

        $this->assertTrue(
            HistoricalDailyVisitor::query()
                ->whereDate('stat_date', '2024-05-20')
                ->where('participant_count', 38)
                ->exists()
        );
    }

    public function test_import_reads_first_two_columns_when_headings_are_unknown(): void
    {
        $import = new HistoricalDailyVisitorsImport;
        $import->collection(collect([
            ['column1' => '2024-05-20', 'column2' => 38],
            ['column1' => '2024-05-21', 'column2' => 41],
        ]));

        $this->assertSame(2, $import->imported);
    }

    public function test_import_class_stores_iso_attributes_and_upserts(): void
    {
        $date = Carbon::parse('2024-05-20');

        $import = new HistoricalDailyVisitorsImport;
        $import->collection(collect([
            ['datum' => $date->toDateString(), 'deltagare' => 40],
            ['datum' => $date->toDateString(), 'deltagare' => 55],
        ]));

        $this->assertSame(2, $import->imported);

        $record = HistoricalDailyVisitor::query()->whereDate('stat_date', '2024-05-20')->first();

        $this->assertNotNull($record);
        $this->assertSame(55, $record->participant_count);
        $this->assertSame((int) $date->isoWeek, $record->iso_week);
        $this->assertSame((int) $date->isoWeekYear, $record->iso_week_year);
        $this->assertSame((int) $date->dayOfWeekIso, $record->iso_weekday);
    }

    public function test_import_parses_swedish_date_format(): void
    {
        $import = new HistoricalDailyVisitorsImport;
        $import->collection(collect([
            ['datum' => '20/05/2024', 'deltagare' => 12],
        ]));

        $this->assertSame(1, $import->imported);
        $this->assertTrue(
            HistoricalDailyVisitor::query()->whereDate('stat_date', '2024-05-20')->exists()
        );
    }

    public function test_historical_lookup_uses_calendar_year_and_iso_slot(): void
    {
        $date2024 = Carbon::create()->setISODate(2024, 21, 1)->startOfDay();
        $date2026 = Carbon::create()->setISODate(2026, 21, 1)->startOfDay();

        HistoricalDailyVisitor::factory()->onDate($date2024->toDateString(), 38)->create();

        $comparison = new HistoricalVisitorComparisonService;

        $this->assertSame(38, $comparison->historicalParticipantsForYear($date2026, 2024));
    }

    public function test_forecast_averages_baseline_years_only(): void
    {
        $target = Carbon::create()->setISODate(2026, 21, 1)->startOfDay();
        $date2024 = Carbon::create()->setISODate(2024, 21, 1)->startOfDay();
        $date2025 = Carbon::create()->setISODate(2025, 21, 1)->startOfDay();

        HistoricalDailyVisitor::factory()->onDate($date2024->toDateString(), 20)->create();
        HistoricalDailyVisitor::factory()->onDate($date2025->toDateString(), 40)->create();

        $forecast = app(HistoricalVisitorForecastService::class);
        $value = $forecast->forecastForDate($target, [2024, 2025]);

        $this->assertSame(30.0, $value);
    }

    public function test_daily_series_includes_separate_years_and_forecast(): void
    {
        $target = Carbon::create()->setISODate(2026, 21, 1)->startOfDay();
        Carbon::setTestNow($target->copy()->addDays(2));

        HistoricalDailyVisitor::factory()->onDate(
            Carbon::create()->setISODate(2024, 21, 1)->toDateString(),
            10,
        )->create();
        HistoricalDailyVisitor::factory()->onDate(
            Carbon::create()->setISODate(2025, 21, 1)->toDateString(),
            30,
        )->create();

        $comparison = new HistoricalVisitorComparisonService;
        $series = $comparison->dailySeries($target, $target, [2024, 2025]);

        $pastRow = $series[0];
        $this->assertTrue($pastRow['is_past']);
        $this->assertSame(10, $pastRow['historical_by_year'][2024]);
        $this->assertSame(30, $pastRow['historical_by_year'][2025]);
        $this->assertSame(20, $pastRow['forecast']);
        $this->assertSame($target->format('j/n'), $pastRow['label_short']);
        $this->assertStringContainsString('maj', $pastRow['label']);

        Carbon::setTestNow();
    }

    public function test_format_comparison_label_capitalizes_swedish_weekday(): void
    {
        $comparison = new HistoricalVisitorComparisonService;
        $label = $comparison->formatComparisonLabel(
            Carbon::create()->setISODate(2026, 21, 1)->locale('sv')
        );

        $this->assertStringStartsWith('Må', $label);
        $this->assertStringContainsString('maj', $label);
    }

    public function test_current_participants_sums_active_bookings_for_date(): void
    {
        $tour = Tour::query()->create([
            'title' => 'Testtur',
            'description' => 'Test',
            'tour_date' => '2025-05-19',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Grupp A',
            'total_count' => 12,
            'status' => 'confirmed',
            'is_waitlist' => false,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Grupp B',
            'total_count' => 8,
            'status' => 'cancelled',
            'is_waitlist' => false,
        ]);

        $comparison = new HistoricalVisitorComparisonService;

        $this->assertSame(12, $comparison->currentParticipantsForDate(Carbon::parse('2025-05-19')));
    }

    public function test_chart_payload_includes_series_for_line_chart(): void
    {
        $target = Carbon::create()->setISODate(2026, 21, 1)->startOfDay();

        HistoricalDailyVisitor::factory()->onDate(
            Carbon::create()->setISODate(2024, 21, 1)->toDateString(),
            10,
        )->create();
        HistoricalDailyVisitor::factory()->onDate(
            Carbon::create()->setISODate(2025, 21, 1)->toDateString(),
            30,
        )->create();

        $comparison = new HistoricalVisitorComparisonService;
        $series = $comparison->dailySeries($target, $target->copy()->addDay(), [2024, 2025]);
        $chart = $comparison->chartPayload($series, [2024, 2025], 2026);

        $this->assertCount(2, $chart['labels']);
        $this->assertCount(4, $chart['datasets']);
        $this->assertSame('2024', $chart['datasets'][0]['label']);
        $this->assertSame('Prognos', $chart['datasets'][2]['label']);
        $this->assertSame('Bokade 2026', $chart['datasets'][3]['label']);
        $this->assertSame([10, null], $chart['datasets'][0]['data']);
    }

    public function test_admin_can_view_period_line_chart_when_enabled(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.historical-visitors.index', [
                'period' => 'week',
                'date' => now()->toDateString(),
                'chart' => 1,
            ]))
            ->assertOk()
            ->assertSee('Linjediagram – vald period', false)
            ->assertSee('historicalPeriodChart', false);
    }

    public function test_admin_can_hide_period_line_chart(): void
    {
        $admin = $this->actingAdmin();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.historical-visitors.index', [
                'period' => 'week',
                'date' => now()->toDateString(),
                'chart' => 0,
            ]))
            ->assertOk()
            ->assertDontSee('historicalPeriodChart', false);
    }

    public function test_admin_can_delete_single_historical_day(): void
    {
        $admin = $this->actingAdmin();
        $record = HistoricalDailyVisitor::factory()->onDate('2023-06-01', 10)->create();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.statistics.historical-visitors.destroy', $record))
            ->assertRedirect(route('admin.statistics.historical-visitors.index'));

        $this->assertDatabaseMissing('historical_daily_visitors', ['id' => $record->id]);
    }
}
