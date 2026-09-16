<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminHistoricalTourBookingTest extends TestCase
{
    public function test_admin_can_open_create_form_for_completed_tour_from_tour_show(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.create', ['tour_id' => $tour->id]))
            ->assertOk()
            ->assertSee('Historiska turer (retroaktiv bokning)', false)
            ->assertSee('Avslutad testtur', false)
            ->assertSee('value="'.$tour->id.'"', false);
    }

    public function test_admin_can_create_booking_on_completed_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Retro-bokning',
                'contact_name' => 'Glömd grupp',
                'phone' => '0701112233',
                'email' => 'grupp@example.com',
                'men_count' => 4,
                'women_count' => 2,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'arrival_status' => 'arrived',
            ])
            ->assertRedirect(route('admin.bookings.index', ['scope' => 'archive']))
            ->assertSessionHas('success');

        $booking = Booking::query()->where('booking_name', 'Retro-bokning')->first();

        $this->assertNotNull($booking);
        $this->assertSame($tour->id, $booking->tour_id);
        $this->assertSame(6, $booking->total_count);
        $this->assertFalse($booking->is_waitlist);
    }

    public function test_admin_can_create_booking_on_past_planned_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = Tour::query()->create([
            'title' => 'Glömd planerad tur',
            'description' => 'Skapad i efterhand.',
            'tour_date' => now()->subDays(3)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Efterhandsbokning',
                'contact_name' => 'Skolklass',
                'men_count' => 10,
                'women_count' => 8,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.bookings.index', ['scope' => 'archive']));

        $this->assertDatabaseHas('bookings', [
            'booking_name' => 'Efterhandsbokning',
            'tour_id' => $tour->id,
            'total_count' => 18,
            'is_waitlist' => 0,
        ]);
    }

    public function test_retroactive_booking_ignores_capacity_limit(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTour();

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Full-tur-bokning',
            'men_count' => 18,
            'women_count' => 2,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 20,
            'status' => 'confirmed',
            'arrival_status' => 'arrived',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Walk-in retro',
                'contact_name' => 'Drop-in',
                'men_count' => 3,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.bookings.index', ['scope' => 'archive']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'booking_name' => 'Walk-in retro',
            'is_waitlist' => 0,
        ]);
    }

    public function test_admin_cannot_create_booking_on_cancelled_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = Tour::query()->create([
            'title' => 'Avbokad tur',
            'description' => 'Inställd.',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'cancelled',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.bookings.create'))
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'Ogiltig bokning',
                'men_count' => 1,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('admin.bookings.create'))
            ->assertSessionHasErrors('tour_id');

        $this->assertDatabaseMissing('bookings', [
            'booking_name' => 'Ogiltig bokning',
        ]);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function completedTour(): Tour
    {
        return Tour::query()->create([
            'title' => 'Avslutad testtur',
            'description' => 'Genomförd tur.',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'completed',
            'started_at' => now()->subDay()->setTime(10, 0),
            'ended_at' => now()->subDay()->setTime(11, 0),
        ]);
    }
}
