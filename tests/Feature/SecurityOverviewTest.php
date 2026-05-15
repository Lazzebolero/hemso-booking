<?php

namespace Tests\Feature;

use App\Models\LoginEvent;
use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityOverviewTest extends TestCase
{
    public function test_login_events_table_exists_after_migrations(): void
    {
        $this->assertTrue(Schema::hasTable('login_events'));
    }

    public function test_admin_can_view_security_overview(): void
    {
        LoginEvent::factory()->failed()->create([
            'occurred_at' => now()->subHour(),
        ]);

        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$adminRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.security-overview.index'))
            ->assertOk()
            ->assertSee('Säkerhetsöversikt', false);
    }
}
