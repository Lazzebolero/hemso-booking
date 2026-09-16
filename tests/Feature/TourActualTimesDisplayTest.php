<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class TourActualTimesDisplayTest extends TestCase
{
    public function test_completed_tour_show_displays_actual_start_end_and_duration(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $startedAt = now()->subDay()->setTime(10, 7);
        $endedAt = now()->subDay()->setTime(11, 22);

        $tour = Tour::query()->create([
            'title' => 'Avslutad tur med tider',
            'tour_date' => now()->subDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'completed',
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Verklig start', false)
            ->assertSee($startedAt->format('Y-m-d H:i'), false)
            ->assertSee('Verklig slut', false)
            ->assertSee($endedAt->format('Y-m-d H:i'), false)
            ->assertSee('Turen tog', false)
            ->assertSee('1 h 15 min', false);
    }

    public function test_planned_tour_show_hides_actual_times(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $tour = Tour::query()->create([
            'title' => 'Planerad tur utan faktiska tider',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertDontSee('Verklig start', false)
            ->assertDontSee('Verklig slut', false)
            ->assertDontSee('Turen tog', false);
    }

    public function test_started_tour_show_displays_actual_start_only(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $startedAt = now()->setTime(9, 45);

        $tour = Tour::query()->create([
            'title' => 'Pågående tur',
            'tour_date' => now()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'started',
            'started_at' => $startedAt,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('Verklig start', false)
            ->assertSee($startedAt->format('Y-m-d H:i'), false)
            ->assertDontSee('Verklig slut', false)
            ->assertDontSee('Turen tog', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
