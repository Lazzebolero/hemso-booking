<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PWA / offline (explicit routes so PHPUnit hits files; production web server
| often serves these from public/ before Laravel).
|--------------------------------------------------------------------------
*/

Route::get('/manifest.webmanifest', function () {
    return response()->json([
        'name' => 'Hemsö Fästning Bokning',
        'short_name' => 'Hemsö',
        'description' => 'Bokning, schema och tidrapportering',
        'start_url' => url('/'),
        'scope' => url('/'),
        'display' => 'standalone',
        'background_color' => '#f8fafc',
        'theme_color' => '#0f172a',
        'icons' => [
            [
                'src' => asset('icons/pwa-icon-192.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => asset('icons/pwa-icon-512.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ], 200, [
        'Content-Type' => 'application/manifest+json; charset=UTF-8',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
})->name('pwa.manifest');

Route::get('/service-worker.js', function () {
    $path = public_path('service-worker.js');
    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
    ]);
})->name('pwa.service-worker');

Route::get('/offline.html', function () {
    $path = public_path('offline.html');
    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
})->name('pwa.offline');

Route::get('/js/offline-queue.js', function () {
    $path = public_path('js/offline-queue.js');
    abort_unless(File::isFile($path), 404);

    return response(File::get($path), 200, [
        'Content-Type' => 'application/javascript; charset=UTF-8',
    ]);
})->name('pwa.offline-queue');
