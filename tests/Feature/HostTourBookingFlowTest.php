<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\NotificationService;
use App\Support\Roles;
use Tests\TestCase;

class HostTourBookingFlowTest extends TestCase
{
    public function test_host_can_create_booking_from_tour_and_returns_to_tour_show(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = Tour::query()->create([
            'title' => 'Värd testtur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        $this->mock(NotificationService::class)
            ->shouldReceive('sendBookingConfirmation')
            ->once();

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('host.bookings.store'), [
                'from_tour' => '1',
                'tour_id' => $tour->id,
                'booking_name' => 'Värdgrupp',
                'contact_name' => 'Anna Värd',
                'phone' => '0701234567',
                'email' => 'anna@example.com',
                'men_count' => 5,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'status' => 'confirmed',
                'arrival_status' => 'booked',
                'languages' => [Language::query()->where('code', 'sv')->value('id')],
            ])
            ->assertRedirect(route('host.tours.show', $tour))
            ->assertSessionHas('success');

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.show', $tour))
            ->assertOk()
            ->assertSee('Värdgrupp', false);
    }

    public function test_host_bookings_index_loads_with_active_bookings(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = Tour::query()->create([
            'title' => 'Aktiv tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 30,
            'status' => 'planned',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Testgrupp',
            'contact_name' => 'Kontakt',
            'men_count' => 4,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'total_count' => 4,
            'status' => 'confirmed',
            'arrival_status' => 'booked',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.bookings.index'))
            ->assertOk()
            ->assertSee('Bokningar', false)
            ->assertSee('Testgrupp', false)
            ->assertDontSee('Historik', false);
    }

    public function test_host_bookings_index_loads_without_admin_activity_log_routes(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.bookings.index'))
            ->assertOk()
            ->assertSee('Bokningar', false)
            ->assertDontSee('Historik', false);
    }

    public function test_host_quick_booking_redirects_to_host_quick_create(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $guidedType = TourType::query()->create([
            'name' => 'Guidad visning värdtest',
            'sort_order' => 1,
            'is_active' => true,
            'include_in_booking_sequence' => true,
            'default_duration_minutes' => 75,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Snabb tur',
            'tour_type_id' => $guidedType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
            'max_participants' => 20,
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
            ->assertRedirect(route('host.bookings.quick-create'))
            ->assertSessionHas('success');
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
