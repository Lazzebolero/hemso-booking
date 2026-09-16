<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminDashboardTourStartTest extends TestCase
{
    public function test_admin_dashboard_shows_start_button_in_upcoming_tours_today_section(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Kommande tur idag med start',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addMinutes(45)->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $tour = Tour::query()->where('title', 'Kommande tur idag med start')->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kommande turer idag', false)
            ->assertSee('Kommande tur idag med start', false)
            ->assertSee('Starta tur', false)
            ->assertSee(route('admin.tours.start', $tour), false);
    }

    public function test_admin_dashboard_shows_start_button_for_planned_tour_today(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Planerad tur idag',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->addHour()->format('H:i:s'),
            'end_time' => now()->addHours(2)->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Starta tur', false)
            ->assertSee(route('admin.tours.start', Tour::query()->where('title', 'Planerad tur idag')->first()), false);
    }

    public function test_host_can_start_tour_from_dashboard(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $tour = Tour::query()->create([
            'title' => 'Värd startar tur',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->subMinutes(20)->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee('Starta tur', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->from(route('host.dashboard'))
            ->post(route('host.tours.start', $tour))
            ->assertRedirect(route('host.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame('started', $tour->fresh()->status);
        $this->assertNotNull($tour->fresh()->started_at);
    }

    public function test_dashboard_does_not_show_start_button_for_started_tour(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        Tour::query()->create([
            'title' => 'Redan startad tur',
            'tour_date' => now()->toDateString(),
            'start_time' => now()->subHour()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'max_participants' => 20,
            'status' => 'started',
            'started_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Redan startad tur', false);
        $response->assertDontSee(route('admin.tours.start', Tour::query()->where('title', 'Redan startad tur')->first()), false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
