<?php

use App\Http\Controllers\GroupChatController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Meddelanden och gruppchattar
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'ensure.active.role'])
    ->prefix('messages')
    ->name('messages.')
    ->group(function () {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/create', [MessageController::class, 'create'])->name('create');
        Route::post('/direct', [MessageController::class, 'storeDirect'])->name('direct.store');

        Route::get('/{conversation}', [MessageController::class, 'show'])->name('show');
        Route::post('/{conversation}/send', [MessageController::class, 'send'])->name('send');
        Route::post('/{conversation}/read', [MessageController::class, 'markRead'])->name('read');
    });

Route::middleware(['auth', 'ensure.active.role'])
    ->prefix('group-chats')
    ->name('group-chats.')
    ->group(function () {
        Route::get('/create', [GroupChatController::class, 'create'])->name('create');
        Route::post('/', [GroupChatController::class, 'store'])->name('store');
    });
