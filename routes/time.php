<?php

use App\Http\Controllers\TimeClockController;
use App\Http\Controllers\TimeClockScanController;
use App\Http\Controllers\TimeEntryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tid, stämpeklocka
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.roles:guide,host,restaurant,admin'])
    ->group(function () {
        Route::get('/time/scan/{token}', [TimeClockScanController::class, 'show'])->name('time.scan');
        Route::get('/time/station/{station}', [TimeClockScanController::class, 'showStation'])
            ->where('station', 'entrance|restaurant')
            ->name('time.station');
        Route::get('/time', [TimeEntryController::class, 'index'])->name('time.index');
        Route::post('/time/clock-in', [TimeClockController::class, 'clockIn'])->name('time.clock-in');
        Route::post('/time/clock-out', [TimeClockController::class, 'clockOut'])->name('time.clock-out');
        Route::get('/time/{timeEntry}/edit', [TimeEntryController::class, 'edit'])->name('time.edit');
        Route::patch('/time/{timeEntry}', [TimeEntryController::class, 'update'])->name('time.update');
        Route::patch('/time/{timeEntry}/submit', [TimeEntryController::class, 'submit'])->name('time.submit');
    });
