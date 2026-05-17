<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Tests\TestCase;

class PhotoDiagnosticsTest extends TestCase
{
    public function test_admin_can_open_photo_diagnostics(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.photo-diagnostics'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('Photo diagnostics', false)
            ->assertSee('resources/views/visitor-dogs/_form.blade.php', false)
            ->assertSee('resources/views/guide/report-form.blade.php', false)
            ->assertSee('capture="environment"', false);
    }
}
