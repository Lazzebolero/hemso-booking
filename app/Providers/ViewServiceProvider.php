<?php

namespace App\Providers;

use App\Models\TourType;
use App\Services\FacilityReportAlertService;
use App\Support\Roles;
use Illuminate\Support\Facades\Auth;
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

            $view->with('newOpenFacilityReportsCount', $newOpenFacilityReportsCount);
        });

        View::composer([
            'admin.tours.create',
            'admin.tours.edit',
            'admin.tours.form',
            'admin.tours.index',
            'admin.tours.show',
        ], function ($view) {
            $tourTypes = TourType::where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            $defaultTourTypeId = TourType::where('is_default', true)->value('id');

            $view->with('tourTypes', $tourTypes)
                ->with('defaultTourTypeId', $defaultTourTypeId);
        });
    }
}
