<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\QuickTourController;

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityReportController;
use App\Http\Controllers\Admin\GuideShiftController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\ReportOptionController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\TourTypeController;
use App\Http\Controllers\Admin\UserController;

use App\Http\Controllers\Guide\DashboardController as GuideDashboardController;
use App\Http\Controllers\Guide\FacilityReportController as GuideFacilityReportController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = auth()->user();

        return match ($user->role) {
            'admin', 'host' => redirect()->route('admin.dashboard'),
            'guide' => redirect()->route('guide.dashboard'),
            default => abort(403),
        };
    })->name('dashboard');

    Route::get('/quick-tours/create', [QuickTourController::class, 'create'])->name('quick-tours.create');
    Route::post('/quick-tours', [QuickTourController::class, 'store'])->name('quick-tours.store');
});

Route::middleware(['auth', 'role:admin,host'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::resource('tours', TourController::class);
        Route::post('tours/{tour}/start', [TourController::class, 'start'])->name('tours.start');
        Route::post('tours/{tour}/complete', [TourController::class, 'complete'])->name('tours.complete');

        Route::resource('bookings', BookingController::class)->except(['show']);
        Route::patch('bookings/{booking}/participants', [BookingController::class, 'quickUpdateParticipants'])
            ->name('bookings.quick-update-participants');
        Route::patch('bookings/{booking}/move', [BookingController::class, 'move'])
            ->name('bookings.move');
        Route::patch('bookings/{booking}/arrival', [BookingController::class, 'markArrival'])
            ->name('bookings.mark-arrival');
        Route::get('bookings/export-csv', [BookingController::class, 'exportCsv'])
            ->name('bookings.export-csv');

        Route::resource('shifts', GuideShiftController::class);

        Route::resource('reports', FacilityReportController::class)->except(['create', 'store']);
        Route::get('reports/create', [FacilityReportController::class, 'create'])->name('reports.create');
        Route::post('reports', [FacilityReportController::class, 'store'])->name('reports.store');

        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('activity-logs/{entityType}/{entityId}', [ActivityLogController::class, 'showEntityHistory'])
            ->name('activity-logs.entity-history');

        Route::get('statistics', [\App\Http\Controllers\Admin\StatisticsController::class, 'index'])
            ->name('statistics.index');
        Route::get('statistics/live', [\App\Http\Controllers\Admin\StatisticsController::class, 'live'])
            ->name('statistics.live');
        Route::get('statistics/export-csv', [\App\Http\Controllers\Admin\StatisticsController::class, 'exportCsv'])
            ->name('statistics.export-csv');

        Route::get('settings', [\App\Http\Controllers\Admin\SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [\App\Http\Controllers\Admin\SettingController::class, 'update'])->name('settings.update');

        Route::get('report-options', [ReportOptionController::class, 'index'])->name('report-options.index');
        Route::post('report-options', [ReportOptionController::class, 'store'])->name('report-options.store');
        Route::put('report-options/{reportOption}', [ReportOptionController::class, 'update'])->name('report-options.update');
        Route::delete('report-options/{reportOption}', [ReportOptionController::class, 'destroy'])->name('report-options.destroy');

        Route::get('tour-types', [TourTypeController::class, 'index'])->name('tour-types.index');
        Route::post('tour-types', [TourTypeController::class, 'store'])->name('tour-types.store');
        Route::put('tour-types/{tourType}', [TourTypeController::class, 'update'])->name('tour-types.update');
        Route::delete('tour-types/{tourType}', [TourTypeController::class, 'destroy'])->name('tour-types.destroy');

        Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
        Route::put('languages/{language}', [LanguageController::class, 'update'])->name('languages.update');
        Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->name('languages.destroy');
    });

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class);

        Route::resource('notification-templates', NotificationTemplateController::class)
            ->except(['show', 'create', 'edit']);

        Route::get('notification-logs', [NotificationLogController::class, 'index'])
            ->name('notification-logs.index');

        Route::post('notification-logs/{notificationLog}/resend', [NotificationLogController::class, 'resend'])
            ->name('notification-logs.resend');
    });

Route::middleware(['auth', 'role:guide,admin'])
    ->prefix('guide')
    ->name('guide.')
    ->group(function () {
        Route::get('/dashboard', [GuideDashboardController::class, 'index'])->name('dashboard');
        Route::post('/tours/{tour}/start', [GuideDashboardController::class, 'startTour'])->name('tours.start');
        Route::post('/tours/{tour}/complete', [GuideDashboardController::class, 'completeTour'])->name('tours.complete');
        Route::get('/tours/{tour}', [GuideDashboardController::class, 'showTour'])->name('tours.show');
        Route::patch('/bookings/{booking}/participants', [GuideDashboardController::class, 'updateBookingParticipants'])
            ->name('bookings.update-participants');

        Route::get('/reports/create', [GuideFacilityReportController::class, 'create'])->name('reports.create');
        Route::post('/reports', [GuideFacilityReportController::class, 'store'])->name('reports.store');
    });

require __DIR__ . '/auth.php';