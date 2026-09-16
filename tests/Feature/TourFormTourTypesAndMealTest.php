<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class TourFormTourTypesAndMealTest extends TestCase
{
    public function test_admin_tour_create_form_lists_tour_types_and_meal_default(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $tourType = TourType::query()->firstOrCreate(
            ['name' => 'Testturtyp mat'],
            [
                'sort_order' => 0,
                'is_active' => true,
                'is_default' => false,
                'default_duration_minutes' => 60,
            ]
        );

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.create'))
            ->assertOk()
            ->assertSee('Testturtyp mat', false)
            ->assertSee('Mat (standard för bokningar)', false)
            ->assertSee('Ej mat', false)
            ->assertSee('Med mat', false)
            ->assertSee('name="default_includes_meal"', false);
    }

    public function test_admin_can_create_tour_with_meal_default(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.tours.store'), [
                'title' => 'Tur med matstandard',
                'tour_type_id' => $tourType->id,
                'tour_date' => now()->addDay()->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'max_participants' => 20,
                'status' => 'planned',
                'default_includes_meal' => '1',
            ])
            ->assertRedirect(route('admin.tours.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tours', [
            'title' => 'Tur med matstandard',
            'default_includes_meal' => true,
        ]);
    }

    public function test_admin_tour_edit_form_shows_guide_field_first(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur för guideordning',
            'tour_type_id' => $tourType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.edit', $tour))
            ->assertOk()
            ->assertSeeInOrder([
                'Guide',
                'Namn på tur',
            ], false);
    }

    public function test_admin_tour_create_form_keeps_guide_field_after_core_fields(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.tours.create'))
            ->assertOk()
            ->assertSeeInOrder([
                'Namn på tur',
                'Guide',
            ], false);
    }

    public function test_admin_can_update_tour_meal_default_on_edit(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);
        $tourType = TourType::query()->firstOrFail();

        $tour = Tour::query()->create([
            'title' => 'Tur utan mat',
            'tour_type_id' => $tourType->id,
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'max_participants' => 20,
            'status' => 'planned',
            'default_includes_meal' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->put(route('admin.tours.update', $tour), [
                'title' => 'Tur utan mat',
                'tour_type_id' => $tourType->id,
                'tour_date' => $tour->tour_date->format('Y-m-d'),
                'start_time' => '10:00',
                'end_time' => '11:00',
                'max_participants' => 20,
                'status' => 'planned',
                'default_includes_meal' => '1',
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertTrue($tour->fresh()->default_includes_meal);
        $this->assertStringContainsString('Med mat', session('success'));
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
