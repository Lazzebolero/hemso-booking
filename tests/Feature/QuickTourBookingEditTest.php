<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class QuickTourBookingEditTest extends TestCase
{
    public function test_admin_can_open_booking_edit_for_quick_tour_booking(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $booking = $this->quickTourBooking();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.edit', $booking))
            ->assertOk()
            ->assertSee('Redigera bokning', false)
            ->assertSee('Snabbtur', false)
            ->assertSee('Spara bokning', false)
            ->assertDontSee('CACHE_NAME', false);
    }

    public function test_host_can_update_booking_for_quick_tour_and_stays_in_host_routes(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $booking = $this->quickTourBooking();

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->put(route('host.bookings.update', $booking), [
                'tour_id' => $booking->tour_id,
                'booking_name' => $booking->booking_name,
                'contact_name' => $booking->contact_name,
                'phone' => $booking->phone,
                'email' => $booking->email,
                'men_count' => 2,
                'women_count' => 1,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'arrival_status' => 'booked',
                'notes' => 'Uppdaterad snabbtur.',
            ])
            ->assertRedirect(route('host.bookings.index', ['scope' => 'archive']));

        $this->assertSame(3, (int) $booking->fresh()->total_count);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function quickTourBooking(): Booking
    {
        $tour = Tour::query()->create([
            'title' => 'Snabbtur 2026-05-18 13:55',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'max_participants' => 25,
            'status' => 'started',
            'started_at' => now(),
        ]);

        return Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Snabbtur 2026-05-18 13:55',
            'contact_name' => 'Snabbtur 2026-05-18 13:55',
            'men_count' => 1,
            'women_count' => 1,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
            'arrival_status' => 'booked',
        ]);
    }
}
