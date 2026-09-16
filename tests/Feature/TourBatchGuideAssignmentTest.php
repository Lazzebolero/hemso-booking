<?php

namespace Tests\Feature;

use App\Models\DailyGuideOrder;
use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class TourBatchGuideAssignmentTest extends TestCase
{
    public function test_batch_create_assigns_guides_in_daily_order(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $date = now()->addDay()->toDateString();

        $guideA = $this->userWithRole(Roles::GUIDE, 'Guide Alfa');
        $guideB = $this->userWithRole(Roles::GUIDE, 'Guide Beta');

        DailyGuideOrder::query()->create([
            'guide_date' => $date,
            'user_id' => $guideA->id,
            'sort_order' => 1,
            'source' => DailyGuideOrder::SOURCE_MANUAL,
        ]);

        DailyGuideOrder::query()->create([
            'guide_date' => $date,
            'user_id' => $guideB->id,
            'sort_order' => 2,
            'source' => DailyGuideOrder::SOURCE_MANUAL,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.tours.batch-store'), [
                'tour_date' => $date,
                'first_tour' => '10:00',
                'last_tour' => '11:00',
                'interval' => '60',
                'tour_type_id' => $tourType->id,
                'max_participants' => 20,
                'assign_daily_guides' => '1',
                'skip_existing' => '1',
            ])
            ->assertRedirect(route('admin.tours.batch-create', ['tour_date' => $date]))
            ->assertSessionHas('success');

        $tours = Tour::query()
            ->whereDate('tour_date', $date)
            ->orderBy('start_time')
            ->get();

        $this->assertCount(2, $tours);
        $this->assertSame($guideA->id, $tours[0]->guide_id);
        $this->assertSame($guideB->id, $tours[1]->guide_id);
    }

    public function test_batch_create_continues_rotation_after_existing_tours(): void
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

        Tour::query()->create([
            'title' => 'Befintlig tur',
            'tour_date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_participants' => 20,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
            'guide_id' => $guideA->id,
        ]);

        Tour::query()->create([
            'title' => 'Befintlig tur två',
            'tour_date' => $date,
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
            'max_participants' => 20,
            'status' => 'planned',
            'tour_type_id' => $tourType->id,
            'guide_id' => $guideB->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.tours.batch-store'), [
                'tour_date' => $date,
                'first_tour' => '10:00',
                'last_tour' => '11:00',
                'interval' => '60',
                'tour_type_id' => $tourType->id,
                'max_participants' => 20,
                'assign_daily_guides' => '1',
                'skip_existing' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $newTours = Tour::query()
            ->whereDate('tour_date', $date)
            ->whereIn('start_time', ['10:00:00', '11:00:00'])
            ->orderBy('start_time')
            ->get();

        $this->assertCount(2, $newTours);
        $this->assertSame($guideC->id, $newTours[0]->guide_id);
        $this->assertSame($guideA->id, $newTours[1]->guide_id);
    }

    public function test_batch_create_can_skip_guide_assignment(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();
        $date = now()->addDay()->toDateString();

        $guide = $this->userWithRole(Roles::GUIDE, 'Guide Solo');

        DailyGuideOrder::query()->create([
            'guide_date' => $date,
            'user_id' => $guide->id,
            'sort_order' => 1,
            'source' => DailyGuideOrder::SOURCE_MANUAL,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.tours.batch-store'), [
                'tour_date' => $date,
                'first_tour' => '10:00',
                'last_tour' => '10:00',
                'interval' => '60',
                'tour_type_id' => $tourType->id,
                'max_participants' => 20,
                'skip_existing' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $tour = Tour::query()
            ->whereDate('tour_date', $date)
            ->where('start_time', '10:00:00')
            ->first();

        $this->assertNotNull($tour);
        $this->assertNull($tour->guide_id);
    }

    private function userWithRole(string $roleSlug, ?string $name = null): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create($name ? ['name' => $name] : []);
        $user->assignRoles([$role]);

        return $user;
    }
}
