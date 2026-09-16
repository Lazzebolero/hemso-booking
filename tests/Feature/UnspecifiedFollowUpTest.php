<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class UnspecifiedFollowUpTest extends TestCase
{
    public function test_admin_can_view_unspecified_follow_up_page(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.unspecified-follow-up'))
            ->assertOk()
            ->assertSee('Ospecificerade bokningar', false)
            ->assertSee('Bokningar att komplettera', false);
    }

    public function test_page_lists_bookings_with_unspecified_count_only(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur med ospecificerade',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Ospecificerad grupp',
            'men_count' => 0,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 12,
            'total_count' => 12,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Specificerad grupp',
            'men_count' => 5,
            'women_count' => 5,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 0,
            'total_count' => 10,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.unspecified-follow-up', [
                'from' => now()->startOfWeek()->toDateString(),
                'to' => now()->endOfWeek()->toDateString(),
                'scope' => 'all',
            ]))
            ->assertOk()
            ->assertSee('Ospecificerad grupp', false)
            ->assertSee('O12', false)
            ->assertDontSee('Specificerad grupp', false);
    }

    public function test_completed_scope_filters_past_tours(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $upcomingTour = Tour::query()->create([
            'title' => 'Kommande tur',
            'tour_date' => now()->addDays(3)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $completedTour = Tour::query()->create([
            'title' => 'Genomförd tur',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'completed',
            'tour_type_id' => $tourType->id,
        ]);

        Booking::query()->create([
            'tour_id' => $upcomingTour->id,
            'booking_name' => 'Kommande ospecificerad',
            'unspecified_count' => 4,
            'total_count' => 4,
            'status' => 'confirmed',
        ]);

        Booking::query()->create([
            'tour_id' => $completedTour->id,
            'booking_name' => 'Historisk ospecificerad',
            'unspecified_count' => 6,
            'total_count' => 6,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.unspecified-follow-up', [
                'from' => now()->subWeek()->toDateString(),
                'to' => now()->addWeek()->toDateString(),
                'scope' => 'completed',
            ]))
            ->assertOk()
            ->assertSee('Historisk ospecificerad', false)
            ->assertDontSee('Kommande ospecificerad', false);
    }

    public function test_host_can_access_unspecified_follow_up_page(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.statistics.unspecified-follow-up'))
            ->assertOk()
            ->assertSee('Ospecificerade bokningar', false);
    }

    public function test_admin_can_quick_complete_participant_distribution_from_follow_up_page(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur att komplettera',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Snabbtur ospecificerad',
            'men_count' => 0,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 12,
            'total_count' => 12,
            'status' => 'confirmed',
        ]);

        $from = now()->startOfWeek()->toDateString();
        $to = now()->endOfWeek()->toDateString();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->patch(route('admin.bookings.quick-update-participants', $booking), [
                'men_count' => 5,
                'women_count' => 4,
                'youth_count' => 2,
                'child_count' => 1,
                'status' => 'confirmed',
                'return_to' => 'unspecified-follow-up',
                'follow_up_booking_id' => $booking->id,
                'follow_up_from' => $from,
                'follow_up_to' => $to,
                'follow_up_scope' => 'all',
            ])
            ->assertRedirect(route('admin.statistics.unspecified-follow-up', [
                'from' => $from,
                'to' => $to,
                'scope' => 'all',
            ]))
            ->assertSessionHas('success');

        $booking->refresh();

        $this->assertSame(5, $booking->men_count);
        $this->assertSame(4, $booking->women_count);
        $this->assertSame(2, $booking->youth_count);
        $this->assertSame(1, $booking->child_count);
        $this->assertSame(0, $booking->unspecified_count);
        $this->assertSame(12, $booking->total_count);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.unspecified-follow-up', [
                'from' => $from,
                'to' => $to,
                'scope' => 'all',
            ]))
            ->assertOk()
            ->assertDontSee('Snabbtur ospecificerad', false);
    }

    public function test_follow_up_page_shows_inline_participant_fields(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur med fält',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:15',
            'max_participants' => 30,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
        ]);

        Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Inline test',
            'unspecified_count' => 8,
            'total_count' => 8,
            'status' => 'confirmed',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.statistics.unspecified-follow-up', [
                'from' => now()->startOfWeek()->toDateString(),
                'to' => now()->endOfWeek()->toDateString(),
                'scope' => 'all',
            ]))
            ->assertOk()
            ->assertSee('name="men_count"', false)
            ->assertSee('return_to', false)
            ->assertSee('Spara', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
