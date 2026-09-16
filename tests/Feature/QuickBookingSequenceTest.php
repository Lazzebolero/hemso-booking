<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class QuickBookingSequenceTest extends TestCase
{
    public function test_booking_sequence_only_lists_eligible_tour_types(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        $specialType = TourType::query()->create([
            'name' => 'Privat tur',
            'sort_order' => 2,
            'is_active' => true,
            'include_in_booking_sequence' => false,
            'default_duration_minutes' => 90,
        ]);

        $guidedTour = Tour::query()->create([
            'title' => 'Synlig guidad tur',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Tour::query()->create([
            'title' => 'Dold privat tur',
            'tour_type_id' => $specialType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '11:30',
            'end_time' => '13:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.bookings.quick-create'))
            ->assertOk()
            ->assertSee('Synlig guidad tur', false)
            ->assertSee('Ospecificerade', false)
            ->assertSee('name="men_count"', false)
            ->assertSee('js-focus-first', false)
            ->assertDontSee('name="unspecified_count" class="form-control js-participant-field js-focus-first"', false)
            ->assertDontSee('Antal personer (snabb)', false)
            ->assertDontSee('Dold privat tur', false);
    }

    public function test_individual_tour_can_be_excluded_from_booking_sequence(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        Tour::query()->create([
            'title' => 'Guidad men exkluderad',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'exclude_from_booking_sequence' => true,
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.bookings.quick-create'))
            ->assertOk()
            ->assertDontSee('Guidad men exkluderad', false);
    }

    public function test_quick_booking_store_rejects_ineligible_tour(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $specialType = TourType::query()->create([
            'name' => 'Privat tur',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => false,
            'default_duration_minutes' => 90,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Ej tillåten tur',
            'tour_type_id' => $specialType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('host.bookings.quick-store'), [
                'tour_id' => $tour->id,
                'men_count' => 2,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
            ])
            ->assertNotFound();
    }

    public function test_tour_type_booking_sequence_setting_controls_quick_booking_list(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning filtertest',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
            'auto_complete_enabled' => true,
            'auto_complete_grace_minutes' => 15,
        ]);

        $privateType = TourType::query()->create([
            'name' => 'Privat tur filtertest',
            'sort_order' => 2,
            'is_active' => true,
            'include_in_booking_sequence' => false,
            'default_duration_minutes' => 90,
            'auto_complete_enabled' => false,
            'auto_complete_grace_minutes' => 15,
        ]);

        Tour::query()->create([
            'title' => 'Synlig i sekvensen',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Tour::query()->create([
            'title' => 'Dold i sekvensen',
            'tour_type_id' => $privateType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:30',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tour-types.update', $guidedType), [
                'name' => $guidedType->name,
                'sort_order' => 1,
                'default_duration_minutes' => 75,
                'is_active' => '1',
                'auto_complete_enabled' => '1',
                'auto_complete_grace_minutes' => 15,
            ])
            ->assertRedirect();

        $this->assertFalse($guidedType->fresh()->include_in_booking_sequence);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tour-types.update', $guidedType), [
                'name' => $guidedType->name,
                'sort_order' => 1,
                'default_duration_minutes' => 75,
                'is_active' => '1',
                'include_in_booking_sequence' => '1',
                'auto_complete_enabled' => '1',
                'auto_complete_grace_minutes' => 15,
            ])
            ->assertRedirect();

        $this->assertTrue($guidedType->fresh()->include_in_booking_sequence);

        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.bookings.quick-create'))
            ->assertOk()
            ->assertSee('Synlig i sekvensen', false)
            ->assertDontSee('Dold i sekvensen', false);
    }

    public function test_quick_booking_tour_dropdown_shows_booked_and_max_like_dashboard(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning beläggning',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Beläggningstur',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'TEST-BOOKING',
            'men_count' => 5,
            'women_count' => 3,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 8,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.quick-create'))
            ->assertOk()
            ->assertSee('Beläggningstur · 8/30', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.create', ['tour_id' => $tour->id]))
            ->assertOk()
            ->assertSee('Beläggningstur · 8/30', false);
    }

    public function test_tour_dropdown_warns_when_four_or_fewer_spots_remain(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning varning',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        $fewSpotsTour = Tour::query()->create([
            'title' => 'Nästan full tur',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $fullTour = Tour::query()->create([
            'title' => 'Fullbokad tur',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '12:00',
            'end_time' => '13:15',
            'max_participants' => 10,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $fewSpotsTour->id,
            'booking_name' => 'NEARLY-FULL',
            'men_count' => 26,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 26,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $fullTour->id,
            'booking_name' => 'FULL-BOOKING',
            'men_count' => 10,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 10,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.quick-create'))
            ->assertOk()
            ->assertSee('Nästan full tur · 26/30 · ⚠ VARNING: ENDAST 4 PLATSER KVAR', false)
            ->assertSee('Fullbokad tur · 10/10 · ⚠ FULLBOKAD – ÖVERBOKNING MÖJLIG', false)
            ->assertSee('data-overbook-allowed="1"', false)
            ->assertSee('data-availability-warning="VARNING: ENDAST 4 PLATSER KVAR"', false);
    }

    public function test_overcapacity_booking_is_saved_as_regular_booking(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning överbokning',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Nästan full för överbokning',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 10,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'NEARLY-FULL',
            'men_count' => 9,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 9,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.quick-store'), [
                'tour_id' => $tour->id,
                'men_count' => 4,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
            ])
            ->assertRedirect(route('admin.bookings.quick-create'))
            ->assertSessionHas('success', 'Bokning skapad.');

        $booking = Booking::query()->where('tour_id', $tour->id)->latest('id')->first();

        $this->assertNotNull($booking);
        $this->assertFalse($booking->is_waitlist);
        $this->assertSame(4, $booking->total_count);
    }

    public function test_regular_admin_booking_store_allows_overbooking_on_full_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $tour = Tour::query()->create([
            'title' => 'Full guidad tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 10,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'FULL-ALREADY',
            'men_count' => 10,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 10,
            'status' => 'confirmed',
        ]);

        $languageId = Language::query()->where('code', 'sv')->value('id');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.bookings.store'), [
                'tour_id' => $tour->id,
                'booking_name' => 'OVERBOOK-TEST',
                'contact_name' => 'Överbokning',
                'men_count' => 3,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'arrival_status' => 'booked',
                'languages' => [$languageId],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $booking = Booking::query()->where('booking_name', 'OVERBOOK-TEST')->first();

        $this->assertNotNull($booking);
        $this->assertFalse($booking->is_waitlist);
        $this->assertSame(13, (int) Booking::query()
            ->where('tour_id', $tour->id)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_count'));
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
