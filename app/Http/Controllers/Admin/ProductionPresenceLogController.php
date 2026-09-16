<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Services\ProductionPresenceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionPresenceLogController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function __invoke(Request $request): View
    {
        $selectedProductionId = $request->integer('production_id') ?: null;

        if ($selectedProductionId !== null && ! Production::query()->whereKey($selectedProductionId)->exists()) {
            $selectedProductionId = null;
        }

        return view('admin.productions.log', [
            'logs' => $this->presence->adminLogs($selectedProductionId),
            'productions' => Production::query()
                ->orderByDesc('starts_on')
                ->orderBy('name')
                ->get(['id', 'name']),
            'selectedProductionId' => $selectedProductionId,
        ]);
    }
}
