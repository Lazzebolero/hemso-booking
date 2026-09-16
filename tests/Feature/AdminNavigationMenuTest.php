<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminNavigationMenuTest extends TestCase
{
    public function test_admin_dashboard_quick_actions_focus_on_daily_tasks(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Bokningssekvens', false)
            ->assertSee('Snabbtur', false)
            ->assertSee('Ny tur', false)
            ->assertSee('Färjetidtabell', false)
            ->assertSee(route('admin.ferry-timetable.index'), false);
    }

    public function test_admin_dashboard_quick_actions_exclude_ferry_adjustment(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk();

        preg_match('/<div class="page-actions dashboard-page-actions">.*?<\/div>/s', $response->getContent(), $matches);
        $quickActions = $matches[0] ?? '';

        $this->assertStringContainsString('ferry-timetable', $quickActions);
        $this->assertStringNotContainsString('ferry-adjustments', $quickActions);
    }

    public function test_admin_sidebar_start_day_section_and_daily_work_layout(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index'))
            ->assertOk()
            ->assertSee('Starta dagen', false)
            ->assertSee('Dagligt arbete', false)
            ->assertSee('Dagens guider', false)
            ->assertSee('Batch skapa turer', false)
            ->assertSee('Färjekorrigering', false)
            ->assertSee('Hundbilder', false)
            ->assertDontSee('>Bokningssekvens<', false)
            ->assertDontSee('>Snabbtur<', false)
            ->assertDontSee('>Ny tur<', false)
            ->assertDontSee('>Färjetidtabell<', false);

        $html = $response->getContent();

        $startPos = strpos($html, 'Starta dagen');
        $dailyPos = strpos($html, 'Dagligt arbete');
        $guidesPos = strpos($html, '>Dagens guider<');
        $batchPos = strpos($html, '>Batch skapa turer<');
        $ferryPos = strpos($html, '>Färjekorrigering<');
        $hundbilderPos = strpos($html, '>Hundbilder<');

        $this->assertNotFalse($startPos);
        $this->assertNotFalse($dailyPos);
        $this->assertLessThan($dailyPos, $startPos);
        $this->assertLessThan($batchPos, $guidesPos);
        $this->assertLessThan($ferryPos, $batchPos);
        $this->assertGreaterThan($dailyPos, $hundbilderPos);
    }

    public function test_host_dashboard_shows_same_daily_quick_actions_as_admin(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $response = $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.dashboard'))
            ->assertOk()
            ->assertSee('Bokningssekvens', false)
            ->assertSee('Snabbtur', false)
            ->assertSee('Ny tur', false)
            ->assertSee('Färjetidtabell', false)
            ->assertSee(route('host.ferry-timetable.index'), false);

        preg_match('/<div class="page-actions dashboard-page-actions">.*?<\/div>/s', $response->getContent(), $matches);
        $quickActions = $matches[0] ?? '';

        $this->assertStringContainsString('ferry-timetable', $quickActions);
        $this->assertStringNotContainsString('ferry-adjustments', $quickActions);
    }

    public function test_host_sidebar_shows_ferry_adjustment(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.tours.index'))
            ->assertOk()
            ->assertSee('Färjekorrigering', false);
    }

    public function test_host_can_view_ferry_timetable_page(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.ferry-timetable.index'))
            ->assertOk()
            ->assertSee('Färjetidtabell', false)
            ->assertSee('Strinningen', false)
            ->assertDontSee('Spara inställning', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
