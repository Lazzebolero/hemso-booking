<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourPhoto;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TourPhotoUploadTest extends TestCase
{
    public function test_guide_can_open_dedicated_tour_photo_page(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.photos.create', $tour))
            ->assertOk()
            ->assertSee('Ladda upp turbild', false)
            ->assertSee('Starta kamera', false)
            ->assertSee('navigator.mediaDevices.getUserMedia', false)
            ->assertSee('name="photo"', false)
            ->assertDontSee('capture="environment"', false);
    }

    public function test_guide_can_upload_tour_photo(): void
    {
        Storage::fake('public');

        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $file = UploadedFile::fake()->image('turbild.jpg', 640, 480);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.photos.store', $tour), [
                'photo' => $file,
                'caption' => 'Gruppbild vid kanonen',
            ])
            ->assertRedirect(route('guide.tours.show', $tour))
            ->assertSessionHas('success');

        $photo = TourPhoto::query()->where('tour_id', $tour->id)->first();
        $this->assertNotNull($photo);
        $this->assertSame('Gruppbild vid kanonen', $photo->caption);
        Storage::disk('public')->assertExists($photo->path);
    }

    public function test_guide_tour_page_shows_photo_section_and_upload_link(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        TourPhoto::query()->create([
            'tour_id' => $tour->id,
            'uploaded_by' => $guide->id,
            'path' => 'tour_photos/test/turbild.jpg',
            'original_name' => 'turbild.jpg',
            'caption' => 'Synlig turbild',
        ]);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertSee('Bilder från turen', false)
            ->assertSee('Ladda upp bild', false)
            ->assertSee('Synlig turbild', false)
            ->assertSee('guide/tours/'.$tour->id.'/photos/create', false);
    }

    public function test_admin_tour_page_shows_uploaded_photos(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        TourPhoto::query()->create([
            'tour_id' => $tour->id,
            'uploaded_by' => $guide->id,
            'path' => 'tour_photos/test/admin.jpg',
            'original_name' => 'admin.jpg',
            'caption' => 'Admin ser bilden',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Bilder från turen', false)
            ->assertSee('Admin ser bilden', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tourForGuide(User $guide): Tour
    {
        return Tour::query()->create([
            'title' => 'Bildtesttur',
            'description' => 'Tur för bildtest.',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'guide_id' => $guide->id,
            'status' => 'planned',
        ]);
    }
}
