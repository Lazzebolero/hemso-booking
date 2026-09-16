<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminTourBookingListTest extends TestCase
{
    public function test_admin_tour_show_lists_bookings_for_the_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tourWithBooking();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Bokningar på turen', false)
            ->assertSee('Familjen Test', false)
            ->assertSee('Anna Testsson', false)
            ->assertSee('0701234567', false)
            ->assertSee('SV', false)
            ->assertSee('4 totalt', false)
            ->assertSee(route('admin.bookings.edit', $tour->bookings->first()), false);
    }

    public function test_host_tour_show_lists_bookings_for_the_tour(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = $this->tourWithBooking();

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.show', $tour))
            ->assertOk()
            ->assertSee('Bokningar på turen', false)
            ->assertSee('Familjen Test', false)
            ->assertSee(route('host.bookings.edit', $tour->bookings->first()), false);
    }

    public function test_completed_tour_show_lists_bookings_with_edit_links(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = Tour::query()->create([
            'title' => 'Avslutad tur med bokning',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'completed',
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Historisk grupp',
            'contact_name' => 'Lisa Historisk',
            'men_count' => 3,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 3,
            'status' => 'confirmed',
            'arrival_status' => 'arrived',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Historisk grupp', false)
            ->assertSee('Lisa Historisk', false)
            ->assertSee('Genomförd tur — bokningar kan rättas i efterhand', false)
            ->assertSee('id="tour-bookings"', false)
            ->assertSee('from_tour=1', false);
    }

    public function test_archive_bookings_index_lists_historical_tour_bookings(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = Tour::query()->create([
            'title' => 'Arkivtur',
            'tour_date' => now()->subDays(2)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'max_participants' => 20,
            'status' => 'completed',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Arkivbokning synlig',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.index', ['scope' => 'archive']))
            ->assertOk()
            ->assertSee('Historiska och avslutade bokningar', false)
            ->assertSee('Arkivbokning synlig', false)
            ->assertSee('Arkivtur', false);
    }

    public function test_started_tour_show_lists_bookings_with_edit_links(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = Tour::query()->create([
            'title' => 'Pågående tur med bokningar',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'started',
            'started_at' => now()->subMinutes(15),
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Pågående grupp',
            'contact_name' => 'Live kontakt',
            'men_count' => 2,
            'women_count' => 1,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 3,
            'status' => 'confirmed',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.show', $tour))
            ->assertOk()
            ->assertSee('Pågående tur — bokningar kan uppdateras här.', false)
            ->assertSee('Pågående grupp', false)
            ->assertSee('id="tour-bookings"', false)
            ->assertSee('Redigera', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->put(route('host.bookings.update', $booking), [
                'from_tour' => '1',
                'tour_id' => $tour->id,
                'booking_name' => $booking->booking_name,
                'contact_name' => 'Uppdaterad under tur',
                'men_count' => 3,
                'women_count' => 1,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('host.tours.show', $tour))
            ->assertSessionHas('success');
    }

    public function test_host_can_edit_booking_on_completed_tour_from_tour_show(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = Tour::query()->create([
            'title' => 'Värd tur med bokning',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'completed',
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Grupp att rätta',
            'contact_name' => 'Ursprunglig kontakt',
            'men_count' => 2,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.show', $tour))
            ->assertOk()
            ->assertSee('id="tour-bookings"', false)
            ->assertSee('Grupp att rätta', false)
            ->assertSee('Redigera', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->put(route('host.bookings.update', $booking), [
                'from_tour' => '1',
                'tour_id' => $tour->id,
                'booking_name' => $booking->booking_name,
                'contact_name' => 'Rättad från turvy',
                'men_count' => 3,
                'women_count' => 1,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('host.tours.show', $tour))
            ->assertSessionHas('success');

        $this->assertSame('Rättad från turvy', $booking->fresh()->contact_name);
    }

    public function test_archive_tour_index_links_to_bookings_on_tour_show(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = Tour::query()->create([
            'title' => 'Arkivtur med länk',
            'tour_date' => now()->subDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'max_participants' => 20,
            'status' => 'completed',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Arkivgrupp',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index', ['scope' => 'archive']))
            ->assertOk()
            ->assertSee('Bokningar', false)
            ->assertSee(route('admin.tours.show', $tour).'#tour-bookings', false);
    }

    public function test_host_can_edit_booking_on_completed_tour_from_archive_list(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = Tour::query()->create([
            'title' => 'Värd arkivtur',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'max_participants' => 20,
            'status' => 'completed',
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Värd historisk bokning',
            'men_count' => 1,
            'women_count' => 1,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
            'status' => 'confirmed',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.bookings.index', ['scope' => 'archive']))
            ->assertOk()
            ->assertSee('Värd historisk bokning', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->put(route('host.bookings.update', $booking), [
                'tour_id' => $tour->id,
                'booking_name' => $booking->booking_name,
                'contact_name' => 'Rättad värd',
                'men_count' => 2,
                'women_count' => 2,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
            ])
            ->assertRedirect(route('host.bookings.index', ['scope' => 'archive']));

        $this->assertSame(4, (int) $booking->fresh()->total_count);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tourWithBooking(): Tour
    {
        $tour = Tour::query()->create([
            'title' => 'Bokningslista testtur',
            'description' => 'Tur med bokningar.',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Familjen Test',
            'contact_name' => 'Anna Testsson',
            'phone' => '0701234567',
            'email' => 'anna@example.com',
            'men_count' => 1,
            'women_count' => 1,
            'youth_count' => 1,
            'child_count' => 1,
            'total_count' => 4,
            'notes' => 'Vill gärna gå nära guiden.',
            'status' => 'confirmed',
            'arrival_status' => 'booked',
        ]);

        $language = Language::query()->firstOrCreate(
            ['code' => 'sv'],
            [
                'name' => 'Svenska',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 0,
            ]
        );

        $booking->languages()->attach($language);

        return $tour->load('bookings.languages');
    }
}
