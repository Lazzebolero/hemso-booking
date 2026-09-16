<?php

use App\Http\Controllers\Admin\FerryTimetableController;
use App\Http\Controllers\FerryScheduleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Färjetrafik (gemensam + admin tidtabell)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])->group(function () {
    Route::get('/ferry-schedule', [FerryScheduleController::class, 'index'])
        ->name('ferry-schedule.index');
});

Route::middleware(['auth', 'ensure.active.role', 'active.role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('ferry-timetable', [FerryTimetableController::class, 'index'])->name('ferry-timetable.index');
        Route::post('ferry-timetable/settings', [FerryTimetableController::class, 'updateSettings'])->name('ferry-timetable.settings');
    });

Route::middleware(['auth', 'ensure.active.role', 'active.role:host'])
    ->prefix('host')
    ->name('host.')
    ->group(function () {
        Route::get('ferry-timetable', [FerryTimetableController::class, 'index'])->name('ferry-timetable.index');
    });
