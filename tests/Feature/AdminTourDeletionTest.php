<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Language;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourPhoto;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTourDeletionTest extends TestCase
{
    public function test_admin_can_delete_tour_and_its_bookings(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tourWithBookings(bookingCount: 2);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.tours.destroy', $tour))
            ->assertRedirect(route('admin.tours.index', ['scope' => 'upcoming']))
            ->assertSessionHas('success');

        $bookingIds = $tour->bookings->pluck('id')->all();

        $this->assertDatabaseMissing('tours', ['id' => $tour->id]);

        foreach ($bookingIds as $bookingId) {
            $this->assertDatabaseMissing('bookings', ['id' => $bookingId]);
        }
    }

    public function test_host_cannot_delete_tour(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $tour = $this->tourWithBookings();

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.index'))
            ->assertOk()
            ->assertDontSee(route('admin.tours.destroy', $tour), false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->delete(route('admin.tours.destroy', $tour))
            ->assertForbidden();

        $this->assertDatabaseHas('tours', ['id' => $tour->id]);
    }

    public function test_deleting_tour_removes_photos_from_storage(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = Tour::query()->create([
            'title' => 'Tur med bild',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 10,
            'status' => 'completed',
        ]);

        $path = 'tour-photos/test.jpg';
        Storage::disk('public')->put($path, 'fake');

        TourPhoto::query()->create([
            'tour_id' => $tour->id,
            'path' => $path,
            'original_name' => 'test.jpg',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.tours.destroy', $tour))
            ->assertRedirect();

        $this->assertDatabaseMissing('tour_photos', ['tour_id' => $tour->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_tour_index_shows_delete_action(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->tourWithBookings();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index', ['scope' => 'archive']))
            ->assertOk()
            ->assertSee(route('admin.tours.destroy', $tour), false)
            ->assertSee('Ta bort', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tourWithBookings(int $bookingCount = 1): Tour
    {
        $tour = Tour::query()->create([
            'title' => 'Testtur att radera',
            'description' => 'Ska tas bort med bokningar.',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'cancelled',
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

        for ($i = 0; $i < $bookingCount; $i++) {
            $booking = Booking::query()->create([
                'tour_id' => $tour->id,
                'booking_name' => 'Bokning '.$i,
                'contact_name' => 'Kontakt '.$i,
                'total_count' => 2,
                'status' => 'confirmed',
            ]);

            $booking->languages()->attach($language);
        }

        return $tour->load('bookings');
    }
}
