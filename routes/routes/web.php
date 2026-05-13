<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FacilityReportController;
use App\Http\Controllers\Admin\GuideShiftController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Guide\DashboardController as GuideDashboardController;
use App\Http\Controllers\Guide\FacilityReportController as GuideFacilityReportController;
use Illuminate\Support\Facades\Route;

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
});

Route::middleware(['auth', 'role:admin,host'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('tours', TourController::class);
    Route::post('tours/{tour}/start', [TourController::class, 'start'])->name('tours.start');
    Route::post('tours/{tour}/complete', [TourController::class, 'complete'])->name('tours.complete');

    Route::resource('bookings', BookingController::class)->except(['show']);
    Route::resource('shifts', GuideShiftController::class);
    Route::resource('reports', FacilityReportController::class)->except(['create', 'store']);

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/{entityType}/{entityId}', [ActivityLogController::class, 'showEntityHistory'])->name('activity-logs.entity-history');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class);
});

Route::middleware(['auth', 'role:guide,admin'])->prefix('guide')->name('guide.')->group(function () {
    Route::get('/dashboard', [GuideDashboardController::class, 'index'])->name('dashboard');
    Route::post('/tours/{tour}/start', [GuideDashboardController::class, 'startTour'])->name('tours.start');
    Route::post('/tours/{tour}/complete', [GuideDashboardController::class, 'completeTour'])->name('tours.complete');
    Route::get('/reports/create', [GuideFacilityReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [GuideFacilityReportController::class, 'store'])->name('reports.store');
});

require __DIR__.'/auth.php';
