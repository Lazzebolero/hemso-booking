<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class GuideOfflineQueueSyncTest extends TestCase
{
    public function test_guide_participant_update_accepts_empty_count_fields_like_offline_queue(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Offline sync turtyp']);

        $tour = Tour::query()->create([
            'title' => 'Offline sync tur',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 40,
            'status' => 'started',
            'started_at' => now(),
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $booking = Booking::query()->create([
            'tour_id' => $tour->id,
            'booking_name' => 'Snabbtur grupp',
            'men_count' => 0,
            'women_count' => 0,
            'youth_count' => 0,
            'child_count' => 0,
            'unspecified_count' => 12,
            'total_count' => 12,
            'status' => 'confirmed',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->patchJson(route('guide.bookings.update-participants', $booking), [
                'men_count' => '',
                'women_count' => '',
                'youth_count' => '',
                'child_count' => '',
                'unspecified_count' => '15',
                'status' => 'confirmed',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Bokningen uppdaterades.');

        $booking->refresh();

        $this->assertSame(15, $booking->unspecified_count);
        $this->assertSame(15, $booking->total_count);
    }

    public function test_guide_complete_tour_returns_json_for_offline_queue_flush(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Offline complete turtyp']);

        $tour = Tour::query()->create([
            'title' => 'Offline complete tur',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 40,
            'status' => 'started',
            'started_at' => now(),
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->postJson(route('guide.tours.complete', $tour))
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('redirect_url', route('guide.dashboard'));

        $this->assertSame('completed', $tour->fresh()->status);
    }

    public function test_duplicate_guide_complete_tour_is_idempotent_for_offline_queue(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tourType = TourType::query()->create(['name' => 'Offline duplicate complete turtyp']);

        $tour = Tour::query()->create([
            'title' => 'Offline duplicate complete tur',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 40,
            'status' => 'completed',
            'started_at' => now()->subHour(),
            'ended_at' => now(),
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->postJson(route('guide.tours.complete', $tour))
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('message', 'Turen är redan avslutad.');
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
