<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\FacilityOccupancyService;
use App\Support\Roles;
use Tests\TestCase;

class BookingUnspecifiedAndHeadcountTest extends TestCase
{
    public function test_quick_tour_with_participant_count_creates_unspecified_booking(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->firstOrFail();

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 30,
                'language_ids' => [],
            ])
            ->assertRedirect(route('guide.dashboard'));

        $booking = Booking::query()->latest('id')->first();

        $this->assertNotNull($booking);
        $this->assertSame(30, $booking->total_count);
        $this->assertSame(30, $booking->unspecified_count);
        $this->assertSame(0, $booking->men_count);
    }

    public function test_guide_start_tour_with_fewer_on_site_reduces_largest_booking(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Värdens tur',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 40,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $large = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Stor grupp',
            'men_count' => 10,
            'women_count' => 10,
            'youth_count' => 5,
            'child_count' => 5,
            'unspecified_count' => 0,
            'total_count' => 30,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Liten grupp',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.start', $tour), [
                'actual_on_site_count' => 27,
            ])
            ->assertRedirect();

        $tour->refresh();
        $large->refresh();

        $this->assertSame('started', $tour->status);
        $this->assertSame(32, $tour->booked_total_at_start);
        $this->assertSame(27, $tour->actual_total_at_start);
        $this->assertSame(25, $large->total_count);
        $this->assertSame(10, $large->men_count);
        $this->assertSame(5, $large->women_count);
        $this->assertSame(5, $large->youth_count);
        $this->assertSame(5, $large->child_count);
        $this->assertSame(0, $large->unspecified_count);
        $this->assertSame(27, $this->sumActiveBookings($tour));
    }

    public function test_guide_start_tour_with_one_missing_person_preserves_booking_breakdowns(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur med två grupper',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 40,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $first = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Grupp ett',
            'men_count' => 8,
            'women_count' => 4,
            'youth_count' => 2,
            'child_count' => 1,
            'unspecified_count' => 0,
            'total_count' => 15,
            'status' => 'confirmed',
        ]);

        $second = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Grupp två',
            'men_count' => 5,
            'women_count' => 3,
            'youth_count' => 1,
            'child_count' => 1,
            'unspecified_count' => 0,
            'total_count' => 10,
            'status' => 'confirmed',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.start', $tour), [
                'actual_on_site_count' => 24,
            ])
            ->assertRedirect();

        $first->refresh();
        $second->refresh();

        $this->assertSame(14, $first->total_count);
        $this->assertSame(8, $first->men_count);
        $this->assertSame(3, $first->women_count);
        $this->assertSame(2, $first->youth_count);
        $this->assertSame(1, $first->child_count);
        $this->assertSame(0, $first->unspecified_count);

        $this->assertSame(10, $second->total_count);
        $this->assertSame(5, $second->men_count);
        $this->assertSame(3, $second->women_count);
        $this->assertSame(1, $second->youth_count);
        $this->assertSame(1, $second->child_count);
        $this->assertSame(0, $second->unspecified_count);
        $this->assertSame(24, $this->sumActiveBookings($tour->fresh()));
    }

    public function test_guide_cannot_reduce_below_youth_and_child_counts_using_adults_only(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur med många barn',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 40,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Skolklass',
            'men_count' => 1,
            'women_count' => 1,
            'youth_count' => 8,
            'child_count' => 10,
            'unspecified_count' => 0,
            'total_count' => 20,
            'status' => 'confirmed',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.start', $tour), [
                'actual_on_site_count' => 17,
            ])
            ->assertSessionHasErrors('actual_on_site_count');
    }

    public function test_guide_start_tour_with_more_on_site_creates_walk_in_booking(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur med walk-in',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 40,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Bokad grupp',
            'men_count' => 10,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 10,
            'status' => 'confirmed',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.start', $tour), [
                'actual_on_site_count' => 13,
            ])
            ->assertRedirect();

        $walkIn = Booking::query()
            ->where('tour_id', $tour->id)
            ->where('is_walk_in', true)
            ->first();

        $this->assertNotNull($walkIn);
        $this->assertSame(3, $walkIn->total_count);
        $this->assertSame(3, $walkIn->unspecified_count);
        $this->assertSame(13, $this->sumActiveBookings($tour->fresh()));
    }

    public function test_host_quick_booking_accepts_unspecified_only(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tourType = TourType::query()->firstOrFail();
        $tourType->update(['include_in_booking_sequence' => true]);

        $tour = Tour::query()->create([
            'title' => 'Värd tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('host.bookings.quick-store'), [
                'tour_id' => $tour->id,
                'unspecified_count' => 12,
            ])
            ->assertRedirect(route('host.bookings.quick-create'));

        $booking = Booking::query()->where('tour_id', $tour->id)->first();

        $this->assertNotNull($booking);
        $this->assertSame(12, $booking->total_count);
        $this->assertSame(12, $booking->unspecified_count);
    }

    public function test_admin_can_update_facility_extra_count(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->patch(route('admin.facility-occupancy.update-extra'), [
                'extra_count' => 8,
            ])
            ->assertRedirect();

        $this->assertSame(8, app(FacilityOccupancyService::class)->extraCount());
    }

    private function sumActiveBookings(Tour $tour): int
    {
        return (int) Booking::query()
            ->where('tour_id', $tour->id)
            ->whereNotIn('status', ['cancelled'])
            ->where('is_waitlist', false)
            ->sum('total_count');
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
