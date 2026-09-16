<?php

use App\Http\Controllers\PublicSiteStatusController;
use App\Http\Controllers\PublicTourBookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publika bokningssidor
|--------------------------------------------------------------------------
*/

Route::get('/public/special-tours.json', [PublicTourBookingController::class, 'upcomingSpecialTours'])
    ->name('public.special-tours.index');

Route::get('/public/site-status.json', [PublicSiteStatusController::class, 'json'])
    ->name('public.site-status.index');

Route::get('/public/site-status-bar.js', [PublicSiteStatusController::class, 'widgetScript'])
    ->name('public.site-status.script');

Route::get('/tour-booking/{slug}', [PublicTourBookingController::class, 'show'])
    ->name('public.tour-booking.show');

Route::post('/tour-booking/{slug}', [PublicTourBookingController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('public.tour-booking.store');

Route::get('/tour-booking/{slug}/thank-you', [PublicTourBookingController::class, 'thankYou'])
    ->name('public.tour-booking.thank-you');
