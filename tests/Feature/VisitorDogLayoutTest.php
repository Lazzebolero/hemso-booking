<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class VisitorDogLayoutTest extends TestCase
{
    public function test_admin_visitor_dogs_index_uses_unified_staff_layout(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.index'))
            ->assertOk()
            ->assertSee('staff-page-stack', false)
            ->assertSee('page-title', false)
            ->assertSee('Besökshundar', false);
    }

    public function test_host_mine_index_uses_unified_staff_layout(): void
    {
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('visitor-dogs.index'))
            ->assertOk()
            ->assertSee('staff-page-stack', false)
            ->assertSee('Mina besökshundar', false);
    }

    public function test_guide_create_form_uses_guide_page_header(): void
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('visitor-dogs.create'))
            ->assertOk()
            ->assertSee('staff-page-stack', false)
            ->assertSee('section-title', false)
            ->assertSee('Besökshund', false);
    }
}
