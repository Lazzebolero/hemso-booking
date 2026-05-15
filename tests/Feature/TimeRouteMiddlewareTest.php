<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TimeRouteMiddlewareTest extends TestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function staffRolesProvider(): array
    {
        return [
            Roles::GUIDE => [Roles::GUIDE],
            Roles::HOST => [Roles::HOST],
            Roles::ADMIN => [Roles::ADMIN],
        ];
    }

    public function test_time_routes_require_ensure_active_role_middleware(): void
    {
        $route = Route::getRoutes()->getByName('time.index');

        $this->assertNotNull($route);
        $middleware = $route->gatherMiddleware();

        $this->assertContains('ensure.active.role', $middleware);
        $this->assertContains('active.roles:guide,host,admin', $middleware);
    }

    public function test_time_index_redirects_to_role_select_without_active_role(): void
    {
        $guideRole = Role::query()->where('slug', Roles::GUIDE)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$guideRole]);

        $this->actingAs($user)
            ->get(route('time.index'))
            ->assertRedirect(route('role.select'));
    }

    #[DataProvider('staffRolesProvider')]
    public function test_time_index_is_accessible_for_staff_roles(string $roleSlug): void
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        $this->actingAs($user)
            ->withSession(['active_role' => $roleSlug])
            ->get(route('time.index'))
            ->assertOk();
    }

    public function test_time_index_is_forbidden_for_restaurant_active_role(): void
    {
        $restaurantRole = Role::query()->where('slug', Roles::RESTAURANT)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$restaurantRole]);

        $this->actingAs($user)
            ->withSession(['active_role' => Roles::RESTAURANT])
            ->get(route('time.index'))
            ->assertForbidden();
    }
}
