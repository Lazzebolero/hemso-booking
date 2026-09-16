<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Services\TourAutoCompleteService;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TourTypeDurationAndAutoCompleteTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_quick_tour_uses_default_tour_type_duration(): void
    {
        Carbon::setTestNow('2026-01-15 10:00:00');

        $tourType = TourType::query()->where('is_default', true)->first()
            ?? TourType::query()->orderBy('id')->firstOrFail();
        $tourType->update(['default_duration_minutes' => 95]);

        $guide = $this->userWithRole(Roles::GUIDE);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 8,
                'language_ids' => [],
            ])
            ->assertRedirect(route('guide.dashboard'));

        $tour = Tour::query()->latest('id')->first();

        $this->assertSame('11:35:00', substr((string) $tour->end_time, 0, 8));
    }

    public function test_auto_complete_completes_tour_after_planned_end_plus_grace(): void
    {
        Carbon::setTestNow('2026-06-15 11:31:00');

        $tourType = TourType::query()->create([
            'name' => 'Auto-avslut test',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'default_duration_minutes' => 75,
            'auto_complete_enabled' => true,
            'auto_complete_grace_minutes' => 15,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Pågående tur',
            'tour_date' => '2026-06-15',
            'start_time' => '10:00:00',
            'end_time' => '11:15:00',
            'baseline_end_time' => '11:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => Carbon::parse('2026-06-15 10:00:00'),
            'tour_type_id' => $tourType->id,
        ]);

        $this->artisan('tours:auto-complete')->assertSuccessful();

        $tour->refresh();

        $this->assertSame('completed', $tour->status);
        $this->assertNotNull($tour->auto_completed_at);
        $this->assertTrue($tour->wasAutoCompleted());
    }

    public function test_auto_complete_waits_until_grace_period_has_passed(): void
    {
        Carbon::setTestNow('2026-06-15 11:20:00');

        $tourType = TourType::query()->create([
            'name' => 'Marginal test',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'default_duration_minutes' => 75,
            'auto_complete_enabled' => true,
            'auto_complete_grace_minutes' => 15,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Pågående tur väntar',
            'tour_date' => '2026-06-15',
            'start_time' => '10:00:00',
            'end_time' => '11:15:00',
            'baseline_end_time' => '11:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => Carbon::parse('2026-06-15 10:00:00'),
            'tour_type_id' => $tourType->id,
        ]);

        $this->artisan('tours:auto-complete')->assertSuccessful();

        $tour->refresh();

        $this->assertSame('started', $tour->status);
        $this->assertNull($tour->auto_completed_at);
    }

    public function test_auto_complete_skips_extended_tour(): void
    {
        Carbon::setTestNow('2026-06-15 11:50:00');

        $tourType = TourType::query()->create([
            'name' => 'Förlängd test',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'default_duration_minutes' => 75,
            'auto_complete_enabled' => true,
            'auto_complete_grace_minutes' => 15,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Förlängd tur',
            'tour_date' => '2026-06-15',
            'start_time' => '10:00:00',
            'end_time' => '11:45:00',
            'baseline_end_time' => '11:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => Carbon::parse('2026-06-15 10:00:00'),
            'tour_type_id' => $tourType->id,
        ]);

        $this->assertTrue(app(TourAutoCompleteService::class)->isExtended($tour));

        $this->artisan('tours:auto-complete')->assertSuccessful();

        $tour->refresh();

        $this->assertSame('started', $tour->status);
        $this->assertNull($tour->auto_completed_at);
    }

    public function test_auto_complete_skips_when_disabled_for_tour_type(): void
    {
        Carbon::setTestNow('2026-06-15 11:40:00');

        $tourType = TourType::query()->create([
            'name' => 'Utan auto-avslut',
            'sort_order' => 1,
            'is_active' => true,
            'is_default' => false,
            'default_duration_minutes' => 75,
            'auto_complete_enabled' => false,
            'auto_complete_grace_minutes' => 15,
        ]);

        $tour = Tour::query()->create([
            'title' => 'Manuell avslutning',
            'tour_date' => '2026-06-15',
            'start_time' => '10:00:00',
            'end_time' => '11:15:00',
            'baseline_end_time' => '11:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => Carbon::parse('2026-06-15 10:00:00'),
            'tour_type_id' => $tourType->id,
        ]);

        $this->artisan('tours:auto-complete')->assertSuccessful();

        $tour->refresh();

        $this->assertSame('started', $tour->status);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
