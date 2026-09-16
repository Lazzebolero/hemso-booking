<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class BookingIncludesMealTest extends TestCase
{
    public function test_admin_can_create_booking_with_meal(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Familjen Mat',
                'contact_name' => 'Test Testsson',
                'men_count' => 2,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'includes_meal' => '1',
            ])
            ->assertRedirect(route('admin.bookings.index'))
            ->assertSessionHas('success');

        $booking = Booking::query()->where('booking_name', 'Familjen Mat')->first();

        $this->assertNotNull($booking);
        $this->assertTrue($booking->includes_meal);
        $this->assertSame('Med mat', $booking->mealLabel());
    }

    public function test_admin_booking_defaults_to_no_meal(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Familjen Utan Mat',
                'contact_name' => 'Test Testsson',
                'men_count' => 1,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $booking = Booking::query()->where('booking_name', 'Familjen Utan Mat')->first();

        $this->assertNotNull($booking);
        $this->assertFalse($booking->includes_meal);
    }

    public function test_admin_can_update_booking_meal_option(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Uppdatera mat',
            'men_count' => 1,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 1,
            'status' => 'confirmed',
            'includes_meal' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.bookings.update', $booking), [
                'tour_id' => $tour->id,
                'booking_name' => 'Uppdatera mat',
                'men_count' => 1,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'includes_meal' => '1',
            ])
            ->assertRedirect(route('admin.bookings.index'));

        $this->assertTrue($booking->fresh()->includes_meal);
    }

    public function test_booking_form_shows_meal_select(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.create', ['tour_id' => $tour->id]))
            ->assertOk()
            ->assertSee('Med mat', false)
            ->assertSee('Ej mat', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tour(): Tour
    {
        return Tour::query()->create([
            'title' => 'Mattest tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);
    }
}
