<?php

use App\Http\Controllers\Admin\RestaurantBoardController;
use App\Http\Controllers\RestaurantStatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Restaurang
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:restaurant'])
    ->prefix('restaurant')
    ->name('restaurant.')
    ->group(function () {
        Route::get('/dashboard', [RestaurantBoardController::class, 'index'])->name('dashboard');
        Route::get('/board', [RestaurantBoardController::class, 'index'])->name('board');
        Route::get('/kiosk', [RestaurantBoardController::class, 'kiosk'])->name('kiosk');
    });

/*
|--------------------------------------------------------------------------
| Restaurang statistik
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->prefix('statistik/restaurang')
    ->name('restaurant-statistik.')
    ->group(function () {
        Route::get('/', [RestaurantBoardController::class, 'statistik'])
            ->name('dashboard');
    });
/*
|--------------------------------------------------------------------------
| Restaurang statistik - fristående login
|--------------------------------------------------------------------------
*/

Route::get('/restaurant-statistics/login', [RestaurantStatisticsController::class, 'loginForm'])
    ->name('restaurant-statistics.login');

Route::post('/restaurant-statistics/login', [RestaurantStatisticsController::class, 'login'])
    ->name('restaurant-statistics.login.store');

Route::post('/restaurant-statistics/logout', [RestaurantStatisticsController::class, 'logout'])
    ->name('restaurant-statistics.logout');

Route::middleware(['restaurant.statistics.access'])
    ->prefix('restaurant-statistics')
    ->name('restaurant-statistics.')
    ->group(function () {
        Route::get('/', [RestaurantStatisticsController::class, 'dashboard'])
            ->name('dashboard');
    });
