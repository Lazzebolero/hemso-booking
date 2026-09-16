<?php

use App\Http\Controllers\Admin\FerryAdjustmentController;
use App\Http\Controllers\Admin\WeatherForecastController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Färjekorrigering + väderprognos (admin + värd)
|--------------------------------------------------------------------------
| Egen fil för enklare WinSCP-deploy. Laddas alltid via routes/web.php.
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('ferry-adjustments', [FerryAdjustmentController::class, 'index'])->name('ferry-adjustments.index');
        Route::post('ferry-adjustments/{tour}/shift', [FerryAdjustmentController::class, 'shift'])->name('ferry-adjustments.shift');
        Route::post('ferry-adjustments/{tour}/reset', [FerryAdjustmentController::class, 'reset'])->name('ferry-adjustments.reset');
        Route::get('weather-forecast', [WeatherForecastController::class, 'index'])->name('weather-forecast.index');
    });

Route::middleware(['auth', 'ensure.active.role', 'active.role:host'])
    ->prefix('host')
    ->name('host.')
    ->group(function () {
        Route::get('ferry-adjustments', [FerryAdjustmentController::class, 'index'])->name('ferry-adjustments.index');
        Route::post('ferry-adjustments/{tour}/shift', [FerryAdjustmentController::class, 'shift'])->name('ferry-adjustments.shift');
        Route::post('ferry-adjustments/{tour}/reset', [FerryAdjustmentController::class, 'reset'])->name('ferry-adjustments.reset');
        Route::get('weather-forecast', [WeatherForecastController::class, 'index'])->name('weather-forecast.index');
    });
