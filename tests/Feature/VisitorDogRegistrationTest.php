<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitorDogRegistrationTest extends TestCase
{
    public function test_host_can_open_visitor_dog_create_form(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('visitor-dogs.create'))
            ->assertOk()
            ->assertSee('Besökshund', false);
    }

    public function test_guide_can_open_form_and_register_dog(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        Storage::fake('public');

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('visitor-dogs.create'))
            ->assertOk()
            ->assertSee('Besökshund', false);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('visitor-dogs.store'), [
                'dog_name' => 'Testhunden Rex',
                'breed' => 'Tax',
                'owner_phone' => '0701234567',
                'visit_date' => now()->format('Y-m-d'),
                'tour_start_time' => '10:30',
            ])
            ->assertRedirect(route('visitor-dogs.create'))
            ->assertSessionHas('success');

        $dog = VisitorDog::query()->where('dog_name', 'Testhunden Rex')->first();
        $this->assertNotNull($dog);
        $this->assertSame(Roles::GUIDE, $dog->registered_as_role);
        $this->assertSame($user->id, $dog->registered_by);
    }

    public function test_host_can_register_dog_with_photo(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        Storage::fake('public');

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$hostRole]);

        $file = UploadedFile::fake()->image('hund.jpg', 200, 200);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->post(route('visitor-dogs.store'), [
                'dog_name' => 'Bella',
                'visit_date' => now()->format('Y-m-d'),
                'photo' => $file,
            ])
            ->assertRedirect(route('visitor-dogs.create'));

        $dog = VisitorDog::query()->where('dog_name', 'Bella')->first();
        $this->assertNotNull($dog);
        $this->assertNotNull($dog->photo_path);
        Storage::disk('public')->assertExists($dog->photo_path);
    }

    public function test_restaurant_role_cannot_access_visitor_dog_form(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $restaurantRole = Role::query()->where('slug', Roles::RESTAURANT)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$restaurantRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('visitor-dogs.create'))
            ->assertForbidden();
    }

    public function test_admin_can_list_and_show_visitor_dogs(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $registrar = User::factory()->create();
        $registrar->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'dog_name' => 'List-test-hund',
            'visit_date' => now()->toDateString(),
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.index'))
            ->assertOk()
            ->assertSee('List-test-hund', false)
            ->assertSee('Hundbilder', false);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.show', $dog))
            ->assertOk()
            ->assertSee('List-test-hund', false);
    }

    public function test_admin_visitor_dogs_gallery_lists_only_dogs_with_photo_in_range(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $registrar = User::factory()->create();
        $registrar->assignRoles([$guideRole]);

        VisitorDog::factory()->create([
            'dog_name' => 'Galleri med foto',
            'visit_date' => '2026-06-01',
            'photo_path' => 'visitor_dogs/2026/06/x.jpg',
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        VisitorDog::factory()->create([
            'dog_name' => 'Galleri utan foto',
            'visit_date' => '2026-06-01',
            'photo_path' => null,
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.gallery', [
                'from_date' => '2026-06-01',
                'to_date' => '2026-06-01',
            ]))
            ->assertOk()
            ->assertSee('Galleri med foto', false)
            ->assertDontSee('Galleri utan foto', false);
    }

    public function test_admin_visitor_dog_photo_route_streams_file(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        Storage::fake('public');

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $registrar = User::factory()->create();
        $registrar->assignRoles([$guideRole]);

        $path = 'visitor_dogs/2026/05/test-photo.jpg';
        Storage::disk('public')->put($path, '%PDF-1.4 fake for binary');

        $dog = VisitorDog::factory()->create([
            'dog_name' => 'Foto-test',
            'visit_date' => now()->toDateString(),
            'photo_path' => $path,
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.photo', $dog))
            ->assertOk();
    }

    public function test_admin_can_delete_visitor_dog_and_removes_photo_file(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        Storage::fake('public');

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $registrar = User::factory()->create();
        $registrar->assignRoles([$guideRole]);

        $path = 'visitor_dogs/2026/05/del-me.jpg';
        Storage::disk('public')->put($path, 'binary');

        $dog = VisitorDog::factory()->create([
            'dog_name' => 'Raderas',
            'visit_date' => '2026-05-10',
            'photo_path' => $path,
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->from(route('admin.visitor-dogs.index', ['from_date' => '2026-05-10', 'to_date' => '2026-05-10']))
            ->delete(route('admin.visitor-dogs.destroy', $dog), [
                'from_date' => '2026-05-10',
                'to_date' => '2026-05-10',
            ])
            ->assertRedirect(route('admin.visitor-dogs.index', [
                'from_date' => '2026-05-10',
                'to_date' => '2026-05-10',
            ]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('visitor_dogs', ['id' => $dog->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_host_can_view_visitor_dogs_list_and_detail(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $registrar = User::factory()->create();
        $registrar->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'dog_name' => 'Värd-list-hund',
            'visit_date' => now()->toDateString(),
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $host = User::factory()->create();
        $host->assignRoles([$hostRole]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.visitor-dogs.index'))
            ->assertOk()
            ->assertSee('Värd-list-hund', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.visitor-dogs.show', $dog))
            ->assertOk()
            ->assertSee('Värd-list-hund', false);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('host.visitor-dogs.gallery', [
                'from_date' => now()->toDateString(),
                'to_date' => now()->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_host_can_delete_visitor_dog(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();

        $registrar = User::factory()->create();
        $registrar->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'dog_name' => 'Värd-radera',
            'visit_date' => '2026-05-11',
            'registered_by' => $registrar->id,
            'registered_as_role' => Roles::GUIDE,
        ]);

        $host = User::factory()->create();
        $host->assignRoles([$hostRole]);

        $this->actingAs($host)
            ->withSession(['active_role' => Roles::HOST])
            ->delete(route('host.visitor-dogs.destroy', $dog), [
                'from_date' => '2026-05-11',
                'to_date' => '2026-05-11',
            ])
            ->assertRedirect(route('host.visitor-dogs.index', [
                'from_date' => '2026-05-11',
                'to_date' => '2026-05-11',
            ]));

        $this->assertDatabaseMissing('visitor_dogs', ['id' => $dog->id]);
    }

    public function test_store_validates_required_dog_name(): void
    {
        if (! Schema::hasTable('visitor_dogs')) {
            $this->markTestSkipped('visitor_dogs-tabellen saknas.');
        }

        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('visitor-dogs.store'), [
                'visit_date' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('dog_name');
    }
}
