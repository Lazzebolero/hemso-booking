<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\User;
use App\Support\GuideShell;
use App\Support\Roles;
use Tests\TestCase;

class QuickTourCreateTest extends TestCase
{
    public function test_guide_sees_quick_tour_form_when_active_role_is_guide(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::GUIDE])
            ->get(route('quick-tours.create'))
            ->assertOk()
            ->assertSee('Starta snabbtur', false)
            ->assertSee('guide-quicktour-form', false);
    }

    public function test_admin_with_guide_role_keeps_admin_role_when_opening_quick_tour(): void
    {
        $user = $this->userWithRoles([Roles::ADMIN, Roles::GUIDE]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('quick-tours.create'))
            ->assertOk()
            ->assertSee('Ej tilldelad', false)
            ->assertSee('Ospecificerade', false);

        $this->assertSame(Roles::ADMIN, session('active_role'));
        $this->assertFalse(GuideShell::isActive());
    }

    public function test_admin_with_guide_role_returns_to_admin_dashboard_after_quick_tour(): void
    {
        $user = $this->userWithRoles([Roles::ADMIN, Roles::GUIDE]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('quick-tours.store'), [
                'participant_count' => 4,
                'language_ids' => [],
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertSame(Roles::ADMIN, session('active_role'));
        $this->assertFalse(GuideShell::isActive());
    }

    public function test_host_with_guide_role_keeps_host_role_when_opening_quick_tour(): void
    {
        $user = $this->userWithRoles([Roles::HOST, Roles::GUIDE]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::HOST])
            ->get(route('quick-tours.create'))
            ->assertOk()
            ->assertSee('Ej tilldelad', false)
            ->assertSee('Ospecificerade', false);

        $this->assertSame(Roles::HOST, session('active_role'));
        $this->assertFalse(GuideShell::isActive());
    }

    public function test_admin_quick_tour_can_use_unspecified_count_only(): void
    {
        $user = $this->userWithRoles([Roles::ADMIN, Roles::GUIDE]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->post(route('quick-tours.store'), [
                'men_count' => 0,
                'women_count' => 0,
                'youth_count' => 0,
                'child_count' => 0,
                'unspecified_count' => 12,
                'language_ids' => [],
            ])
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $booking = Booking::query()->latest('id')->first();

        $this->assertNotNull($booking);
        $this->assertSame(12, $booking->total_count);
        $this->assertSame(12, $booking->unspecified_count);
        $this->assertSame(0, $booking->men_count);
    }

    /**
     * @param  list<string>  $roleSlugs
     */
    private function userWithRoles(array $roleSlugs): User
    {
        $roles = Role::query()->whereIn('slug', $roleSlugs)->get();
        $user = User::factory()->create();
        $user->assignRoles($roles->all());

        return $user;
    }
}
