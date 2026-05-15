<?php

use App\Http\Controllers\PublicTourBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publika bokningssidor
|--------------------------------------------------------------------------
*/

Route::get('/tour-booking/{slug}', [PublicTourBookingController::class, 'show'])
    ->name('public.tour-booking.show');

Route::post('/tour-booking/{slug}', [PublicTourBookingController::class, 'store'])
    ->name('public.tour-booking.store');
