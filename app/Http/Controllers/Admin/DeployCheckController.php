<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Roles;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DeployCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        abort_unless(
            auth()->check() && session('active_role') === Roles::ADMIN,
            404
        );

        $adminRoutesPath = base_path('routes/admin.php');
        $webRoutesPath = base_path('routes/web.php');
        $layoutPath = resource_path('views/layouts/app.blade.php');
        $dailyRoutesPath = base_path('routes/daily-guide-orders.php');

        return response()->json([
            'checked_at' => now()->toIso8601String(),
            'routes_cached' => app()->routesAreCached(),
            'route_cache_file' => File::exists(base_path('bootstrap/cache/routes-v7.php')),
            'daily_guide_route_registered' => Route::has('admin.daily-guide-orders.index'),
            'controller_class_exists' => class_exists(DailyGuideOrderController::class),
            'view_exists' => view()->exists('admin.daily-guide-orders.index'),
            'table_exists' => Schema::hasTable('daily_guide_orders'),
            'files' => [
                'routes/daily-guide-orders.php' => [
                    'exists' => File::exists($dailyRoutesPath),
                    'contains_marker' => File::exists($dailyRoutesPath)
                        && str_contains(File::get($dailyRoutesPath), 'daily-guide-orders'),
                ],
                'routes/web.php' => [
                    'exists' => File::exists($webRoutesPath),
                    'requires_daily_guide_routes' => File::exists($webRoutesPath)
                        && str_contains(File::get($webRoutesPath), 'daily-guide-orders.php'),
                ],
                'routes/admin.php' => [
                    'exists' => File::exists($adminRoutesPath),
                    'contains_marker' => File::exists($adminRoutesPath)
                        && str_contains(File::get($adminRoutesPath), 'daily-guide-orders'),
                ],
                'layouts/app.blade.php' => [
                    'exists' => File::exists($layoutPath),
                    'contains_menu_label' => File::exists($layoutPath)
                        && str_contains(File::get($layoutPath), 'Dagens guider'),
                    'layout_marker' => File::exists($layoutPath)
                        && preg_match('/hemso-layout:\s*(\S+)/', File::get($layoutPath), $matches)
                        ? $matches[1]
                        : null,
                ],
            ],
            'next_steps' => $this->nextSteps(),
        ]);
    }

    /**
     * @return list<string>
     */
    private function nextSteps(): array
    {
        $steps = [];

        if (app()->routesAreCached() && ! Route::has('admin.daily-guide-orders.index')) {
            $steps[] = 'Ta bort bootstrap/cache/routes-v7.php på servern eller kör php artisan route:cache.';
        }

        if (! Route::has('admin.daily-guide-orders.index')) {
            $steps[] = 'Ladda upp routes/web.php och routes/daily-guide-orders.php.';
        }

        if (! class_exists(DailyGuideOrderController::class)) {
            $steps[] = 'Ladda upp app/Http/Controllers/Admin/DailyGuideOrderController.php.';
        }

        if (! view()->exists('admin.daily-guide-orders.index')) {
            $steps[] = 'Ladda upp resources/views/admin/daily-guide-orders/index.blade.php.';
        }

        if (! Schema::hasTable('daily_guide_orders')) {
            $steps[] = 'Kör php artisan migrate --force.';
        }

        if ($steps === []) {
            $steps[] = 'Allt ser korrekt ut från applikationens sida. Testa /admin/daily-guide-orders igen.';
        }

        return $steps;
    }
}
