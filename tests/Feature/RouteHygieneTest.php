<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\ActiveRole;
use App\Support\Roles;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteHygieneTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: list<string>}>
     */
    public static function criticalGetRoutesByRoleProvider(): array
    {
        return [
            Roles::ADMIN => [Roles::ADMIN, [
                'admin.dashboard',
                'admin.visitor-dogs.index',
                'admin.time.index',
                'admin.time.control-panel',
                'admin.time.payroll-locks.index',
            ]],
            Roles::HOST => [Roles::HOST, [
                'host.entry',
                'host.visitor-dogs.index',
                'visitor-dogs.index',
                'visitor-dogs.create',
                'time.index',
            ]],
            Roles::GUIDE => [Roles::GUIDE, [
                'guide.dashboard',
                'visitor-dogs.index',
                'visitor-dogs.create',
                'time.index',
                'guide.reports.create',
            ]],
            Roles::RESTAURANT => [Roles::RESTAURANT, [
                'restaurant.dashboard',
            ]],
        ];
    }

    public function test_restaurant_route_name_helper_points_to_registered_routes(): void
    {
        session(['active_role' => Roles::RESTAURANT]);

        $this->assertTrue(RouteFacade::has(ActiveRole::routeName('restaurant-board.kiosk')));
        $this->assertTrue(RouteFacade::has(ActiveRole::routeName('restaurant-board')));
    }

    public function test_application_route_controller_classes_exist(): void
    {
        $missing = [];

        foreach (RouteFacade::getRoutes() as $route) {
            $class = $this->resolveControllerClass($route);

            if ($class === null) {
                continue;
            }

            if (! class_exists($class)) {
                $missing[] = sprintf(
                    '%s %s → %s (class missing)',
                    implode('|', $route->methods()),
                    $route->uri(),
                    $class,
                );
            }
        }

        $this->assertSame(
            [],
            $missing,
            "Routes with missing controller classes:\n".implode("\n", $missing),
        );
    }

    #[DataProvider('criticalGetRoutesByRoleProvider')]
    public function test_critical_get_routes_return_ok_for_role(string $roleSlug, array $routeNames): void
    {
        $role = Role::query()->where('slug', $roleSlug)->firstOrFail();
        $user = User::factory()->create();
        $user->assignRoles([$role]);

        foreach ($routeNames as $routeName) {
            $this->assertTrue(
                RouteFacade::has($routeName),
                "Route \"{$routeName}\" is not registered.",
            );

            $this->actingAs($user)
                ->withSession(['active_role' => $roleSlug])
                ->get(route($routeName))
                ->assertOk("GET {$routeName} should return 200 for role {$roleSlug}.");
        }
    }

    private function resolveControllerClass(Route $route): ?string
    {
        $class = $route->getControllerClass();

        if ($class === null) {
            return null;
        }

        if (class_exists($class)) {
            return $class;
        }

        $candidates = [
            'App\\Http\\Controllers\\'.$class,
            'App\\Http\\Controllers\\Admin\\'.$class,
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return $class;
    }
}
