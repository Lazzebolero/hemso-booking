<?php

use App\Http\Controllers\Admin\WeatherForecastController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Väder & prognos (admin + värd)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('weather-forecast', [WeatherForecastController::class, 'index'])->name('weather-forecast.index');
    });

Route::middleware(['auth', 'ensure.active.role', 'active.role:host'])
    ->prefix('host')
    ->name('host.')
    ->group(function () {
        Route::get('weather-forecast', [WeatherForecastController::class, 'index'])->name('weather-forecast.index');
    });
