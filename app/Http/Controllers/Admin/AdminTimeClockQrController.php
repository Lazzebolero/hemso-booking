<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Services\TimeClockStationRegistry;
use App\Support\Roles;
use Illuminate\View\View;

class AdminTimeClockQrController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', TimeEntry::class);

        $stations = collect(TimeClockStationRegistry::all())
            ->map(function (array $station, string $key) {
                return [
                    'key' => $key,
                    'label' => $station['label'] ?? $key,
                    'description' => $station['description'] ?? '',
                    'configured' => filled($station['token'] ?? null),
                    'scan_url' => TimeClockStationRegistry::scanUrl($key),
                ];
            })
            ->values();

        return view('admin.time.qr-codes', [
            'stations' => $stations,
            'clockRoles' => TimeClockStationRegistry::clockRoles(),
            'clockRoleLabels' => Roles::labels(),
            'facilityConfigured' => is_numeric(config('time_clock.facility_latitude'))
                && is_numeric(config('time_clock.facility_longitude')),
        ]);
    }
}
