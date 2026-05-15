<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Models\VisitorDog;
use App\Support\Roles;
use App\Support\VisitorDogActivityLogger;
use Tests\TestCase;

class VisitorDogActivityLogTest extends TestCase
{
    public function test_guide_registration_creates_activity_log(): void
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->post(route('visitor-dogs.store'), [
                'dog_name' => 'Loggad hund',
                'visit_date' => now()->format('Y-m-d'),
            ])
            ->assertRedirect(route('visitor-dogs.create'));

        $dog = VisitorDog::query()->where('dog_name', 'Loggad hund')->first();
        $this->assertNotNull($dog);

        $log = ActivityLog::query()
            ->where('entity_type', VisitorDogActivityLogger::ENTITY_TYPE)
            ->where('entity_id', $dog->id)
            ->where('action', 'created')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Loggad hund', $log->new_values['dog_name'] ?? null);
    }

    public function test_update_and_delete_are_logged(): void
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $dog = VisitorDog::factory()->create([
            'registered_by' => $user->id,
            'registered_as_role' => Roles::GUIDE,
            'dog_name' => 'Före',
        ]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->put(route('visitor-dogs.update', $dog), [
                'dog_name' => 'Efter',
                'visit_date' => $dog->visit_date->format('Y-m-d'),
            ])
            ->assertRedirect(route('visitor-dogs.show', $dog));

        $updateLog = ActivityLog::query()
            ->where('entity_type', VisitorDogActivityLogger::ENTITY_TYPE)
            ->where('entity_id', $dog->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertSame('Före', $updateLog->old_values['dog_name'] ?? null);
        $this->assertSame('Efter', $updateLog->new_values['dog_name'] ?? null);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->delete(route('visitor-dogs.destroy', $dog))
            ->assertRedirect();

        $deleteLog = ActivityLog::query()
            ->where('entity_type', VisitorDogActivityLogger::ENTITY_TYPE)
            ->where('entity_id', $dog->id)
            ->where('action', 'deleted')
            ->latest('id')
            ->first();

        $this->assertNotNull($deleteLog);
        $this->assertSame('Efter', $deleteLog->old_values['dog_name'] ?? null);
    }

    public function test_admin_show_page_displays_activity_log(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $dog = VisitorDog::factory()->create(['dog_name' => 'Aktiv Rex']);

        ActivityLog::query()->create([
            'user_id' => $admin->id,
            'entity_type' => VisitorDogActivityLogger::ENTITY_TYPE,
            'entity_id' => $dog->id,
            'action' => 'created',
            'new_values' => ['dog_name' => 'Aktiv Rex'],
            'description' => 'Registrerade besökshund Aktiv Rex',
        ]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.visitor-dogs.show', $dog))
            ->assertOk()
            ->assertSee('Aktivitetslogg', false)
            ->assertSee('Registrerade besökshund Aktiv Rex', false);
    }
}
