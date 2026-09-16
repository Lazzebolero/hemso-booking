<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminStatisticsPageTest extends TestCase
{
    public function test_statistics_page_does_not_show_misleading_occupancy_metrics(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $tour = Tour::query()->create([
            'title' => 'Förbokad grupp',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Gruppen',
            'men_count' => 15,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 15,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.index'))
            ->assertOk()
            ->assertSee('Bokade personer', false)
            ->assertSee('15', false)
            ->assertDontSee('Beläggning', false)
            ->assertDontSee('Bästa beläggning', false)
            ->assertSee('Bokade per turtyp', false);
    }

    public function test_popular_times_are_grouped_in_fifteen_minute_slots(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $date = now()->toDateString();

        $morningSlotTour = Tour::query()->create([
            'title' => 'Tidig tur',
            'tour_date' => $date,
            'start_time' => '11:07',
            'end_time' => '12:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $sameSlotTour = Tour::query()->create([
            'title' => 'Samma kvart',
            'tour_date' => $date,
            'start_time' => '11:12',
            'end_time' => '12:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $nextSlotTour = Tour::query()->create([
            'title' => 'Nästa kvart',
            'tour_date' => $date,
            'start_time' => '11:20',
            'end_time' => '12:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $morningSlotTour->id,
            'booking_name' => 'Grupp A',
            'men_count' => 5,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 5,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $sameSlotTour->id,
            'booking_name' => 'Grupp B',
            'men_count' => 3,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 3,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $nextSlotTour->id,
            'booking_name' => 'Grupp C',
            'men_count' => 7,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 7,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('admin.statistics.live', [
                'period' => 'day',
                'date' => $date,
            ]));

        $response->assertOk();

        $popularTimes = $response->json('stats.popular_times');

        $this->assertSame([
            [
                'label' => '11:00-11:15',
                'bookings' => 2,
                'booked_people' => 8,
            ],
            [
                'label' => '11:15-11:30',
                'bookings' => 1,
                'booked_people' => 7,
            ],
        ], $popularTimes);

        $timelineLabels = $response->json('stats.timeline.labels');

        $this->assertSame(['11:00-11:15', '11:15-11:30'], $timelineLabels);
    }

    public function test_week_timeline_aggregates_quarter_hour_slots_across_days(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $weekStart = now()->startOfWeek()->toDateString();

        $mondayTour = Tour::query()->create([
            'title' => 'Måndagstur',
            'tour_date' => $weekStart,
            'start_time' => '10:05',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $wednesdayTour = Tour::query()->create([
            'title' => 'Onsdagstur',
            'tour_date' => now()->startOfWeek()->addDays(2)->toDateString(),
            'start_time' => '10:12',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $otherSlotTour = Tour::query()->create([
            'title' => 'Senare tur',
            'tour_date' => $weekStart,
            'start_time' => '14:20',
            'end_time' => '15:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $mondayTour->id,
            'booking_name' => 'Måndag',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $wednesdayTour->id,
            'booking_name' => 'Onsdag',
            'men_count' => 6,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 6,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $otherSlotTour->id,
            'booking_name' => 'Eftermiddag',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->getJson(route('admin.statistics.live', [
                'period' => 'week',
                'date' => $weekStart,
            ]));

        $response->assertOk();

        $this->assertSame(
            ['10:00-10:15', '14:15-14:30'],
            $response->json('stats.timeline.labels')
        );

        $this->assertSame(
            [10, 2],
            $response->json('stats.timeline.booked')
        );

        $this->assertSame(
            [2, 1],
            $response->json('stats.timeline.tours')
        );
    }

    public function test_statistics_can_filter_by_specific_month(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $mayTour = Tour::query()->create([
            'title' => 'Majtur',
            'tour_date' => '2025-05-15',
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $juneTour = Tour::query()->create([
            'title' => 'Junitur',
            'tour_date' => '2025-06-10',
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $mayTour->id,
            'booking_name' => 'Majgrupp',
            'men_count' => 12,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 12,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $juneTour->id,
            'booking_name' => 'Junigrupp',
            'men_count' => 8,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 8,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.index', [
                'period' => 'month',
                'year' => 2025,
                'month' => 5,
            ]));

        $response->assertOk()
            ->assertSee('2025-05-01 – 2025-05-31', false);

        $this->assertSame(12, $response->viewData('stats')['summary']['booked_people']);
        $this->assertSame(5, $response->viewData('month'));
    }

    public function test_statistics_can_filter_by_whole_year(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $springTour = Tour::query()->create([
            'title' => 'Vårtur',
            'tour_date' => '2025-04-12',
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $autumnTour = Tour::query()->create([
            'title' => 'Hösttur',
            'tour_date' => '2025-10-03',
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $otherYearTour = Tour::query()->create([
            'title' => 'Förra året',
            'tour_date' => '2024-05-12',
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $springTour->id,
            'booking_name' => 'Vår',
            'men_count' => 5,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 5,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $autumnTour->id,
            'booking_name' => 'Höst',
            'men_count' => 7,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 7,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $otherYearTour->id,
            'booking_name' => 'Gammal',
            'men_count' => 99,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 99,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.index', [
                'period' => 'year',
                'year' => 2025,
            ]));

        $response->assertOk()
            ->assertSee('2025-01-01 – 2025-12-31', false)
            ->assertSee('Hela året', false);

        $this->assertSame(12, $response->viewData('stats')['summary']['booked_people']);
    }
}
