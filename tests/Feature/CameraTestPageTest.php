<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CameraTestPageTest extends TestCase
{
    public function test_guide_can_open_camera_test_page(): void
    {
        $user = $this->guideUser();

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.camera-test.create'))
            ->assertOk()
            ->assertSee('Kameratest', false)
            ->assertSee('getUserMedia', false)
            ->assertSee('camera-test-form', false)
            ->assertDontSee('capture="environment"', false);
    }

    public function test_guide_can_submit_camera_test_photo(): void
    {
        Storage::fake('public');

        $user = $this->guideUser();
        $file = UploadedFile::fake()->image('kameratest.jpg', 300, 200);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.camera-test.store'), [
                'photo' => $file,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertCount(1, Storage::disk('public')->allFiles('camera_tests'));
    }

    private function guideUser(): User
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        return $user;
    }
}
