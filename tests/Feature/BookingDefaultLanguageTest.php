<?php

namespace Tests\Feature;

use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class BookingDefaultLanguageTest extends TestCase
{
    public function test_booking_create_form_preselects_swedish(): void
    {
        $swedish = Language::query()->where('code', 'sv')->first();
        $this->assertNotNull($swedish);

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->createPlannedTour();

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.create'))
            ->assertOk()
            ->assertSee('Svenska', false)
            ->assertSee('value="'.$swedish->id.'"', false)
            ->assertSee('checked', false);
    }

    public function test_storing_booking_without_language_uses_swedish_default(): void
    {
        $swedish = Language::query()->where('code', 'sv')->first();
        $this->assertNotNull($swedish);

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $tour = $this->createPlannedTour();

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Testbokning',
                'men_count' => 2,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect();

        $booking = $tour->fresh()->bookings()->first();
        $this->assertNotNull($booking);
        $this->assertTrue($booking->languages->contains('id', $swedish->id));
    }

    public function test_booking_create_honours_tour_id_from_dashboard_link(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $firstTour = Tour::query()->create([
            'title' => 'Första tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'max_participants' => 25,
            'status' => 'planned',
        ]);

        $secondTour = Tour::query()->create([
            'title' => 'Andra tur',
            'tour_date' => now()->addDays(2)->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'max_participants' => 25,
            'status' => 'planned',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.create', ['tour_id' => $secondTour->id]))
            ->assertOk();

        $html = $response->getContent();

        $this->assertMatchesRegularExpression(
            '/value="'.$secondTour->id.'"\s+selected/',
            $html,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/value="'.$firstTour->id.'"\s+selected/',
            $html,
        );
    }

    private function createPlannedTour(): Tour
    {
        return Tour::query()->create([
            'title' => 'Bokning språktest',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 25,
            'status' => 'planned',
        ]);
    }
}
