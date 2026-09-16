<?php

use App\Http\Controllers\Guide\DashboardController as GuideDashboardController;
use App\Http\Controllers\Guide\FacilityMemoryController as GuideFacilityMemoryController;
use App\Http\Controllers\Guide\FacilityReportController as GuideFacilityReportController;
use App\Http\Controllers\Guide\OpeningCheckController as GuideOpeningCheckController;
use App\Http\Controllers\Guide\TourPhotoController as GuideTourPhotoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guide
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'guide.shell.activate', 'active.role:guide'])
    ->prefix('guide')
    ->name('guide.')
    ->group(function () {
        Route::get('/dashboard', [GuideDashboardController::class, 'index'])->name('dashboard');

        Route::post('/tours/{tour}/start', [GuideDashboardController::class, 'startTour'])->name('tours.start');
        Route::patch('/tours/{tour}/headcount', [GuideDashboardController::class, 'adjustTourHeadcount'])->name('tours.adjust-headcount');
        Route::post('/tours/{tour}/complete', [GuideDashboardController::class, 'completeTour'])->name('tours.complete');
        Route::get('/tours/{tour}/photos/create', [GuideTourPhotoController::class, 'create'])->name('tours.photos.create');
        Route::post('/tours/{tour}/photos', [GuideTourPhotoController::class, 'store'])->name('tours.photos.store');
        Route::delete('/tours/{tour}/photos/{tourPhoto}', [GuideTourPhotoController::class, 'destroy'])->name('tours.photos.destroy');
        Route::get('/tours/{tour}', [GuideDashboardController::class, 'showTour'])->name('tours.show');

        Route::patch('/bookings/{booking}/participants', [GuideDashboardController::class, 'updateBookingParticipants'])->name('bookings.update-participants');

        Route::get('/reports/create', [GuideFacilityReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [GuideFacilityReportController::class, 'store'])->name('reports.store');

        Route::get('/opening-check', [GuideOpeningCheckController::class, 'edit'])->name('opening-checks.edit');
        Route::put('/opening-check', [GuideOpeningCheckController::class, 'update'])->name('opening-checks.update');
        Route::post('/opening-check/deviations', [GuideOpeningCheckController::class, 'storeDeviation'])->name('opening-checks.deviations.store');

        Route::get('/memories', [GuideFacilityMemoryController::class, 'index'])->name('memories.index');
        Route::get('/memories/create', [GuideFacilityMemoryController::class, 'create'])->name('memories.create');
        Route::post('/memories', [GuideFacilityMemoryController::class, 'store'])->name('memories.store');
        Route::get('/memories/{facilityMemory}/audio', [GuideFacilityMemoryController::class, 'audio'])->name('memories.audio');
        Route::get('/memories/{facilityMemory}', [GuideFacilityMemoryController::class, 'show'])->name('memories.show');
    });
