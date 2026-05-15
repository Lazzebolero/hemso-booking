<?php

use App\Http\Controllers\VisitorDogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Besökshundar (guide och värd)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role', 'active.roles:guide,host'])
    ->prefix('besokshundar')
    ->name('visitor-dogs.')
    ->group(function () {
        Route::get('mina', [VisitorDogController::class, 'index'])->name('index');
        Route::get('/', [VisitorDogController::class, 'create'])->name('create');
        Route::post('/', [VisitorDogController::class, 'store'])->name('store');
        Route::get('{visitorDog}/edit', [VisitorDogController::class, 'edit'])->name('edit');
        Route::put('{visitorDog}', [VisitorDogController::class, 'update'])->name('update');
        Route::get('{visitorDog}/photo', [VisitorDogController::class, 'photo'])->name('photo');
        Route::get('{visitorDog}', [VisitorDogController::class, 'show'])->name('show');
        Route::delete('{visitorDog}', [VisitorDogController::class, 'destroy'])->name('destroy');
    });
