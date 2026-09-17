<?php

namespace Tests\Feature;

use App\Models\FacilityMemory;
use App\Models\ReportLocation;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FacilityMemoryTest extends TestCase
{
    public function test_guide_can_open_memory_create_form(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.memories.create'))
            ->assertOk()
            ->assertSee('Spara minne', false)
            ->assertSee('Spela in', false)
            ->assertSee('navigator.mediaDevices.getUserMedia', false);
    }

    public function test_guide_can_submit_text_memory(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);
        $location = ReportLocation::query()->create([
            'name' => 'Mansköket',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.memories.store'), [
                'type' => FacilityMemory::TYPE_TEXT,
                'body' => 'Besökaren berättade om vaktgång vid mansköket 1973.',
                'location_id' => $location->id,
                'era_text' => 'ca 1973',
                'tour_id' => $tour->id,
                'consent_given' => '1',
            ]);

        $memory = FacilityMemory::query()->first();

        $this->assertNotNull($memory);
        $response
            ->assertRedirect(route('guide.memories.show', $memory))
            ->assertSessionHas('success');
        $this->assertSame(FacilityMemory::TYPE_TEXT, $memory->type);
        $this->assertSame('Mansköket', $memory->location_text);
        $this->assertSame(FacilityMemory::STATUS_SUBMITTED, $memory->status);
        $this->assertSame((int) $guide->id, (int) $memory->collected_by);
    }

    public function test_guide_can_submit_audio_memory(): void
    {
        Storage::fake('public');

        $guide = $this->userWithRole(Roles::GUIDE);
        $file = UploadedFile::fake()->create('minne.webm', 120, 'audio/webm');

        $response = $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.memories.store'), [
                'type' => FacilityMemory::TYPE_AUDIO,
                'audio' => $file,
                'audio_duration_seconds' => 95,
                'context_note' => 'Besökare berättade om kasern 3.',
                'location_text' => 'Kasern 3',
                'consent_given' => '1',
            ]);

        $memory = FacilityMemory::query()->first();

        $this->assertNotNull($memory);
        $response
            ->assertRedirect(route('guide.memories.show', $memory))
            ->assertSessionHas('success');
        $this->assertSame(FacilityMemory::TYPE_AUDIO, $memory->type);
        $this->assertSame(FacilityMemory::CONSENT_RECORDED, $memory->consent_type);
        $this->assertNotNull($memory->audio_path);
        Storage::disk('public')->assertExists($memory->audio_path);
    }

    public function test_text_memory_requires_consent(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->from(route('guide.memories.create'))
            ->post(route('guide.memories.store'), [
                'type' => FacilityMemory::TYPE_TEXT,
                'body' => 'Ett minne utan samtycke.',
            ])
            ->assertRedirect(route('guide.memories.create'))
            ->assertSessionHasErrors('consent_given');

        $this->assertDatabaseCount('facility_memories', 0);
    }

    public function test_guide_cannot_attach_memory_to_another_guides_tour(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $otherGuide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($otherGuide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('guide.memories.store'), [
                'type' => FacilityMemory::TYPE_TEXT,
                'body' => 'Försök koppla till annans tur.',
                'tour_id' => $tour->id,
                'consent_given' => '1',
            ])
            ->assertSessionHasErrors('tour_id');

        $this->assertDatabaseCount('facility_memories', 0);
    }

    public function test_admin_can_view_memory_inbox_and_detail(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $memory = FacilityMemory::factory()->create([
            'body' => 'Unikt arkivminne för adminvy.',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.facility-memories.index'))
            ->assertOk()
            ->assertSee('Anläggningsminnen', false)
            ->assertSee('Unikt arkivminne', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.facility-memories.show', $memory))
            ->assertOk()
            ->assertSee('Unikt arkivminne för adminvy.', false);

        $this->assertSame(FacilityMemory::STATUS_READ, $memory->fresh()->status);
    }

    public function test_admin_can_stream_audio_memory(): void
    {
        Storage::fake('public');

        $admin = $this->userWithRole(Roles::ADMIN);
        $path = 'facility_memories/2026/07/test.webm';
        Storage::disk('public')->put($path, 'fake-audio-content');

        $memory = FacilityMemory::factory()->audio()->create([
            'audio_path' => $path,
            'audio_mime_type' => 'audio/webm',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.facility-memories.audio', $memory))
            ->assertOk();
    }

    public function test_guide_cannot_access_admin_memory_routes(): void
    {
        $guide = $this->userWithRole(Roles::GUIDE);
        $memory = FacilityMemory::factory()->create();

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('admin.facility-memories.index'))
            ->assertForbidden();
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
        $tourType = TourType::query()->create(['name' => 'Minnestur']);

        return Tour::query()->create([
            'title' => 'Tur för minnestest',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);
    }
}
