<?php

use App\Http\Controllers\MyScheduleController;
use App\Http\Controllers\Staff\StaffDashboardController;
use App\Http\Controllers\Staff\StaffDocumentController as StaffStaffDocumentController;
use App\Http\Controllers\Staff\StaffScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Eget schema
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])->group(function () {
    Route::get('/my-schedule', [MyScheduleController::class, 'index'])
        ->name('my-schedule.index');
});

/*
|--------------------------------------------------------------------------
| Personal / mobil personalyta
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])
    ->prefix('staff')
    ->name('staff.')
    ->group(function () {
        Route::get('/', [StaffDashboardController::class, 'index'])->name('dashboard');
        Route::get('/schedule', [StaffScheduleController::class, 'index'])->name('schedule');

        Route::get('/documents', [StaffStaffDocumentController::class, 'index'])->name('documents.index');
        Route::get('/documents/{staffDocument}', [StaffStaffDocumentController::class, 'show'])->name('documents.show');
        Route::get('/documents/{staffDocument}/preview', [StaffStaffDocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/documents/{staffDocument}/download', [StaffStaffDocumentController::class, 'download'])->name('documents.download');
    });
