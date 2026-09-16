<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffVisitorDogTest extends TestCase
{
    public function test_admin_visitor_dogs_index_shows_new_dog_button(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.index'))
            ->assertOk()
            ->assertSee('Ny besökshund', false)
            ->assertSee(route('admin.visitor-dogs.create'), false);
    }

    public function test_admin_dashboard_does_not_show_visitor_dog_form(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Besökshundar idag', false)
            ->assertDontSee('dashboard_dog_name', false);
    }

    public function test_admin_can_register_visitor_dog_without_photo(): void
    {
        $admin = $this->userWithRole(Roles::ADMIN);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.create'))
            ->assertOk()
            ->assertSee('Ny besökshund', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('admin.visitor-dogs.store'), [
                'dog_name' => 'Lista Rex',
                'breed' => 'Tax',
                'owner_phone' => '0701234567',
                'tour_start_time' => '11:30',
                'visit_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('admin.visitor-dogs.index'))
            ->assertSessionHas('success');

        $dog = VisitorDog::query()->where('dog_name', 'Lista Rex')->first();

        $this->assertNotNull($dog);
        $this->assertNull($dog->photo_path);
        $this->assertTrue($dog->needsPhoto());
        $this->assertSame(Roles::ADMIN, $dog->registered_as_role);
        $this->assertSame($admin->id, $dog->registered_by);
    }

    public function test_host_can_register_visitor_dog_and_see_missing_photo_on_index(): void
    {
        $host = $this->userWithRole(Roles::HOST);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('host.visitor-dogs.store'), [
                'dog_name' => 'Värdhund Bella',
                'visit_date' => now()->toDateString(),
            ])
            ->assertRedirect(route('host.visitor-dogs.index'))
            ->assertSessionHas('success');

        $dog = VisitorDog::query()->where('dog_name', 'Värdhund Bella')->firstOrFail();

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.visitor-dogs.index'))
            ->assertOk()
            ->assertSee('Värdhund Bella', false)
            ->assertSee('Saknar bild', false)
            ->assertSee(route('visitor-dogs.edit', $dog), false)
            ->assertSee('Lägg till bild', false);
    }

    public function test_host_can_add_photo_to_staff_registered_dog_from_app_form(): void
    {
        Storage::fake('public');

        $host = $this->userWithRole(Roles::HOST);
        $admin = $this->userWithRole(Roles::ADMIN);

        $dog = VisitorDog::query()->create([
            'dog_name' => 'Behöver bild',
            'visit_date' => now()->toDateString(),
            'registered_by' => $admin->id,
            'registered_as_role' => Roles::ADMIN,
        ]);

        $file = UploadedFile::fake()->image('hund.jpg', 300, 300);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->put(route('visitor-dogs.update', $dog), [
                'dog_name' => 'Behöver bild',
                'visit_date' => now()->toDateString(),
                'photo' => $file,
            ])
            ->assertRedirect(route('visitor-dogs.show', $dog));

        $dog->refresh();

        $this->assertNotNull($dog->photo_path);
        $this->assertFalse($dog->needsPhoto());
        Storage::disk('public')->assertExists($dog->photo_path);
    }

    public function test_host_sees_other_peoples_dogs_needing_photo_in_mobile_list(): void
    {
        $host = $this->userWithRole(Roles::HOST);
        $admin = $this->userWithRole(Roles::ADMIN);

        VisitorDog::factory()->create([
            'dog_name' => 'Adminhund utan bild',
            'visit_date' => now()->toDateString(),
            'registered_by' => $admin->id,
            'registered_as_role' => Roles::ADMIN,
            'photo_path' => null,
        ]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('visitor-dogs.index'))
            ->assertOk()
            ->assertSee('Saknar bild', false)
            ->assertSee('Adminhund utan bild', false)
            ->assertSee('Lägg till bild', false);
    }

    private function userWithRole(string $roleSlug): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        return $user;
    }
}
