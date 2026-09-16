<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Services\TourCoGuideService;
use App\Support\Roles;
use Tests\TestCase;

class AdminDashboardUpcomingToursTest extends TestCase
{
    public function test_dashboard_splits_today_and_upcoming_tour_lists(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $today = now()->toDateString();

        Tour::query()->create([
            'title' => 'Kommande tur idag',
            'tour_date' => $today,
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        Tour::query()->create([
            'title' => 'Kommande tur imorgon',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        Tour::query()->create([
            'title' => 'Tur om tio dagar',
            'tour_date' => now()->addDays(10)->toDateString(),
            'start_time' => '11:00',
            'end_time' => '12:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kommande turer idag', false)
            ->assertSee('Kommande tur idag', false)
            ->assertSee('Kommande tur imorgon', false)
            ->assertDontSee('Tur om tio dagar', false);
    }

    public function test_dashboard_can_show_upcoming_tours_for_thirty_days(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Tur om tjugo dagar',
            'tour_date' => now()->addDays(20)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard', ['ahead_days' => 30]))
            ->assertOk()
            ->assertSee('Tur om tjugo dagar', false)
            ->assertSee('30 dagar', false);
    }

    public function test_dashboard_ahead_list_excludes_today_even_when_only_today_has_planned_tours(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Dagens kommande tur',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHours(2)->format('H:i:s'),
            'end_time' => now()->addHours(3)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Dagens kommande tur', false)
            ->assertSee('Inga kommande turer de valda dagarna.', false);
    }

    public function test_admin_and_host_dashboard_show_co_guides_for_upcoming_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $host = $this->userWithRole(Roles::HOST);
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $lead = User::factory()->create(['name' => 'Huvudguide Anna']);
        $assistant = User::factory()->create(['name' => 'Assistent Erik']);
        $lead->assignRoles([$guideRole]);
        $assistant->assignRoles([$guideRole]);

        $tour = Tour::query()->create([
            'title' => 'Tur med medguide',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHours(2)->format('H:i:s'),
            'end_time' => now()->addHours(3)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $lead->id,
        ]);

        $tour->coGuides()->attach($assistant->id, [
            'role' => TourCoGuideService::ROLE_ASSISTANT,
            'notes' => 'Engelska grupp',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Huvudguide Anna', false)
            ->assertSee('Med: Assistent Erik (Assistent) — Engelska grupp', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee('Med: Assistent Erik (Assistent) — Engelska grupp', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
