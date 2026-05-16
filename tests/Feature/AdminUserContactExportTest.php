<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminUserContactExportTest extends TestCase
{
    public function test_admin_user_contact_export_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.users.export.contacts-csv'));
    }

    public function test_admin_can_export_users_as_contact_csv(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $hostRole = Role::query()->where('slug', Roles::HOST)->firstOrFail();

        $admin = User::factory()->create([
            'name' => 'Admin Export',
            'email' => 'admin-export@example.com',
        ]);
        $admin->assignRoles([$adminRole]);

        $guide = User::factory()->create([
            'name' => 'Anna Guide',
            'email' => 'anna.guide@example.com',
            'phone' => '070-123 45 67',
            'is_active' => true,
        ]);
        $guide->assignRoles([$guideRole]);

        $host = User::factory()->create([
            'name' => 'Bertil Host',
            'email' => 'bertil.host@example.com',
            'is_active' => false,
        ]);
        $host->assignRoles([$hostRole]);

        $response = $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.users.export.contacts-csv'));

        $response->assertOk();
        $response->assertDownload('hemso-personal-kontakter-'.now()->format('Y-m-d').'.csv');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Name,"E-mail Address",Phone,Categories,Notes', $content);
        $this->assertStringContainsString('"Anna Guide",anna.guide@example.com,"070-123 45 67",Guide,"Aktiv; Roller: Guide"', $content);
        $this->assertStringContainsString('"Bertil Host",bertil.host@example.com,,Värd,"Inaktiv; Roller: Värd"', $content);
        $this->assertStringNotContainsString('password', $content);
    }

    public function test_admin_users_index_links_to_contact_export(): void
    {
        $adminRole = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$adminRole]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Exportera kontakter', false)
            ->assertSee('href="/admin/users/export/contacts-csv"', false);
    }
}
