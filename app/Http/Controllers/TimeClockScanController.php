<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use App\Services\TimeClockStationRegistry;
use App\Support\Roles;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TimeClockScanController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $station = TimeClockStationRegistry::findByToken($token);

        if (! $station) {
            throw new NotFoundHttpException;
        }

        return $this->renderScanPage($request, $station, $token);
    }

    public function showStation(Request $request, string $station): View
    {
        $stationConfig = TimeClockStationRegistry::findByKey($station);

        if (! $stationConfig) {
            throw new NotFoundHttpException;
        }

        $token = (string) ($stationConfig['token'] ?? '');

        if ($token === '') {
            abort(503, 'Stämpelstationen är inte konfigurerad. Kontakta admin.');
        }

        return $this->renderScanPage($request, $stationConfig, $token);
    }

    /**
     * @param  array<string, mixed>  $station
     */
    private function renderScanPage(Request $request, array $station, string $token): View
    {
        $activeRole = session('active_role');

        if (! is_string($activeRole) || ! TimeClockStationRegistry::userMayAccess($station, $activeRole)) {
            abort(403, 'Din roll kan inte stämpla via QR.');
        }

        $this->authorize('clock', TimeEntry::class);

        $openEntry = TimeEntry::currentOpenForUser($request->user()->id);

        return view('time.scan', [
            'station' => $station,
            'scanToken' => $token,
            'openEntry' => $openEntry,
            'useGuideLayout' => $activeRole === Roles::GUIDE,
        ]);
    }
}
