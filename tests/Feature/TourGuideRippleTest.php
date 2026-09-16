<?php

namespace Tests\Feature;

use App\Models\DailyGuideOrder;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class TourGuideRippleTest extends TestCase
{
    public function test_changing_guide_ripples_to_subsequent_planned_tours(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $date = now()->addDay()->toDateString();

        $guideA = $this->userWithRole(Roles::GUIDE, 'Guide Alfa');
        $guideB = $this->userWithRole(Roles::GUIDE, 'Guide Beta');
        $guideC = $this->userWithRole(Roles::GUIDE, 'Guide Gamma');

        foreach ([$guideA, $guideB, $guideC] as $index => $guide) {
            DailyGuideOrder::query()->create([
                'guide_date' => $date,
                'user_id' => $guide->id,
                'sort_order' => $index + 1,
                'source' => DailyGuideOrder::SOURCE_MANUAL,
            ]);
        }

        $tour10 = $this->createTour($date, '10:00:00', $guideA->id, $tourType->id);
        $tour11 = $this->createTour($date, '11:00:00', $guideB->id, $tourType->id);
        $tour12 = $this->createTour($date, '12:00:00', $guideC->id, $tourType->id);
        $tour13 = $this->createTour($date, '13:00:00', $guideA->id, $tourType->id);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour12), $this->tourPayload($tour12, $guideB->id, $tourType->id))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame($guideB->id, $tour12->fresh()->guide_id);
        $this->assertSame($guideC->id, $tour13->fresh()->guide_id);
        $this->assertSame($guideA->id, $tour10->fresh()->guide_id);
        $this->assertSame($guideB->id, $tour11->fresh()->guide_id);
    }

    public function test_ripple_skips_started_and_completed_tours(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $date = now()->addDay()->toDateString();

        $guideA = $this->userWithRole(Roles::GUIDE, 'Guide Alfa');
        $guideB = $this->userWithRole(Roles::GUIDE, 'Guide Beta');
        $guideC = $this->userWithRole(Roles::GUIDE, 'Guide Gamma');

        foreach ([$guideA, $guideB, $guideC] as $index => $guide) {
            DailyGuideOrder::query()->create([
                'guide_date' => $date,
                'user_id' => $guide->id,
                'sort_order' => $index + 1,
                'source' => DailyGuideOrder::SOURCE_MANUAL,
            ]);
        }

        $tour10 = $this->createTour($date, '10:00:00', $guideA->id, $tourType->id);
        $tour11 = $this->createTour($date, '11:00:00', $guideB->id, $tourType->id, 'started');
        $tour12 = $this->createTour($date, '12:00:00', $guideC->id, $tourType->id);
        $tour13 = $this->createTour($date, '13:00:00', $guideA->id, $tourType->id);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour10), $this->tourPayload($tour10, $guideC->id, $tourType->id))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($guideC->id, $tour10->fresh()->guide_id);
        $this->assertSame($guideB->id, $tour11->fresh()->guide_id);
        $this->assertSame($guideB->id, $tour12->fresh()->guide_id);
        $this->assertSame($guideC->id, $tour13->fresh()->guide_id);
    }

    public function test_ripple_can_be_disabled_for_single_tour_change(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $date = now()->addDay()->toDateString();

        $guideA = $this->userWithRole(Roles::GUIDE, 'Guide Alfa');
        $guideB = $this->userWithRole(Roles::GUIDE, 'Guide Beta');
        $guideC = $this->userWithRole(Roles::GUIDE, 'Guide Gamma');

        foreach ([$guideA, $guideB, $guideC] as $index => $guide) {
            DailyGuideOrder::query()->create([
                'guide_date' => $date,
                'user_id' => $guide->id,
                'sort_order' => $index + 1,
                'source' => DailyGuideOrder::SOURCE_MANUAL,
            ]);
        }

        $tour11 = $this->createTour($date, '11:00:00', $guideB->id, $tourType->id);
        $tour12 = $this->createTour($date, '12:00:00', $guideC->id, $tourType->id);

        $payload = $this->tourPayload($tour11, $guideA->id, $tourType->id);
        unset($payload['ripple_subsequent_guides']);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour11), $payload)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame($guideA->id, $tour11->fresh()->guide_id);
        $this->assertSame($guideC->id, $tour12->fresh()->guide_id);
    }

    private function createTour(
        string $date,
        string $startTime,
        ?int $guideId,
        int $tourTypeId,
        string $status = 'planned'
    ): Tour {
        return Tour::query()->create([
            'title' => 'Tur '.$startTime,
            'tour_date' => $date,
            'start_time' => $startTime,
            'end_time' => '12:00:00',
            'max_participants' => 20,
            'status' => $status,
            'guide_id' => $guideId,
            'tour_type_id' => $tourTypeId,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function tourPayload(Tour $tour, ?int $guideId, int $tourTypeId): array
    {
        return [
            'title' => $tour->title,
            'tour_type_id' => $tourTypeId,
            'tour_date' => $tour->tour_date->format('Y-m-d'),
            'start_time' => substr((string) $tour->start_time, 0, 5),
            'end_time' => substr((string) $tour->end_time, 0, 5),
            'max_participants' => 20,
            'guide_id' => $guideId,
            'status' => $tour->status,
            'ripple_subsequent_guides' => '1',
        ];
    }

    private function userWithRole(string $roleSlug, ?string $name = null): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create($name ? ['name' => $name] : []);
        $user->assignRoles([$role]);

        return $user;
    }
}
