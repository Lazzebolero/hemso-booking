<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpeningCheck;
use App\Models\OpeningDeviation;
use App\Services\LogService;
use App\Services\OpeningCheckService;
use App\Support\ActiveRole;
use App\Support\OpeningCheckpoints;
use App\Support\OpeningCheckTables;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpeningCheckController extends Controller
{
    public function __construct(
        private OpeningCheckService $openingChecks,
    ) {}

    public function index(): View|RedirectResponse
    {
        if (! OpeningCheckTables::exist()) {
            return redirect()
                ->route(ActiveRole::routePrefix().'.system-health.index')
                ->with('warning', 'Kör väntande migrationer på Systemhälsa först. Därefter fungerar öppningskontrollen.');
        }
        $checks = OpeningCheck::query()
            ->with(['openedBy'])
            ->withCount([
                'deviations',
                'deviations as open_deviations_count' => fn ($query) => $query->open(),
            ])
            ->orderByDesc('check_date')
            ->orderByDesc('id')
            ->paginate(30);

        $openDeviations = OpeningDeviation::query()
            ->open()
            ->with(['openingCheck', 'reporter'])
            ->orderByDesc('occurred_at')
            ->get();

        return view('admin.opening-checks.index', [
            'checks' => $checks,
            'openDeviations' => $openDeviations,
            'todayCheck' => $this->openingChecks->forDate(now()),
            'prefix' => ActiveRole::routePrefix(),
        ]);
    }

    public function show(OpeningCheck $openingCheck): View|RedirectResponse
    {
        if (! OpeningCheckTables::exist()) {
            return redirect()
                ->route(ActiveRole::routePrefix().'.system-health.index')
                ->with('warning', 'Kör väntande migrationer på Systemhälsa först. Därefter fungerar öppningskontrollen.');
        }
        $openingCheck->load([
            'openedBy',
            'deviations.reporter',
            'deviations.resolver',
        ]);

        return view('admin.opening-checks.show', [
            'check' => $openingCheck,
            'checkpoints' => OpeningCheckpoints::labels(),
            'prefix' => ActiveRole::routePrefix(),
        ]);
    }

    public function resolveDeviation(Request $request, OpeningCheck $openingCheck, OpeningDeviation $openingDeviation): RedirectResponse
    {
        abort_unless((int) $openingDeviation->opening_check_id === (int) $openingCheck->id, 404);

        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->openingChecks->resolve(
            $openingDeviation,
            auth()->user(),
            (string) ($data['resolution_note'] ?? ''),
        );

        LogService::log(
            OpeningCheck::class,
            $openingCheck->id,
            'deviation_resolved',
            null,
            ['deviation_id' => $openingDeviation->id],
            'Märkte öppningsavvikelse som åtgärdad'
        );

        return redirect()
            ->route(ActiveRole::routePrefix().'.opening-checks.show', $openingCheck)
            ->with('success', 'Avvikelsen är märkt som åtgärdad.');
    }
}
