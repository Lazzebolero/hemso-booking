<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Services\ProductionPresenceService;
use App\Support\Roles;
use Illuminate\View\View;

class ProductionNumberController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function show(): View
    {
        try {
            $production = $this->presence->currentProduction();
            $phoneNumbers = $production?->phoneNumbers ?? collect();
            $insideCount = $production
                ? $production->people()->inside()->count()
                : 0;
        } catch (\Throwable $exception) {
            report($exception);
            $production = null;
            $phoneNumbers = collect();
            $insideCount = 0;
        }

        return view('berg.numbers', [
            'production' => $production,
            'phoneNumbers' => $phoneNumbers,
            'insideCount' => $insideCount,
            'canManagePeople' => session('active_role') === Roles::PRODUKTION_ADMIN,
            'unavailableMessage' => $production === null
                ? 'Ingen aktiv produktion just nu. Be Hemsö-admin att skapa den under Projekt.'
                : null,
        ]);
    }
}
