<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductionPresenceService;
use Illuminate\View\View;

class ProductionPresenceOverviewController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function __invoke(): View
    {
        return view('admin.productions.presence', [
            'boards' => $this->presence->adminPresenceOverview(),
        ]);
    }
}
