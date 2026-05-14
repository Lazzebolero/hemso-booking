<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ReportOptionRoutesTest extends TestCase
{
    public function test_admin_report_option_routes_are_registered(): void
    {
        $this->assertTrue(Route::has('admin.report-options.index'));
        $this->assertTrue(Route::has('admin.report-options.store'));
        $this->assertTrue(Route::has('admin.report-options.update'));
        $this->assertTrue(Route::has('admin.report-options.destroy'));
    }

    public function test_admin_can_open_report_options_settings_page(): void
    {
        $role = Role::query()->where('slug', Roles::ADMIN)->firstOrFail();
        $admin = User::factory()->create();
        $admin->assignRoles([$role]);

        $this->actingAs($admin)
            ->withSession(['active_role' => Roles::ADMIN])
            ->get(route('admin.report-options.index'))
            ->assertOk();
    }
}
