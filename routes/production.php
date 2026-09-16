<?php

use App\Http\Controllers\Production\ProductionLogController;
use App\Http\Controllers\Production\ProductionNumberController;
use App\Http\Controllers\Production\ProductionPeopleController;
use App\Http\Controllers\Production\ProductionPresenceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'ensure.active.role', 'active.roles:produktion_admin,produktion_personal'])
    ->prefix('berget')
    ->name('berg.')
    ->group(function () {
        Route::get('/', [ProductionPresenceController::class, 'show'])->name('presence');
        Route::get('/nummer', [ProductionNumberController::class, 'show'])->name('numbers');
        Route::get('/logg', [ProductionLogController::class, 'show'])->name('log');
        Route::get('/poll', [ProductionPresenceController::class, 'poll'])->name('poll');
        Route::post('/stampa', [ProductionPresenceController::class, 'stamp'])->name('stamp');
        Route::post('/personer/{person}/stampa', [ProductionPresenceController::class, 'stampPerson'])->name('people.stamp');
        Route::post('/personer/{person}/utrest', [ProductionPresenceController::class, 'markDeparted'])->name('people.depart');
        Route::post('/personer/{person}/aterstall', [ProductionPresenceController::class, 'restoreDeparted'])->name('people.restore');
    });

Route::middleware(['auth', 'ensure.active.role', 'active.role:produktion_admin'])
    ->prefix('berget')
    ->name('berg.')
    ->group(function () {
        Route::get('/personer', [ProductionPeopleController::class, 'index'])->name('people.index');
        Route::post('/personer', [ProductionPeopleController::class, 'store'])->name('people.store');
        Route::post('/personer/import', [ProductionPeopleController::class, 'import'])->name('people.import');
        Route::get('/personer/{person}/redigera', [ProductionPeopleController::class, 'edit'])->name('people.edit');
        Route::put('/personer/{person}', [ProductionPeopleController::class, 'update'])->name('people.update');
        Route::delete('/personer/{person}', [ProductionPeopleController::class, 'destroy'])->name('people.destroy');
    });
