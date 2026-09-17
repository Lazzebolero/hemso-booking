<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\FerryScheduleService;
use App\Services\Trafikverket\FerryTrafficService;
use App\Support\FerryDayTypes;
use App\Support\FerryDirections;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FerryScheduleTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ferry_schedule_page_loads_for_guide(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('ferry-schedule.index'))
            ->assertOk()
            ->assertSee('Färjetrafik', false)
            ->assertSee('avgångar från Strinningen', false)
            ->assertDontSee('Till jobbet', false)
            ->assertDontSee('>Hem<', false)
            ->assertSee('guide-mobile-action', false)
            ->assertDontSee('restaurant-mobile-header', false);
    }

    public function test_ferry_schedule_keeps_restaurant_personal_shell_navigation(): void
    {
        $restaurant = $this->userWithRole(Roles::RESTAURANT);

        $this->actingAs($restaurant)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('ferry-schedule.index'))
            ->assertOk()
            ->assertSee('Färjetrafik', false)
            ->assertSee('Restaurang · Personalvy', false)
            ->assertSee('restaurant-mobile-header', false)
            ->assertDontSee('guide-mobile-action', false)
            ->assertDontSee('Boknings- och guidesystem', false);
    }

    public function test_ferry_schedule_keeps_host_personal_shell_navigation(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('ferry-schedule.index', ['date' => '2026-06-20']))
            ->assertOk()
            ->assertSee('Färjetrafik', false)
            ->assertSee('Lördag', false)
            ->assertSee('restaurant-mobile-header', false)
            ->assertDontSee('guide-mobile-action', false)
            ->assertDontSee('Boknings- och guidesystem', false);
    }

    public function test_saturday_and_sunday_use_weekend_timetable_with_call_departures(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $saturday = Carbon::parse('2026-06-20');
        $sunday = Carbon::parse('2026-06-21');

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('ferry-schedule.index', ['date' => $saturday->toDateString()]))
            ->assertOk()
            ->assertSee('Lördag', false)
            ->assertSee('07:30', false)
            ->assertSee('06:30', false)
            ->assertSee('11:30', false)
            ->assertSee('14:30', false)
            ->assertSee('Kallelsetur', false)
            ->assertSee('Dubbleringsturer', false)
            ->assertSee('Dubbleringsturer: Nej', false)
            ->assertDontSee('07:20', false)
            ->assertDontSee('14:40', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('ferry-schedule.index', ['date' => $sunday->toDateString()]))
            ->assertOk()
            ->assertSee('Söndag', false)
            ->assertSee('07:30', false)
            ->assertDontSee('07:20', false);
    }

    public function test_day_type_for_weekend_dates_is_saturday_and_sunday(): void
    {
        $service = app(FerryScheduleService::class);

        $this->assertSame(FerryDayTypes::SATURDAY, $service->dayTypeFor(Carbon::parse('2026-06-20')));
        $this->assertSame(FerryDayTypes::SUNDAY, $service->dayTypeFor(Carbon::parse('2026-06-21')));
        $this->assertSame(FerryDayTypes::WEEKDAY, $service->dayTypeFor(Carbon::parse('2026-06-19')));
        $this->assertSame('Lördag', $service->dayTypeLabelFor(Carbon::parse('2026-06-20')));
        $this->assertSame('Söndag', $service->dayTypeLabelFor(Carbon::parse('2026-06-21')));
    }

    public function test_admin_ferry_timetable_page_is_read_only(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.ferry-timetable.index'))
            ->assertOk()
            ->assertSee('Färjetidtabell', false)
            ->assertSee('Strinningen', false)
            ->assertSee('Vardag', false)
            ->assertSee('Lördag', false)
            ->assertSee('Söndag', false)
            ->assertSee('Dubbleringsturer', false)
            ->assertSee('04:30', false)
            ->assertSee('11:30', false)
            ->assertSee('18:30', false)
            ->assertSee('2026-09-10', false)
            ->assertSee('23:30', false)
            ->assertDontSee('Ta bort avgången', false)
            ->assertDontSee('Ny avgång', false);
    }

    public function test_day_snapshot_marks_next_departure(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 09:15:00'));

        $snapshot = app(FerryScheduleService::class)->daySnapshot(now(), FerryDirections::TO_ISLAND);

        $this->assertSame('09:00', $snapshot['last']['time'] ?? null);
        $this->assertSame('09:30', $snapshot['next']['time'] ?? null);
    }

    public function test_tour_ferry_advice_warns_when_margin_is_short(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 08:00:00'));

        $tourType = TourType::query()->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Kort marginal tur',
            'tour_date' => '2026-06-23',
            'start_time' => '10:15:00',
            'end_time' => '11:35:00',
            'max_participants' => 20,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $advice = app(FerryScheduleService::class)->tourFerryAdvice($tour);

        $this->assertSame('warn', $advice['level'] ?? null);
        $this->assertSame('10:00', $advice['ferry_time'] ?? null);
    }

    public function test_tour_ferry_advice_is_ok_when_margin_is_sufficient(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 08:00:00'));

        $tourType = TourType::query()->firstOrFail();
        $tour = Tour::query()->create([
            'title' => 'Bra marginal tur',
            'tour_date' => '2026-06-23',
            'start_time' => '13:35:00',
            'end_time' => '14:55:00',
            'max_participants' => 20,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $advice = app(FerryScheduleService::class)->tourFerryAdvice($tour);

        $this->assertSame('ok', $advice['level'] ?? null);
    }

    public function test_admin_dashboard_shows_ferry_card(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-23 09:00:00'));

        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Hemsöleden · Strinningen', false)
            ->assertSee('Senast avgått', false)
            ->assertSee('Nästa avgång', false);
    }

    public function test_day_snapshot_uses_live_last_departure_for_extra_trip(): void
    {
        Config::set('trafikverket.api_key', 'test-key');

        Http::fake([
            'api.trafikinfo.trafikverket.se/*' => Http::sequence()
                ->push([
                    'RESPONSE' => [
                        'RESULT' => [
                            [
                                'FerryAnnouncement' => [
                                    [
                                        'RouteName' => 'Hemsöleden',
                                        'DepartureTime' => '2026-06-23T10:15:00',
                                        'TimeTabledDepartureTime' => '2026-06-23T10:15:00',
                                        'DepartureIsCancelled' => false,
                                        'FromHarbor' => ['Name' => 'Strinningen'],
                                        'ToHarbor' => ['Name' => 'Hemsö'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ], 200)
                ->push(['RESPONSE' => ['RESULT' => [['Situation' => []]]]], 200),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-06-23 10:25:00'));

        app(FerryTrafficService::class)->refreshForDate(now());

        $snapshot = app(FerryScheduleService::class)->daySnapshot(now(), FerryDirections::TO_ISLAND);

        $this->assertSame('10:15', $snapshot['last']['time'] ?? null);
        $this->assertTrue($snapshot['last']['is_extra'] ?? false);
        $this->assertTrue($snapshot['last']['from_live_api'] ?? false);
        $this->assertTrue($snapshot['last']['guest_arrival_relevant'] ?? false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
