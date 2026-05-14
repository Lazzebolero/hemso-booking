<?php

use App\Http\Middleware\EnsureActiveRole;
use App\Http\Middleware\EnsureRestaurantStatisticsAccess;
use App\Http\Middleware\RequireActiveRole;
use App\Http\Middleware\RequireAnyActiveRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            // NYA rollsystemet
            'ensure.active.role' => EnsureActiveRole::class,
            'active.role' => RequireActiveRole::class,
            'active.roles' => RequireAnyActiveRole::class,

            // (valfritt) behåll endast om du inte hunnit ta bort överallt
            // 'role' => \App\Http\Middleware\RoleMiddleware::class,
            'restaurant.statistics.access' => EnsureRestaurantStatisticsAccess::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
