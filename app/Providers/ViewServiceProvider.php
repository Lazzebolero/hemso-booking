<?php

namespace App\Providers;

use App\Models\Tour;
use App\Models\TourType;
use App\Services\FacilityReportAlertService;
use App\Services\GuideQuickTourGuardService;
use App\Services\LayoutNotificationService;
use App\Services\TimeClockStationRegistry;
use App\Services\WeatherForecastService;
use App\Support\Roles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $activeRole = session('active_role');
            $newOpenFacilityReportsCount = 0;

            if ($user !== null && $activeRole === Roles::ADMIN) {
                $newOpenFacilityReportsCount = FacilityReportAlertService::countNewOpenSinceAcknowledgmentForUser($user);
            }

            $sidebarWeather = null;

            if ($user !== null && in_array($activeRole, [Roles::ADMIN, Roles::HOST], true)) {
                $routePrefix = $activeRole === Roles::HOST ? 'host' : 'admin';

                if (Route::has($routePrefix.'.weather-forecast.index')) {
                    $sidebarWeather = app(WeatherForecastService::class)->sidebarSnippet();
                }
            }

            $view->with('newOpenFacilityReportsCount', $newOpenFacilityReportsCount);
            $view->with('sidebarWeather', $sidebarWeather);
        });

        View::composer(['layouts.app', 'layouts.guide'], function ($view): void {
            $user = Auth::user();
            $activeRole = session('active_role');
            $qrStampStations = is_string($activeRole)
                ? TimeClockStationRegistry::stationsForRole($activeRole)
                : [];

            $notifications = [
                'activeSystemMessages' => collect(),
                'unreadSystemMessagesCount' => 0,
                'unreadConversationsCount' => 0,
            ];

            try {
                $notifications = app(LayoutNotificationService::class)->forUser(
                    $user,
                    is_string($activeRole) ? $activeRole : null,
                );
            } catch (\Throwable) {
                // Layouten ska alltid kunna renderas, även om meddelandetabeller saknas.
            }

            $view->with([
                'qrStampStations' => $qrStampStations,
                'requiresQrStamp' => $qrStampStations !== [],
                'primaryQrStation' => $qrStampStations[0] ?? null,
                ...$notifications,
            ]);
        });

        View::composer('layouts.guide', function ($view): void {
            $user = Auth::user();

            $guideToursVersion = null;
            $guideQuickTourBlock = null;

            if ($user !== null && session('active_role') === Roles::GUIDE) {
                $guideQuickTourBlock = app(GuideQuickTourGuardService::class)->assess((int) $user->id);
            }

            if ($user !== null) {
                $latest = Tour::query()
                    ->where('guide_id', $user->id)
                    ->max('updated_at');
                $guideToursVersion = $latest ? (string) $latest : null;
            }

            $view->with([
                'guideToursVersion' => $guideToursVersion,
                'guideQuickTourBlock' => $guideQuickTourBlock,
            ]);
        });

        View::composer([
            'admin.tours.create',
            'admin.tours.edit',
            'admin.tours.index',
            'admin.tours.show',
        ], function ($view): void {
            if (! $view->offsetExists('tourTypes') || collect($view->getData()['tourTypes'] ?? [])->isEmpty()) {
                $view->with('tourTypes', TourType::activeOrdered());
            }

            if (! $view->offsetExists('defaultTourTypeId')) {
                $view->with('defaultTourTypeId', TourType::where('is_default', true)->value('id'));
            }
        });
    }
}
