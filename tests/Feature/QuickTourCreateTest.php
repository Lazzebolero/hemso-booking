<?php

namespace Tests\Feature;

use App\Models\User;
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
            ->assertSee('Deltagare och språk', false);
    }
}
