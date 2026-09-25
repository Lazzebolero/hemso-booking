<?php

namespace Tests\Feature;

use App\Models\Production;
use App\Models\ProductionDepartureLog;
use App\Models\ProductionPerson;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class ProductionDepartureLogTest extends TestCase
{
    public function test_leave_button_asks_if_the_participant_left_the_competition(): void
    {
        $productionAdmin = $this->userWithRole(Roles::PRODUKTION_ADMIN, 'Produktionsadmin Anna');
        $production = Production::factory()->create();

        ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'user_id' => $productionAdmin->id,
            'name' => 'Produktionsadmin Anna',
            'kind' => ProductionPerson::KIND_ADMIN,
        ]);

        ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'name' => 'Erik Berg',
            'is_inside' => false,
        ]);

        $this->actingAs($productionAdmin)
            ->withSession(['active_role' => Roles::PRODUKTION_ADMIN])
            ->get(route('berg.presence'))
            ->assertOk()
            ->assertSee('Märk Erik Berg som åkt ur tävlingen?', false)
            ->assertDontSee('som utrest', false);
    }

    public function test_admin_production_page_shows_who_marked_a_participant_as_departed(): void
    {
        $hemsoAdmin = $this->userWithRole(Roles::ADMIN, 'Hemsö Admin');
        $productionAdmin = $this->userWithRole(Roles::PRODUKTION_ADMIN, 'Produktionsadmin Anna');
        $production = Production::factory()->create();

        ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'user_id' => $productionAdmin->id,
            'name' => 'Produktionsadmin Anna',
            'kind' => ProductionPerson::KIND_ADMIN,
        ]);

        $participant = ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'name' => 'Deltagare Bo',
            'is_inside' => false,
        ]);

        $this->actingAs($productionAdmin)
            ->withSession(['active_role' => Roles::PRODUKTION_ADMIN])
            ->post(route('berg.people.depart', $participant))
            ->assertRedirect();

        $this->assertDatabaseHas('production_departure_logs', [
            'production_person_id' => $participant->id,
            'action' => ProductionDepartureLog::ACTION_DEPARTED,
            'recorded_by' => $productionAdmin->id,
        ]);

        $this->assertDatabaseMissing('production_presence_logs', [
            'production_person_id' => $participant->id,
        ]);

        $this->actingAs($hemsoAdmin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.productions.show', $production))
            ->assertOk()
            ->assertSee('Logg för åkt ut')
            ->assertSee('Deltagare Bo')
            ->assertSee('Produktionsadmin Anna')
            ->assertSee('Åkt ut');

        $this->actingAs($productionAdmin)
            ->withSession(['active_role' => Roles::PRODUKTION_ADMIN])
            ->get(route('admin.productions.show', $production))
            ->assertForbidden();

        $this->actingAs($productionAdmin)
            ->withSession(['active_role' => Roles::PRODUKTION_ADMIN])
            ->get(route('berg.log'))
            ->assertOk()
            ->assertDontSee('Åkt ut');
    }

    public function test_departing_someone_inside_keeps_the_out_stamp_separate_from_akt_ut(): void
    {
        $hemsoAdmin = $this->userWithRole(Roles::ADMIN, 'Hemsö Admin');
        $productionAdmin = $this->userWithRole(Roles::PRODUKTION_ADMIN, 'Produktionsadmin Anna');
        $production = Production::factory()->create();

        ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'user_id' => $productionAdmin->id,
            'name' => 'Produktionsadmin Anna',
            'kind' => ProductionPerson::KIND_ADMIN,
        ]);

        $participant = ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'name' => 'Deltagare Bo',
            'is_inside' => true,
        ]);

        $this->actingAs($productionAdmin)
            ->withSession(['active_role' => Roles::PRODUKTION_ADMIN])
            ->post(route('berg.people.depart', $participant))
            ->assertRedirect();

        $this->assertDatabaseHas('production_presence_logs', [
            'production_person_id' => $participant->id,
            'direction' => ProductionPerson::DIRECTION_OUT,
            'recorded_by' => $productionAdmin->id,
        ]);

        $this->actingAs($hemsoAdmin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.productions.show', $production))
            ->assertOk()
            ->assertSee('Ut')
            ->assertSee('Åkt ut');
    }

    public function test_restoring_a_departed_participant_records_who_did_it(): void
    {
        $hemsoAdmin = $this->userWithRole(Roles::ADMIN, 'Hemsö Admin');
        $productionAdmin = $this->userWithRole(Roles::PRODUKTION_ADMIN, 'Produktionsadmin Anna');
        $production = Production::factory()->create();

        ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'user_id' => $productionAdmin->id,
            'name' => 'Produktionsadmin Anna',
            'kind' => ProductionPerson::KIND_ADMIN,
        ]);

        $participant = ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'name' => 'Deltagare Bo',
            'departed_at' => now()->subHour(),
            'departed_on' => now()->toDateString(),
        ]);

        $this->actingAs($productionAdmin)
            ->withSession(['active_role' => Roles::PRODUKTION_ADMIN])
            ->post(route('berg.people.restore', $participant))
            ->assertRedirect();

        $participant->refresh();
        $this->assertFalse($participant->hasDeparted());

        $this->assertDatabaseHas('production_departure_logs', [
            'production_person_id' => $participant->id,
            'action' => ProductionDepartureLog::ACTION_RESTORED,
            'recorded_by' => $productionAdmin->id,
        ]);

        $this->actingAs($hemsoAdmin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.productions.show', $production))
            ->assertOk()
            ->assertSee('Återställd')
            ->assertSee('Produktionsadmin Anna');
    }

    public function test_older_departures_without_a_recorder_show_the_time_only(): void
    {
        $hemsoAdmin = $this->userWithRole(Roles::ADMIN, 'Hemsö Admin');
        $production = Production::factory()->create();
        $departedAt = now()->subDays(2)->startOfHour();

        ProductionPerson::factory()->create([
            'production_id' => $production->id,
            'name' => 'Deltagare Bo',
            'departed_at' => $departedAt,
            'departed_on' => $departedAt->toDateString(),
        ]);

        $this->actingAs($hemsoAdmin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.productions.show', $production))
            ->assertOk()
            ->assertSee($departedAt->format('Y-m-d H:i'))
            ->assertSee('Loggades inte')
            ->assertSee('Deltagare Bo');
    }

    public function test_admin_dashboard_links_to_departed_participants(): void
    {
        $hemsoAdmin = $this->userWithRole(Roles::ADMIN, 'Hemsö Admin');
        $production = Production::factory()->create(['name' => 'Inspelning vecka 39']);

        ProductionPerson::factory()->count(2)->create([
            'production_id' => $production->id,
            'departed_at' => now(),
            'departed_on' => now()->toDateString(),
        ]);

        $this->actingAs($hemsoAdmin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('2 deltagare märkta som åkt ut')
            ->assertSee(route('admin.productions.show', $production), false);
    }

    private function userWithRole(string $roleSlug, string $name): User
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create(['name' => $name]);
        $user->assignRoles([$role]);

        return $user;
    }
}
