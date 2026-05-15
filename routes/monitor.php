<?php

use App\Http\Controllers\Admin\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health/monitor', [SystemHealthController::class, 'monitor'])
    ->middleware('system.health.monitor')
    ->name('health.monitor');
