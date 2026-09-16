<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class BookingDeletionTest extends TestCase
{
    public function test_admin_can_delete_booking_from_index(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->createTour();
        $booking = $this->createBooking($tour, 'Grupp att ta bort');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.bookings.destroy', $booking), [
                'scope' => 'active',
            ])
            ->assertRedirect(route('admin.bookings.index', ['scope' => 'active']))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);

        $this->assertDatabaseHas('activity_logs', [
            'entity_type' => 'booking',
            'entity_id' => $booking->id,
            'action' => 'deleted',
        ]);
    }

    public function test_host_can_delete_booking_from_tour_page(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = $this->createTour();
        $booking = $this->createBooking($tour, 'Värdgrupp');

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.show', $tour))
            ->assertOk()
            ->assertSee('Ta bort', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->delete(route('host.bookings.destroy', $booking), [
                'from_tour' => '1',
            ])
            ->assertRedirect(route('host.tours.show', $tour))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
    }

    public function test_admin_can_delete_booking_from_edit_page(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->createTour();
        $booking = $this->createBooking($tour, 'Redigera och ta bort');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('Ta bort bokning', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.bookings.destroy', $booking), [
                'from_tour' => '1',
            ])
            ->assertRedirect(route('admin.tours.show', $tour));

        $this->assertSame(1, ActivityLog::query()
            ->where('entity_type', 'booking')
            ->where('entity_id', $booking->id)
            ->where('action', 'deleted')
            ->count());
    }

    private function createTour(): Tour
    {
        return Tour::query()->create([
            'title' => 'Testtur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);
    }

    private function createBooking(Tour $tour, string $name): Booking
    {
        return Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => $name,
            'contact_name' => 'Kontakt',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
        ]);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
