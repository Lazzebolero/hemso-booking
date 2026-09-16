<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tour;
use App\Models\TourType;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuideTourShowResilienceTest extends TestCase
{
    public function test_guide_tour_show_works_when_tour_photos_table_is_missing(): void
    {
        if (! Schema::hasTable('tour_photos')) {
            $this->markTestSkipped('tour_photos table is not present in this database.');
        }

        Schema::drop('tour_photos');

        $guide = $this->userWithRole(Roles::GUIDE);
        $tour = $this->tourForGuide($guide);

        $this->actingAs($guide)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('guide.tours.show', $tour))
            ->assertOk()
            ->assertSee('data-guide-tour-root', false)
            ->assertDontSee('Ladda upp bild', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }

    private function tourForGuide(User $guide): Tour
    {
        $tourType = TourType::query()->create(['name' => 'Resilient turtyp']);

        return Tour::query()->create([
            'title' => 'Resilient tur',
            'tour_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'max_participants' => 20,
            'status' => 'planned',
            'guide_id' => $guide->id,
            'tour_type_id' => $tourType->id,
        ]);
    }
}
