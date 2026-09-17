<?php

namespace Tests\Feature;

use App\Models\RestaurantFunction;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Support\Roles;
use App\Support\ShiftCoverage;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class SettingsStaffingGoalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_settings_staffing_goals_include_restaurant_functions_from_database(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        RestaurantFunction::query()->create([
            'slug' => 'buffe',
            'name' => 'Buffé',
            'sort_order' => 25,
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('name="staffing_goal_buffe"', false)
            ->assertSee('Buffé', false)
            ->assertSee('name="staffing_goal_kassa"', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.update'), [
                'default_tour_capacity' => 25,
                'timezone' => 'Europe/Stockholm',
                'staffing_goal_buffe' => 2,
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('2', Setting::query()->where('key', 'staffing_goal_buffe')->value('value'));

        $requirements = ShiftCoverage::requirements(Carbon::parse('2026-07-15'));

        $this->assertSame('Buffé', $requirements['restaurant_functions']['buffe']['label'] ?? null);
        $this->assertSame(2, $requirements['restaurant_functions']['buffe']['minimum'] ?? null);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRoles([$role]);

        return $user;
    }
}
