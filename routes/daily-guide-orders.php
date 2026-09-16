<?php

use App\Http\Controllers\Admin\DailyGuideOrderController;
use App\Http\Controllers\Admin\DeployCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dagens guider (admin + värd)
|--------------------------------------------------------------------------
| Egen fil för enklare WinSCP-deploy. Laddas alltid via routes/web.php.
*/

Route::middleware(['auth', 'ensure.active.role', 'active.role:admin'])
    ->get('admin/_deploy-check', DeployCheckController::class)
    ->name('admin.deploy-check');

Route::middleware(['auth', 'ensure.active.role', 'active.role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('daily-guide-orders', [DailyGuideOrderController::class, 'index'])->name('daily-guide-orders.index');
        Route::post('daily-guide-orders/sync-schedule', [DailyGuideOrderController::class, 'syncFromSchedule'])->name('daily-guide-orders.sync-schedule');
        Route::post('daily-guide-orders/reorder', [DailyGuideOrderController::class, 'reorder'])->name('daily-guide-orders.reorder');
        Route::post('daily-guide-orders', [DailyGuideOrderController::class, 'store'])->name('daily-guide-orders.store');
        Route::post('daily-guide-orders/{dailyGuideOrder}/move', [DailyGuideOrderController::class, 'move'])->name('daily-guide-orders.move');
        Route::delete('daily-guide-orders/{dailyGuideOrder}', [DailyGuideOrderController::class, 'destroy'])->name('daily-guide-orders.destroy');
    });

Route::middleware(['auth', 'ensure.active.role', 'active.role:host'])
    ->prefix('host')
    ->name('host.')
    ->group(function () {
        Route::get('daily-guide-orders', [DailyGuideOrderController::class, 'index'])->name('daily-guide-orders.index');
        Route::post('daily-guide-orders/sync-schedule', [DailyGuideOrderController::class, 'syncFromSchedule'])->name('daily-guide-orders.sync-schedule');
        Route::post('daily-guide-orders/reorder', [DailyGuideOrderController::class, 'reorder'])->name('daily-guide-orders.reorder');
        Route::post('daily-guide-orders', [DailyGuideOrderController::class, 'store'])->name('daily-guide-orders.store');
        Route::post('daily-guide-orders/{dailyGuideOrder}/move', [DailyGuideOrderController::class, 'move'])->name('daily-guide-orders.move');
        Route::delete('daily-guide-orders/{dailyGuideOrder}', [DailyGuideOrderController::class, 'destroy'])->name('daily-guide-orders.destroy');
    });
