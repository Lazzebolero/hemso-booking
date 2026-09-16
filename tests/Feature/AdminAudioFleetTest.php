<?php

namespace Tests\Feature;

use App\Models\AudioDevice;
use App\Models\AudioGroup;
use App\Models\Loudspeaker;
use App\Models\Role;
use App\Models\Sound;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAudioFleetTest extends TestCase
{
    public function test_admin_can_view_audio_fleet_index(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.audio.index'))
            ->assertOk()
            ->assertSee('Ljud', false);
    }

    public function test_admin_can_register_device_upload_sound_and_control_playback(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.devices.store'), [
                'id' => 6,
                'name' => 'Bunkerberry 6',
                'location' => 'Utställning A',
                'hostname' => 'bunkerberry-6',
                'is_active' => '1',
                'channels' => ['left'],
            ])
            ->assertRedirect(route('admin.audio.devices.show', 6));

        $device = AudioDevice::query()->findOrFail(6);
        $channel = Loudspeaker::query()->where('device_id', 6)->where('side', 'left')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.sounds.store'), [
                'name' => 'Testljud',
                'audio_file' => UploadedFile::fake()->create('test.mp3', 100, 'audio/mpeg'),
            ])
            ->assertRedirect();

        $sound = Sound::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->patch(route('admin.audio.channels.update', $channel), [
                'sound_id' => $sound->id,
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.channels.play', $channel))
            ->assertRedirect();

        $this->assertTrue($channel->fresh()->status);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.channels.stop', $channel))
            ->assertRedirect();

        $this->assertFalse($channel->fresh()->status);
        $this->assertSame($device->id, 6);
        $this->assertStringContainsString('/storage/audio/', $sound->path);
    }

    public function test_non_admin_cannot_access_audio_fleet(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('admin.audio.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_group_and_control_group_playback(): void
    {
        Storage::fake('public');
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.groups.store'), [
                'name' => 'Entré',
                'description' => 'Foajé och entréhall',
                'sort_order' => 1,
            ])
            ->assertRedirect();

        $group = AudioGroup::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.devices.store'), [
                'id' => 6,
                'name' => 'Bunkerberry 6',
                'audio_group_id' => $group->id,
                'is_active' => '1',
                'channels' => ['left'],
            ])
            ->assertRedirect(route('admin.audio.devices.show', 6));

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.devices.store'), [
                'id' => 7,
                'name' => 'Bunkerberry 7',
                'audio_group_id' => $group->id,
                'is_active' => '1',
                'channels' => ['left'],
            ])
            ->assertRedirect(route('admin.audio.devices.show', 7));

        $channelSix = Loudspeaker::query()->where('device_id', 6)->where('side', 'left')->firstOrFail();
        $channelSeven = Loudspeaker::query()->where('device_id', 7)->where('side', 'left')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.sounds.store'), [
                'name' => 'Gruppljud',
                'audio_file' => UploadedFile::fake()->create('group.mp3', 100, 'audio/mpeg'),
            ])
            ->assertRedirect();

        $sound = Sound::query()->firstOrFail();

        $channelSix->update(['sound_id' => $sound->id]);
        $channelSeven->update(['sound_id' => $sound->id]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.groups.play', $group))
            ->assertRedirect();

        $this->assertTrue($channelSix->fresh()->status);
        $this->assertTrue($channelSeven->fresh()->status);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.audio.groups.stop', $group))
            ->assertRedirect();

        $this->assertFalse($channelSix->fresh()->status);
        $this->assertFalse($channelSeven->fresh()->status);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.audio.groups.show', $group))
            ->assertOk()
            ->assertSee('Entré', false)
            ->assertSee('Bunkerberry 6', false)
            ->assertSee('Bunkerberry 7', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
