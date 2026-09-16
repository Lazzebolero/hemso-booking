<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DailyCountryLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityMemoryController;
use App\Http\Controllers\Admin\FacilityOccupancyController;
use App\Http\Controllers\Admin\GroupBookingController;
use App\Http\Controllers\Admin\GuideAvailabilityController;
use App\Http\Controllers\Admin\GuideLanguageController;
use App\Http\Controllers\Admin\OpeningCheckController;
use App\Http\Controllers\Admin\PostalCodeCollectionController;
use App\Http\Controllers\Admin\PostalCodeReportController;
use App\Http\Controllers\Admin\QuickBookingController;
use App\Http\Controllers\Admin\RestaurantBoardController;
use App\Http\Controllers\Admin\SpecialTourController;
use App\Http\Controllers\Admin\StatisticsController;
use App\Http\Controllers\Admin\StatisticsDayNoteController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\Admin\SystemLogController;
use App\Http\Controllers\Admin\TourBatchController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\UnspecifiedFollowUpController;
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
        Route::patch('/facility-occupancy/extra', [FacilityOccupancyController::class, 'updateExtra'])->name('facility-occupancy.update-extra');

        Route::get('daily-countries', [DailyCountryLogController::class, 'edit'])->name('daily-countries.edit');
        Route::put('daily-countries', [DailyCountryLogController::class, 'update'])->name('daily-countries.update');

        Route::get('statistics-notes', [StatisticsDayNoteController::class, 'edit'])->name('statistics-notes.edit');
        Route::put('statistics-notes', [StatisticsDayNoteController::class, 'update'])->name('statistics-notes.update');

        Route::get('postal-codes', [PostalCodeCollectionController::class, 'edit'])->name('postal-codes.edit');
        Route::put('postal-codes', [PostalCodeCollectionController::class, 'update'])->name('postal-codes.update');
        Route::get('postal-codes/report', [PostalCodeReportController::class, 'index'])->name('postal-codes.report');

        Route::get('visitor-dogs/create', [AdminVisitorDogController::class, 'create'])->name('visitor-dogs.create');
        Route::post('visitor-dogs', [AdminVisitorDogController::class, 'store'])->name('visitor-dogs.store');
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
        Route::post('tours/{tour}/close-for-bookings', [TourController::class, 'closeForBookings'])->name('tours.close-for-bookings');
        Route::post('tours/{tour}/reopen-for-bookings', [TourController::class, 'reopenForBookings'])->name('tours.reopen-for-bookings');
        Route::post('tours/{tour}/cancel', [TourController::class, 'cancel'])->name('tours.cancel');

        Route::resource('tours', TourController::class)->except(['destroy']);
        Route::resource('special-tours', SpecialTourController::class)
            ->parameters(['special-tours' => 'tour']);

        Route::get('guides/availability', [GuideAvailabilityController::class, 'index'])->name('guides.availability');
        Route::get('guide-languages', [GuideLanguageController::class, 'index'])->name('guide-languages.index');

        Route::get('work-shifts/staffing', [WorkShiftController::class, 'staffing'])
            ->name('work-shifts.staffing');

        Route::get('bookings/export-csv', [BookingController::class, 'exportCsv'])->name('bookings.export-csv');
        Route::get('bookings/tours/search', [BookingController::class, 'searchTours'])->middleware('throttle:30,1')->name('bookings.tours.search');
        Route::get('quick-bookings/create', [QuickBookingController::class, 'create'])->name('bookings.quick-create');
        Route::post('quick-bookings', [QuickBookingController::class, 'store'])->name('bookings.quick-store');
        Route::get('group-bookings/create', [GroupBookingController::class, 'create'])->name('group-bookings.create');
        Route::post('group-bookings', [GroupBookingController::class, 'store'])->name('group-bookings.store');
        Route::patch('bookings/{booking}/participants', [BookingController::class, 'quickUpdateParticipants'])->name('bookings.quick-update-participants');
        Route::patch('bookings/{booking}/move', [BookingController::class, 'move'])->name('bookings.move');
        Route::patch('bookings/{booking}/arrival', [BookingController::class, 'markArrival'])->name('bookings.mark-arrival');
        Route::resource('bookings', BookingController::class)->except(['show']);

        Route::get('restaurant-board', [RestaurantBoardController::class, 'index'])->name('restaurant-board');
        Route::get('restaurant-board/kiosk', [RestaurantBoardController::class, 'kiosk'])->name('restaurant-board.kiosk');
        Route::get('restaurant-board/poll', [RestaurantBoardController::class, 'poll'])->name('restaurant-board.poll');
        Route::get('restaurant-board/ferry-timetable', [RestaurantBoardController::class, 'ferryTimetable'])->name('restaurant-board.ferry-timetable');

        Route::get('statistics', [StatisticsController::class, 'index'])->name('statistics.index');
        Route::get('statistics/live', [StatisticsController::class, 'live'])->name('statistics.live');
        Route::get('statistics/booking-inflow', [StatisticsController::class, 'bookingInflow'])->name('statistics.booking-inflow');
        Route::get('statistics/ferry-booking-waves', [StatisticsController::class, 'ferryBookingWaves'])->name('statistics.ferry-booking-waves');
        Route::get('statistics/tour-wait-times', [StatisticsController::class, 'tourWaitTimes'])->name('statistics.tour-wait-times');
        Route::get('statistics/year-countries-map', [StatisticsController::class, 'yearCountriesMap'])->name('statistics.year-countries-map');
        Route::get('statistics/unspecified-follow-up', [UnspecifiedFollowUpController::class, 'index'])->name('statistics.unspecified-follow-up');

        Route::get('system-health', [SystemHealthController::class, 'index'])
            ->name('system-health.index');
        Route::get('system-logs', [SystemLogController::class, 'index'])
            ->name('system-logs.index');

        Route::get('memories', [FacilityMemoryController::class, 'index'])->name('memories.index');
        Route::get('memories/create', [FacilityMemoryController::class, 'create'])->name('memories.create');
        Route::post('memories', [FacilityMemoryController::class, 'store'])->name('memories.store');
        Route::get('memories/{facilityMemory}/audio', [FacilityMemoryController::class, 'audio'])->name('memories.audio');
        Route::get('memories/{facilityMemory}', [FacilityMemoryController::class, 'show'])->name('memories.show');

        Route::get('opening-checks', [OpeningCheckController::class, 'index'])->name('opening-checks.index');
        Route::get('opening-checks/{openingCheck}', [OpeningCheckController::class, 'show'])->name('opening-checks.show');
        Route::patch('opening-checks/{openingCheck}/deviations/{openingDeviation}/resolve', [OpeningCheckController::class, 'resolveDeviation'])->name('opening-checks.deviations.resolve');
    });
