<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\User;
use App\Services\TourEarlyStartService;
use App\Support\Roles;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TourEarlyStartTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_tour_more_than_fifteen_minutes_away_requires_confirmation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $service = app(TourEarlyStartService::class);
        $tour = $this->plannedTour('Tidig tur', '12:00:00');

        $this->assertTrue($service->requiresConfirmation($tour));
        $this->assertSame(120, $service->minutesUntilScheduledStart($tour));
        $this->assertStringContainsString('Tidig tur', $service->confirmationMessage($tour));
        $this->assertStringContainsString('12:00', $service->confirmationMessage($tour));
        $this->assertStringContainsString('2 timmar', $service->confirmationMessage($tour));
    }

    public function test_tour_within_fifteen_minutes_does_not_require_confirmation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $service = app(TourEarlyStartService::class);
        $tour = $this->plannedTour('Snart tur', '10:10:00');

        $this->assertFalse($service->requiresConfirmation($tour));
    }

    public function test_admin_cannot_start_tour_early_without_confirmation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->plannedTour('För tidig adminstart', '12:00:00');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.tours.show', $tour))
            ->post(route('admin.tours.start', $tour))
            ->assertRedirect(route('admin.tours.show', $tour))
            ->assertSessionHasErrors('tour');

        $this->assertSame('planned', $tour->fresh()->status);
    }

    public function test_admin_can_start_tour_early_with_confirmation_flag(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->plannedTour('Bekräftad tidig start', '12:00:00');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.tours.show', $tour))
            ->post(route('admin.tours.start', $tour), [
                'confirm_early_start' => '1',
            ])
            ->assertRedirect(route('admin.tours.show', $tour))
            ->assertSessionHas('success');

        $this->assertSame('started', $tour->fresh()->status);
    }

    public function test_host_cannot_start_tour_early_without_confirmation(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $host = $this->userWithRole(Roles::HOST);
        $tour = $this->plannedTour('För tidig värdstart', '12:00:00');

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->from(route('host.dashboard'))
            ->post(route('host.tours.start', $tour))
            ->assertRedirect(route('host.dashboard'))
            ->assertSessionHasErrors('tour');

        $this->assertSame('planned', $tour->fresh()->status);
    }

    public function test_host_can_start_tour_when_it_is_due(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $host = $this->userWithRole(Roles::HOST);
        $tour = $this->plannedTour('Värd startar i tid', '09:50:00');

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->from(route('host.dashboard'))
            ->post(route('host.tours.start', $tour))
            ->assertRedirect(route('host.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame('started', $tour->fresh()->status);
    }

    public function test_tour_show_includes_early_start_confirmation_attributes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-24 10:00:00'));

        $admin = $this->userWithRole(Roles::ADMIN);
        $tour = $this->plannedTour('Dialogtur', '12:00:00');

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.show', $tour))
            ->assertOk()
            ->assertSee('data-tour-early-start-form', false)
            ->assertSee('data-requires-early-start-confirm="1"', false)
            ->assertSee('Dialogtur', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function plannedTour(string $title, string $startTime): Tour
    {
        return Tour::query()->create([
            'title' => $title,
            'tour_date' => '2026-06-24',
            'start_time' => $startTime,
            'end_time' => '13:00:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);
    }
}
