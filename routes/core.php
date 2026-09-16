<?php

use App\Http\Controllers\Admin\SystemMessageController;
use App\Http\Controllers\Admin\SystemMessageStatusController;
use App\Http\Controllers\AppPulseController;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\QuickTourController;
use App\Http\Controllers\RoleSelectionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Start
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/dashboard');
});
/*
|--------------------------------------------------------------------------
| Gemensamt efter inloggning
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');

    Route::get('/select-role', [RoleSelectionController::class, 'create'])
        ->name('role.select');

    Route::post('/select-role', [RoleSelectionController::class, 'store'])
        ->name('role.store');

    Route::post('/switch-role', [RoleSelectionController::class, 'store'])
        ->name('role.switch');

    Route::post('/system-messages/{systemMessage}/read', [SystemMessageStatusController::class, 'read'])
        ->name('system-messages.read');

    Route::post('/system-messages/{systemMessage}/dismiss', [SystemMessageStatusController::class, 'dismiss'])
        ->name('system-messages.dismiss');

    Route::post('/system-messages/{systemMessage}/restore', [SystemMessageStatusController::class, 'restore'])
        ->name('system-messages.restore');

    Route::post('/system-messages/{systemMessage}/acknowledge', [SystemMessageStatusController::class, 'acknowledge'])
        ->name('system-messages.acknowledge');

    Route::get('/system-messages/live/panel', [SystemMessageController::class, 'livePanel'])
        ->name('system-messages.live-panel');

    Route::get('/system-messages/force-popup/panel', [SystemMessageController::class, 'forcePopupPanel'])
        ->name('system-messages.force-popup-panel');
    Route::middleware(['auth'])->get('/app/pulse', AppPulseController::class)
        ->name('app.pulse');
});

Route::middleware(['auth', 'ensure.active.role', 'active.roles:admin,host,guide'])
    ->group(function () {
        Route::get('/quick-tours/create', [QuickTourController::class, 'create'])
            ->name('quick-tours.create');

        Route::post('/quick-tours', [QuickTourController::class, 'store'])
            ->name('quick-tours.store');
    });
