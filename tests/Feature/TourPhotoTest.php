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

class TourPhotoTest extends TestCase
{
    public function test_guide_can_upload_photo_to_own_tour(): void
    {
        Storage::fake('public');

        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $file = UploadedFile::fake()->image('foretagsgrupp.jpg', 640, 480);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.photos.store', $tour), [
                'photo' => $file,
                'caption' => 'Företagsgruppen vid kanonen',
            ])
            ->assertRedirect(route('guide.tours.show', $tour));

        $photo = TourPhoto::query()->firstOrFail();

        $this->assertSame($tour->id, $photo->tour_id);
        $this->assertSame($guide->id, $photo->uploaded_by);
        $this->assertSame('Företagsgruppen vid kanonen', $photo->caption);
        $this->assertSame('foretagsgrupp.jpg', $photo->original_name);
        Storage::disk('public')->assertExists($photo->image_path);
    }

    public function test_guide_can_upload_camera_photo_to_own_tour(): void
    {
        Storage::fake('public');

        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.photos.store', $tour), [
                'photo_camera' => UploadedFile::fake()->image('kamera.jpg', 640, 480),
            ])
            ->assertRedirect(route('guide.tours.show', $tour));

        $photo = TourPhoto::query()->firstOrFail();

        $this->assertSame('kamera.jpg', $photo->original_name);
        Storage::disk('public')->assertExists($photo->image_path);
    }

    public function test_guide_tour_page_renders_camera_upload_field(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertSee('name="photo_camera"', false)
            ->assertSee('name="photo_library"', false)
            ->assertSee('accept="image/*"', false)
            ->assertSee('capture="environment"', false)
            ->assertSee('Välj antingen kamera eller bildbibliotek', false);
    }

    public function test_guide_cannot_upload_photo_to_another_guides_tour(): void
    {
        Storage::fake('public');

        $guide = $this->userWithRole(Roles::GUIDE);
        $otherGuide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($otherGuide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.tours.photos.store', $tour), [
                'photo' => UploadedFile::fake()->image('nope.jpg', 200, 200),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tour_photos', 0);
    }

    public function test_guide_can_view_photo_for_own_tour(): void
    {
        Storage::fake('public');

        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $photo = $this->photoForTour($tour, $guide, 'tour_photos/2026/06/guide.jpg');

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.photos.show', ['tour' => $tour, 'tourPhoto' => $photo]))
            ->assertOk();
    }

    public function test_admin_can_download_original_tour_photo(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole(Roles::ADMIN);
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $photo = $this->photoForTour($tour, $guide, 'tour_photos/2026/06/original.jpg');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.photos.download', ['tour' => $tour, 'tourPhoto' => $photo]))
            ->assertOk()
            ->assertDownload('kundbild.jpg');
    }

    public function test_admin_can_delete_tour_photo_and_file(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole(Roles::ADMIN);
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $photo = $this->photoForTour($tour, $guide, 'tour_photos/2026/06/delete-me.jpg');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.tours.photos.destroy', ['tour' => $tour, 'tourPhoto' => $photo]))
            ->assertRedirect(route('admin.tours.show', $tour));

        $this->assertDatabaseMissing('tour_photos', ['id' => $photo->id]);
        Storage::disk('public')->assertMissing('tour_photos/2026/06/delete-me.jpg');
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
            'title' => 'Företagstur',
            'tour_date' => now()->toDateString(),
            'start_time' => '11:00',
            'max_participants' => 30,
            'guide_id' => $guide->id,
            'status' => 'planned',
        ]);
    }

    private function photoForTour(Tour $tour, User $guide, string $path): TourPhoto
    {
        Storage::disk('public')->put($path, 'fake image bytes');

        return TourPhoto::query()->create([
            'tour_id' => $tour->id,
            'uploaded_by' => $guide->id,
            'image_path' => $path,
            'original_name' => 'kundbild.jpg',
            'caption' => 'Kundbild',
            'mime_type' => 'image/jpeg',
            'size' => 16,
            'taken_at' => now(),
        ]);
    }
}
