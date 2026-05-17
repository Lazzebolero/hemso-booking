<?php

use App\Http\Controllers\Guide\CameraTestController as GuideCameraTestController;
use App\Http\Controllers\Guide\DashboardController as GuideDashboardController;
use App\Http\Controllers\Guide\FacilityReportController as GuideFacilityReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guide
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:guide'])
    ->prefix('guide')
    ->name('guide.')
    ->group(function () {
        Route::get('/dashboard', [GuideDashboardController::class, 'index'])->name('dashboard');

        Route::get('/camera-test', [GuideCameraTestController::class, 'create'])->name('camera-test.create');
        Route::post('/camera-test', [GuideCameraTestController::class, 'store'])->name('camera-test.store');

        Route::post('/tours/{tour}/start', [GuideDashboardController::class, 'startTour'])->name('tours.start');
        Route::post('/tours/{tour}/complete', [GuideDashboardController::class, 'completeTour'])->name('tours.complete');
        Route::get('/tours/{tour}', [GuideDashboardController::class, 'showTour'])->name('tours.show');

        Route::patch('/bookings/{booking}/participants', [GuideDashboardController::class, 'updateBookingParticipants'])->name('bookings.update-participants');

        Route::get('/reports/create', [GuideFacilityReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [GuideFacilityReportController::class, 'store'])->name('reports.store');
    });
