<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminCompletedTourEditTest extends TestCase
{
    public function test_admin_can_open_edit_form_for_completed_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->assertSee('Redigera tur', false)
            ->assertSee('turen är avslutad', false);
    }

    public function test_admin_can_update_completed_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), [
                'title' => 'Korrigerad avslutad tur',
                'tour_type_id' => $tour->tour_type_id,
                'description' => 'Uppdaterad beskrivning',
                'tour_date' => $tour->tour_date->format('Y-m-d'),
                'start_time' => '11:00',
                'end_time' => '12:00',
                'max_participants' => 25,
                'guide_id' => $tour->guide_id,
                'status' => 'completed',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $tour->refresh();

        $this->assertSame('Korrigerad avslutad tur', $tour->title);
        $this->assertSame('completed', $tour->status);
        $this->assertSame('11:00', substr((string) $tour->start_time, 0, 5));
    }

    public function test_completed_tour_show_has_edit_link(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTour();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee(route('admin.tours.edit', $tour), false);
    }

    public function test_admin_can_open_booking_edit_from_completed_tour_show(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTourWithBooking();

        $booking = $tour->bookings->first();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee(url(route('admin.bookings.edit', $booking, false)), false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.bookings.edit', ['booking' => $booking, 'from_tour' => 1]))
            ->assertOk()
            ->assertSee('Redigera bokning', false)
            ->assertSee('Avslutad testtur', false)
            ->assertSee('rätta bokningen i efterhand', false);
    }

    public function test_admin_can_update_booking_from_completed_tour_show_and_returns_to_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTourWithBooking();
        $booking = $tour->bookings->first();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.bookings.update', $booking), [
                'from_tour' => '1',
                'tour_id' => $tour->id,
                'booking_name' => $booking->booking_name,
                'contact_name' => 'Korrigerad från turvy',
                'phone' => $booking->phone,
                'email' => $booking->email,
                'men_count' => 2,
                'women_count' => 1,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'arrival_status' => 'booked',
            ])
            ->assertRedirect(route('admin.tours.show', $tour))
            ->assertSessionHas('success');

        $this->assertSame('Korrigerad från turvy', $booking->fresh()->contact_name);
    }

    public function test_admin_can_update_booking_on_completed_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->completedTourWithBooking();
        $booking = $tour->bookings->first();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.bookings.update', $booking), [
                'tour_id' => $tour->id,
                'booking_name' => $booking->booking_name,
                'contact_name' => 'Uppdaterad kontakt',
                'phone' => $booking->phone,
                'email' => $booking->email,
                'men_count' => 2,
                'women_count' => 1,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'arrival_status' => 'booked',
                'notes' => 'Anteckning efter avslutad tur.',
            ])
            ->assertRedirect(route('admin.bookings.index', ['scope' => 'archive']));

        $this->assertSame('Uppdaterad kontakt', $booking->fresh()->contact_name);
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

    private function completedTourWithBooking(): Tour
    {
        $tour = $this->completedTour();

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Arkivbokning',
            'contact_name' => 'Erik Exempel',
            'phone' => '0709998877',
            'email' => 'erik@example.com',
            'men_count' => 1,
            'women_count' => 1,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 2,
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
