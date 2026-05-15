<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\GuideAvailabilityController;
use App\Http\Controllers\Admin\QuickBookingController;
use App\Http\Controllers\Admin\RestaurantBoardController;
use App\Http\Controllers\Admin\SpecialTourController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\TourBatchController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\VisitorDogController as AdminVisitorDogController;
use App\Http\Controllers\Admin\WorkShiftController;
use App\Http\Controllers\Host\HostEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Host
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:host'])
    ->prefix('host')
    ->name('host.')
    ->group(function () {
        Route::get('/valj-vy', HostEntryController::class)->name('entry');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('visitor-dogs', [AdminVisitorDogController::class, 'index'])->name('visitor-dogs.index');
        Route::get('visitor-dogs/gallery', [AdminVisitorDogController::class, 'gallery'])->name('visitor-dogs.gallery');
        Route::get('visitor-dogs/{visitorDog}/edit', [AdminVisitorDogController::class, 'edit'])->name('visitor-dogs.edit');
        Route::put('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'update'])->name('visitor-dogs.update');
        Route::get('visitor-dogs/{visitorDog}/photo', [AdminVisitorDogController::class, 'photo'])->name('visitor-dogs.photo');
        Route::get('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'show'])->name('visitor-dogs.show');
        Route::delete('visitor-dogs/{visitorDog}', [AdminVisitorDogController::class, 'destroy'])->name('visitor-dogs.destroy');

        Route::get('tours/batch-create', [TourBatchController::class, 'create'])->name('tours.batch-create');
        Route::post('tours/batch-create', [TourBatchController::class, 'store'])->name('tours.batch-store');

        Route::post('tours/{tour}/start', [TourController::class, 'start'])->name('tours.start');
        Route::post('tours/{tour}/complete', [TourController::class, 'complete'])->name('tours.complete');
        Route::post('tours/{tour}/cancel', [TourController::class, 'cancel'])->name('tours.cancel');

        Route::resource('tours', TourController::class);
        Route::resource('special-tours', SpecialTourController::class)
            ->parameters(['special-tours' => 'tour']);

        Route::get('guides/availability', [GuideAvailabilityController::class, 'index'])->name('guides.availability');

        Route::get('work-shifts/staffing', [WorkShiftController::class, 'staffing'])
            ->name('work-shifts.staffing');

        Route::get('bookings/export-csv', [BookingController::class, 'exportCsv'])->name('bookings.export-csv');
        Route::get('quick-bookings/create', [QuickBookingController::class, 'create'])->name('bookings.quick-create');
        Route::post('quick-bookings', [QuickBookingController::class, 'store'])->name('bookings.quick-store');
        Route::patch('bookings/{booking}/participants', [BookingController::class, 'quickUpdateParticipants'])->name('bookings.quick-update-participants');
        Route::patch('bookings/{booking}/move', [BookingController::class, 'move'])->name('bookings.move');
        Route::patch('bookings/{booking}/arrival', [BookingController::class, 'markArrival'])->name('bookings.mark-arrival');
        Route::resource('bookings', BookingController::class)->except(['show']);

        Route::get('restaurant-board', [RestaurantBoardController::class, 'index'])->name('restaurant-board');
        Route::get('restaurant-board/kiosk', [RestaurantBoardController::class, 'kiosk'])->name('restaurant-board.kiosk');

        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('statistics/live', [StatisticsController::class, 'live'])->name('statistics.live');

        Route::get('system-logs', [SystemLogController::class, 'index'])
            ->name('system-logs.index');
    });
