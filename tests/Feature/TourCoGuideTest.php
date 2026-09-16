<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Services\TourCoGuideService;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TourCoGuideTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_can_assign_co_guides_when_updating_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');
        $trainee = $this->elevUser('Trainee Lisa');

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'assistant_guide_ids' => [$assistant->id],
                'trainee_guide_ids' => [$trainee->id],
                'co_guide_notes' => [
                    $assistant->id => 'Engelska grupp',
                    $trainee->id => 'Utbildning',
                ],
            ]))
            ->assertRedirect(route('admin.dashboard'));

        $tour->load('coGuides');

        $this->assertSame(
            [$assistant->id],
            $tour->coGuides->where('pivot.role', TourCoGuideService::ROLE_ASSISTANT)->pluck('id')->all()
        );
        $this->assertSame(
            [$trainee->id],
            $tour->coGuides->where('pivot.role', TourCoGuideService::ROLE_TRAINEE)->pluck('id')->all()
        );
        $this->assertSame('Engelska grupp', $tour->coGuides->firstWhere('id', $assistant->id)?->pivot?->notes);
        $this->assertSame('Utbildning', $tour->coGuides->firstWhere('id', $trainee->id)?->pivot?->notes);
    }

    public function test_tour_show_displays_co_guide_notes(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead);
        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Assistent Erik (Assistent) — Engelska grupp', false);
    }

    public function test_co_guide_sees_assignment_note_on_dashboard(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead, '13:10:00');
        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $this->actingAs($assistant)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Engelska grupp', false);
    }

    public function test_tour_show_displays_co_guides(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead);
        $tour->coGuides()->attach($assistant->id, ['role' => TourCoGuideService::ROLE_ASSISTANT]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Huvudguide', false)
            ->assertSee('Medguider', false)
            ->assertSee('Assistent Erik (Assistent)', false);
    }

    public function test_co_guide_sees_read_only_assignment_on_dashboard(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead, '13:10:00');
        $tour->coGuides()->attach($assistant->id, ['role' => TourCoGuideService::ROLE_ASSISTANT]);

        $this->actingAs($assistant)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Turer du följer med på', false)
            ->assertSee('Huvudguide', false)
            ->assertSee('Din roll:', false)
            ->assertSee('Assistent', false)
            ->assertSee('13:10', false)
            ->assertDontSee('Starta tur', false);
    }

    public function test_co_guide_sees_started_tour_in_follow_along_section(): void
    {
        Carbon::setTestNow('2026-06-17 13:30:00');

        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = Tour::query()->create([
            'title' => 'Pågående medguide',
            'tour_date' => now()->toDateString(),
            'start_time' => '13:00:00',
            'end_time' => '14:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now(),
            'guide_id' => $lead->id,
        ]);
        $tour->coGuides()->attach($assistant->id, ['role' => TourCoGuideService::ROLE_ASSISTANT]);

        $this->actingAs($assistant)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('Turer du följer med på', false)
            ->assertSee('Pågående', false);
    }

    public function test_lead_guide_still_sees_tour_in_guide_dashboard(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead, '13:10:00');
        $tour->coGuides()->attach($assistant->id, ['role' => TourCoGuideService::ROLE_ASSISTANT]);

        $this->actingAs($lead)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.dashboard'))
            ->assertOk()
            ->assertSee('13:10', false);
    }

    public function test_co_guide_is_blocked_from_starting_quick_tour_when_tour_is_due(): void
    {
        Carbon::setTestNow('2026-06-17 13:10:00');

        $lead = $this->guideUser('Huvudguide');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead, '13:10:00');
        $tour->coGuides()->attach($assistant->id, ['role' => TourCoGuideService::ROLE_ASSISTANT]);

        $this->actingAs($assistant)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('quick-tours.store'), [
                'participant_count' => 4,
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('quick_tour');

        $this->assertSame(0, Tour::query()->where('title', 'like', 'Snabbtur%')->count());
    }

    public function test_elev_can_be_assigned_as_trainee_on_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $elev = $this->elevUser('Elev Anna');

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'trainee_guide_ids' => [$elev->id],
                'co_guide_notes' => [
                    $elev->id => 'Följer med som elev',
                ],
            ]))
            ->assertRedirect(route('admin.dashboard'));

        $tour->load('coGuides');

        $this->assertSame(
            [$elev->id],
            $tour->coGuides->where('pivot.role', TourCoGuideService::ROLE_TRAINEE)->pluck('id')->all()
        );

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->assertSee('Elev Anna', false);
    }

    public function test_host_can_be_assigned_as_trainee_on_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $host = $this->staffUser('Värd Erik', Roles::HOST);

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'trainee_guide_ids' => [$host->id],
            ]))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(
            [$host->id],
            $tour->fresh()->coGuides->where('pivot.role', TourCoGuideService::ROLE_TRAINEE)->pluck('id')->all()
        );

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->assertSee('Värd Erik', false);
    }

    public function test_restaurant_user_can_be_assigned_as_trainee_on_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $restaurant = $this->staffUser('Restaurang Lisa', Roles::RESTAURANT);

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'trainee_guide_ids' => [$restaurant->id],
            ]))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSame(
            [$restaurant->id],
            $tour->fresh()->coGuides->where('pivot.role', TourCoGuideService::ROLE_TRAINEE)->pluck('id')->all()
        );
    }

    public function test_guide_cannot_be_assigned_as_trainee(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $guideTrainee = $this->guideUser('Guide i utbildning');

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'trainee_guide_ids' => [$guideTrainee->id],
            ]))
            ->assertSessionHasErrors('trainee_guide_ids');

        $this->assertSame(0, $tour->fresh()->coGuides()->count());
    }

    public function test_user_with_elev_and_guide_roles_is_not_trainee_candidate(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $mixed = $this->elevUser('Både elev och guide');
        $mixed->assignRoles([Role::query()->where('slug', Roles::GUIDE)->firstOrFail()]);

        $tour = $this->plannedTour($lead);

        $candidates = app(TourCoGuideService::class)->traineeCandidatesForDate($tour->tour_date);

        $this->assertFalse($candidates->contains('id', $mixed->id));

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'trainee_guide_ids' => [$mixed->id],
            ]))
            ->assertSessionHasErrors('trainee_guide_ids');
    }

    public function test_elev_cannot_be_assigned_as_assistant(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');
        $elev = $this->elevUser('Elev Anna');

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'assistant_guide_ids' => [$elev->id],
            ]))
            ->assertSessionHasErrors('assistant_guide_ids');

        $this->assertSame(0, $tour->fresh()->coGuides()->count());
    }

    public function test_cannot_assign_lead_guide_as_co_guide(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide');

        $tour = $this->plannedTour($lead);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), $this->tourPayload($tour, $lead, [
                'assistant_guide_ids' => [$lead->id],
            ]))
            ->assertSessionHasErrors('assistant_guide_ids');

        $this->assertSame(0, $tour->fresh()->coGuides()->count());
    }

    public function test_tour_index_shows_co_guides(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $lead = $this->guideUser('Huvudguide Anna');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = $this->plannedTour($lead);
        $tour->update(['title' => 'Tur i listan']);
        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index'))
            ->assertOk()
            ->assertSee('Tur i listan', false)
            ->assertSee('Med: Assistent Erik (Assistent) — Engelska grupp', false);
    }

    public function test_restaurant_board_shows_co_guides_without_controller_decorate(): void
    {
        $lead = $this->guideUser('Huvudguide Lasse');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = Tour::query()->create([
            'title' => 'Vytest utan decorate',
            'tour_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '09:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now()->setTime(8, 54),
            'guide_id' => $lead->id,
        ]);

        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $html = view('partials.admin.tour-guide-display-block', [
            'tour' => $tour->load('guide'),
        ])->render();

        $this->assertStringContainsString('Huvudguide Lasse', $html);
        $this->assertStringContainsString('Med: Assistent Erik (Assistent) — Engelska grupp', $html);
    }

    public function test_restaurant_board_shows_co_guides_for_ongoing_tour(): void
    {
        $restaurantRole = Role::query()->where('slug', Roles::RESTAURANT)->firstOrFail();
        $restaurantUser = User::factory()->create();
        $restaurantUser->assignRoles([$restaurantRole]);

        $lead = $this->guideUser('Huvudguide Lasse');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = Tour::query()->create([
            'title' => 'Restaurangtavla med medguide',
            'tour_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '09:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now()->setTime(8, 54),
            'guide_id' => $lead->id,
        ]);

        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $this->actingAs($restaurantUser)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('restaurant.dashboard'))
            ->assertOk()
            ->assertSee('Restaurangtavla med medguide', false)
            ->assertSee('Huvudguide Lasse', false)
            ->assertSee('Med: Assistent Erik (Assistent) — Engelska grupp', false)
            ->assertSee('Turen startade 08:54', false);

        $this->actingAs($restaurantUser)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('restaurant-statistik.dashboard'))
            ->assertOk()
            ->assertSee('Med: Assistent Erik (Assistent) — Engelska grupp', false);
    }

    public function test_restaurant_statistics_dashboard_shows_co_guides_for_ongoing_tour(): void
    {
        $lead = $this->guideUser('Huvudguide Lasse');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = Tour::query()->create([
            'title' => 'Pågående restaurangtur med medguide',
            'tour_date' => now()->toDateString(),
            'start_time' => '08:00:00',
            'end_time' => '09:15:00',
            'max_participants' => 30,
            'status' => 'started',
            'started_at' => now()->setTime(8, 54),
            'guide_id' => $lead->id,
        ]);

        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Pågående restaurangtur med medguide', false)
            ->assertSee('Huvudguide Lasse', false)
            ->assertSee('Med: Assistent Erik (Assistent) — Engelska grupp', false)
            ->assertSee('Turen startade 08:54', false);
    }

    public function test_restaurant_statistics_dashboard_shows_co_guides(): void
    {
        $lead = $this->guideUser('Huvudguide Anna');
        $assistant = $this->guideUser('Assistent Erik');

        $tour = Tour::query()->create([
            'title' => 'Restaurangtur med medguide',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHours(2)->format('H:i:s'),
            'end_time' => now()->addHours(3)->format('H:i:s'),
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $lead->id,
            'default_includes_meal' => true,
        ]);

        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_TRAINEE,
            'notes' => 'Utbildning',
        ]);

        $this->withSession(['restaurant_statistics_auth' => true])
            ->get(route('restaurant-statistics.dashboard'))
            ->assertOk()
            ->assertSee('Restaurangtur med medguide', false)
            ->assertSee('Med: Assistent Erik (Trainee) — Utbildning', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function guideUser(string $name): User
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $guide = User::factory()->create(['name' => $name]);
        $guide->assignRoles([$guideRole]);

        return $guide;
    }

    private function elevUser(string $name): User
    {
        return $this->staffUser($name, Roles::ELEV, false);
    }

    private function staffUser(string $name, string $roleSlug, bool $isActive = true): User
    {
        $role = Role::query()->firstOrCreate(
            ['slug' => $roleSlug],
            ['name' => Roles::labels()[$roleSlug] ?? $roleSlug, 'description' => 'Testroll'],
        );

        $user = User::factory()->create([
            'name' => $name,
            'is_active' => $isActive,
        ]);
        $user->assignRoles([$role]);

        return $user;
    }

    private function plannedTour(User $lead, string $startTime = '10:00:00'): Tour
    {
        return Tour::query()->create([
            'title' => 'Medguidetest',
            'tour_date' => now()->toDateString(),
            'start_time' => $startTime,
            'end_time' => '11:15:00',
            'max_participants' => 30,
            'status' => 'planned',
            'guide_id' => $lead->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function tourPayload(Tour $tour, User $lead, array $extra = []): array
    {
        return array_merge([
            'title' => $tour->title,
            'tour_date' => $tour->tour_date->toDateString(),
            'start_time' => substr((string) $tour->start_time, 0, 5),
            'end_time' => substr((string) $tour->end_time, 0, 5),
            'max_participants' => $tour->max_participants,
            'guide_id' => $lead->id,
            'status' => 'planned',
            'default_includes_meal' => '0',
            'ripple_subsequent_guides' => '0',
        ], $extra);
    }
}
