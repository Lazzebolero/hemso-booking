<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class AdminTourArchiveNavigationTest extends TestCase
{
    public function test_admin_tour_index_has_clear_navigation_between_upcoming_and_archive(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.index'))
            ->assertOk()
            ->assertSee('Välj vilka turer som ska visas', false)
            ->assertSee('Visa aktiva och kommande turer', false)
            ->assertSee('Visa genomförda turer / arkiv', false)
            ->assertSee('scope=archive', false);
    }
}
