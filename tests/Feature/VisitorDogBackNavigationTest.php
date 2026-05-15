<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;
use App\Support\VisitorDogSupport;
use Tests\TestCase;

class VisitorDogBackNavigationTest extends TestCase
{
    public function test_admin_show_from_gallery_links_back_to_gallery_with_filters(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $dog = VisitorDog::factory()->create([
            'visit_date' => '2026-06-05',
            'photo_path' => 'visitor_dogs/2026/06/test.jpg',
        ]);

        $query = [
            'from_date' => '2026-06-01',
            'to_date' => '2026-06-10',
            'return' => VisitorDogSupport::RETURN_GALLERY,
        ];

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.show', array_merge(['visitorDog' => $dog], $query)))
            ->assertOk()
            ->assertSee('Till galleriet', false)
            ->assertSee('visitor-dogs/gallery?from_date=2026-06-01&amp;to_date=2026-06-10', false);
    }

    public function test_admin_destroy_from_gallery_returns_to_gallery_with_filters(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $dog = VisitorDog::factory()->create(['visit_date' => '2026-06-05']);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->delete(route('admin.visitor-dogs.destroy', $dog), [
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
                'return' => VisitorDogSupport::RETURN_GALLERY,
            ])
            ->assertRedirect(route('admin.visitor-dogs.gallery', [
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
            ]));
    }

    public function test_guide_show_preserves_mine_list_filters_on_back_link(): void
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'registered_by' => $user->id,
            'registered_as_role' => Roles::GUIDE,
            'visit_date' => '2026-06-05',
        ]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('visitor-dogs.show', [
                'visitorDog' => $dog,
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-10',
                'return' => VisitorDogSupport::RETURN_MINE,
            ]))
            ->assertOk()
            ->assertSee('Mina hundar', false)
            ->assertSee('besokshundar/mina?from_date=2026-06-01&amp;to_date=2026-06-10', false);
    }
}
