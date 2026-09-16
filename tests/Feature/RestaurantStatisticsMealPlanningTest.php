<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use Tests\TestCase;

class RestaurantStatisticsMealPlanningTest extends TestCase
{
    public function test_dashboard_lists_tours_with_meal_bookings_within_seven_days(): void
    {
        $this->tourWithMealBooking(
            title: 'Matplanering inom veckan',
            tourDate: now()->addDays(3)->toDateString(),
            people: 5,
        );

        $this->tourWithMealBooking(
            title: 'Matplanering utanför veckan',
            tourDate: now()->addDays(10)->toDateString(),
            people: 4,
        );

        $tourWithoutMeal = Tour::query()->create([
            'title' => 'Tur utan mat',
            'tour_date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tourWithoutMeal->id,
            'booking_name' => 'Ej mat',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
            'includes_meal' => false,
        ]);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Matbokningar – kommande 7 dagar', false)
            ->assertSee('Matplanering inom veckan', false)
            ->assertSee('5 gäster med mat', false)
            ->assertDontSee('Matplanering utanför veckan', false)
            ->assertDontSee('Tur utan mat', false)
            ->assertSee('Pågående turer', false)
            ->assertSee('Kommande turer', false);
    }

    public function test_dashboard_meal_list_only_counts_bookings_with_meal(): void
    {
        $tour = Tour::query()->create([
            'title' => 'Blandad matbokning',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Med mat',
            'men_count' => 2,
            'women_count' => 1,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 3,
            'status' => 'confirmed',
            'includes_meal' => true,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Utan mat',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
            'includes_meal' => false,
        ]);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Blandad matbokning', false)
            ->assertSee('3 gäster med mat', false);
    }

    public function test_dashboard_today_visitor_total_excludes_guests_on_ongoing_tours(): void
    {
        $today = now()->toDateString();

        $ongoingTour = Tour::query()->create([
            'title' => 'Pågående tur',
            'tour_date' => $today,
            'start_time' => '09:00',
            'end_time' => '10:15',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now()->subMinutes(20),
        ]);

        Booking::query()->create([
            'tour_id' => $ongoingTour->id,
            'booking_name' => 'Under jord',
            'men_count' => 12,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 12,
            'status' => 'confirmed',
        ]);

        $upcomingTour = Tour::query()->create([
            'title' => 'Kommande tur',
            'tour_date' => $today,
            'start_time' => now()->addHours(2)->format('H:i'),
            'end_time' => now()->addHours(3)->format('H:i'),
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $upcomingTour->id,
            'booking_name' => 'Kommer senare',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
        ]);

        $completedTour = Tour::query()->create([
            'title' => 'Avslutad tur',
            'tour_date' => $today,
            'start_time' => '08:00',
            'end_time' => '09:15',
            'max_participants' => 30,
            'status' => 'completed',
            'started_at' => now()->subHours(3),
            'ended_at' => now()->subHours(2),
        ]);

        Booking::query()->create([
            'tour_id' => $completedTour->id,
            'booking_name' => 'Redan varit',
            'men_count' => 6,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 6,
            'status' => 'confirmed',
        ]);

        $response = $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Gäster på pågående turer', false)
            ->assertSee('Besökare totalt idag', false);

        preg_match(
            '/Besökare totalt idag<\/div>\s*<div class="board-stat-value">(\d+)<\/div>/',
            $response->getContent(),
            $matches
        );

        $this->assertSame('10', $matches[1] ?? null);
    }

    public function test_dashboard_meal_list_can_show_thirty_day_period(): void
    {
        $this->tourWithMealBooking(
            title: 'Matplanering dag 20',
            tourDate: now()->addDays(20)->toDateString(),
            people: 6,
        );

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard', ['meal_days' => 7]))
            ->assertOk()
            ->assertDontSee('Matplanering dag 20', false);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard', ['meal_days' => 30]))
            ->assertOk()
            ->assertSee('Matbokningar – kommande 30 dagar', false)
            ->assertSee('Matplanering dag 20', false)
            ->assertSee('6 gäster med mat', false);
    }

    public function test_dashboard_meal_list_falls_back_to_seven_days_for_invalid_period(): void
    {
        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard', ['meal_days' => 14]))
            ->assertOk()
            ->assertSee('Matbokningar – kommande 7 dagar', false);
    }

    public function test_dashboard_meal_list_includes_meal_tours_without_bookings(): void
    {
        Tour::query()->create([
            'title' => 'Mat tur utan bokning',
            'description' => 'Lunch ingår i turen.',
            'tour_date' => now()->addDays(2)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'max_participants' => 25,
            'status' => 'planned',
            'default_includes_meal' => true,
        ]);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Mat tur utan bokning', false)
            ->assertSee('Lunch ingår i turen.', false)
            ->assertSee('Med mat', false);
    }

    public function test_dashboard_meal_list_shows_tour_description(): void
    {
        $tour = Tour::query()->create([
            'title' => 'Tur med beskrivning',
            'description' => 'Vegetarisk lunch och kaffe efter turen.',
            'tour_date' => now()->addDays(2)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'max_participants' => 25,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Matgrupp',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
            'includes_meal' => true,
        ]);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Vegetarisk lunch och kaffe efter turen.', false);
    }

    private function tourWithMealBooking(string $title, string $tourDate, int $people): Tour
    {
        $tour = Tour::query()->create([
            'title' => $title,
            'tour_date' => $tourDate,
            'start_time' => '11:00',
            'end_time' => '12:00',
            'max_participants' => 25,
            'status' => 'planned',
            'default_includes_meal' => true,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => $title,
            'men_count' => $people,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => $people,
            'status' => 'confirmed',
            'includes_meal' => true,
        ]);

        return $tour;
    }
}
