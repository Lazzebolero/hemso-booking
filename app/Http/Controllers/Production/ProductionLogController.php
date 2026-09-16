<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Services\ProductionPresenceService;
use App\Support\Roles;
use Illuminate\View\View;

class ProductionLogController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function show(): View
    {
        try {
            $production = $this->presence->currentProduction();
            $logs = $production
                ? $this->presence->recentLogs($production)
                : collect();
            $insideCount = $production
                ? $production->people()->inside()->count()
                : 0;
        } catch (\Throwable $exception) {
            report($exception);
            $production = null;
            $logs = collect();
            $insideCount = 0;
        }

        return view('berg.log', [
            'production' => $production,
            'logs' => $logs,
            'insideCount' => $insideCount,
            'canManagePeople' => session('active_role') === Roles::PRODUKTION_ADMIN,
            'unavailableMessage' => $production === null
                ? 'Ingen aktiv produktion just nu. Be Hemsö-admin att skapa den under Projekt.'
                : null,
        ]);
    }
}
