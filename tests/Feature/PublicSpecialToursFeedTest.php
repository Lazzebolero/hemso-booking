<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\TourBookingPage;
use App\Models\TourType;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PublicSpecialToursFeedTest extends TestCase
{
    public function test_public_special_tours_feed_route_is_registered(): void
    {
        $this->assertTrue(Route::has('public.special-tours.index'));
    }

    public function test_public_special_tours_feed_lists_upcoming_public_booking_pages(): void
    {
        $tourType = TourType::query()->create([
            'name' => 'Mörkertur',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'default_duration_minutes' => 90,
        ]);

        $visibleTour = Tour::query()->create([
            'title' => 'Mörkertur i fästningen',
            'tour_type_id' => $tourType->id,
            'description' => 'En kvällstur genom fästningen.',
            'tour_date' => now()->addDays(7)->toDateString(),
            'start_time' => '18:30',
            'end_time' => '20:00',
            'max_participants' => 10,
            'status' => 'planned',
        ]);

        TourBookingPage::query()->create([
            'tour_id' => $visibleTour->id,
            'slug' => 'morkertur-i-fastningen',
            'page_title' => 'Boka mörkertur',
            'page_text' => 'Följ med på en unik specialtur.',
            'adult_price' => 250,
            'youth_price' => 150,
            'child_price' => 0,
            'is_public' => true,
        ]);

        Booking::query()->create([
            'tour_id' => $visibleTour->id,
            'booking_name' => 'BOK-TEST-1',
            'total_count' => 3,
            'status' => 'confirmed',
            'is_waitlist' => false,
        ]);

        Booking::query()->create([
            'tour_id' => $visibleTour->id,
            'booking_name' => 'BOK-TEST-2',
            'total_count' => 2,
            'status' => 'cancelled',
            'is_waitlist' => false,
        ]);

        $this->createHiddenSpecialTour('Privat specialtur', now()->addDays(8)->toDateString(), false, 'planned');
        $this->createHiddenSpecialTour('Gammal specialtur', now()->subDay()->toDateString(), true, 'planned');
        $this->createHiddenSpecialTour('Avbokad specialtur', now()->addDays(9)->toDateString(), true, 'cancelled');

        $response = $this->getJson(route('public.special-tours.index'));

        $response->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonPath('meta.count', 1)
            ->assertJsonPath('data.0.title', 'Mörkertur i fästningen')
            ->assertJsonPath('data.0.tour_type', 'Mörkertur')
            ->assertJsonPath('data.0.date', now()->addDays(7)->toDateString())
            ->assertJsonPath('data.0.start_time', '18:30')
            ->assertJsonPath('data.0.capacity', 10)
            ->assertJsonPath('data.0.booked_participants', 3)
            ->assertJsonPath('data.0.available_spots', 7)
            ->assertJsonPath('data.0.is_full', false)
            ->assertJsonPath('data.0.prices.adult', 250)
            ->assertJsonPath('data.0.booking_path', '/tour-booking/morkertur-i-fastningen')
            ->assertJsonPath('data.0.booking_url', url('/tour-booking/morkertur-i-fastningen'))
            ->assertJsonMissing(['title' => 'Privat specialtur'])
            ->assertJsonMissing(['title' => 'Gammal specialtur'])
            ->assertJsonMissing(['title' => 'Avbokad specialtur']);
    }

    private function createHiddenSpecialTour(string $title, string $date, bool $isPublic, string $status): void
    {
        $tour = Tour::query()->create([
            'title' => $title,
            'tour_date' => $date,
            'start_time' => '12:00',
            'max_participants' => 20,
            'status' => $status,
        ]);

        TourBookingPage::query()->create([
            'tour_id' => $tour->id,
            'slug' => str($title)->slug()->toString(),
            'page_title' => $title,
            'adult_price' => 100,
            'youth_price' => 50,
            'child_price' => 0,
            'is_public' => $isPublic,
        ]);
    }
}
