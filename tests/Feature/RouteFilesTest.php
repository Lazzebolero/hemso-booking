<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RouteFilesTest extends TestCase
{
    /**
     * @return list<string>
     */
    public static function modularRouteFilesProvider(): array
    {
        return [
            'pwa' => ['pwa.php'],
            'core' => ['core.php'],
            'public-booking' => ['public-booking.php'],
            'messaging' => ['messaging.php'],
            'staff' => ['staff.php'],
            'visitor-dogs' => ['visitor-dogs.php'],
            'admin' => ['admin.php'],
            'host' => ['host.php'],
            'guide' => ['guide.php'],
            'restaurant' => ['restaurant.php'],
            'time' => ['time.php'],
        ];
    }

    #[DataProvider('modularRouteFilesProvider')]
    public function test_modular_route_file_exists(string $filename): void
    {
        $this->assertFileExists(base_path('routes/'.$filename));
    }

    public function test_critical_named_routes_remain_registered(): void
    {
        foreach ([
            'dashboard',
            'time.index',
            'admin.dashboard',
            'guide.dashboard',
            'host.entry',
            'visitor-dogs.index',
            'restaurant.dashboard',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Route \"{$name}\" is not registered.");
        }
    }
}
