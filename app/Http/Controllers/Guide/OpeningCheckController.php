<?php

namespace App\Http\Controllers\Guide;

use App\Http\Controllers\Controller;
use App\Models\OpeningCheck;
use App\Services\LogService;
use App\Services\OpeningCheckService;
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

    public function edit(): View|RedirectResponse
    {
        if (! OpeningCheckTables::exist()) {
            return redirect()
                ->route('guide.dashboard')
                ->with('success', 'Öppningskontrollen är inte redo ännu. Admin behöver köra väntande migrationer under Systemhälsa.');
        }
        $check = $this->openingChecks->ensureForToday(auth()->user());
        $check->load(['deviations.reporter', 'openedBy']);

        return view('guide.opening-check', [
            'check' => $check,
            'checkpoints' => OpeningCheckpoints::labels(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        if (is_string($request->input('visitor_opens_at')) && strlen($request->input('visitor_opens_at')) > 5) {
            $request->merge([
                'visitor_opens_at' => substr($request->input('visitor_opens_at'), 0, 5),
            ]);
        }

        $data = $request->validate([
            'visitor_opens_at' => ['nullable', 'date_format:H:i'],
            'confirmed' => ['nullable', 'boolean'],
            'intent' => ['nullable', 'in:save,complete'],
            'items' => ['nullable', 'array'],
            'items.*' => ['nullable', 'in:'.implode(',', OpeningCheckpoints::outcomes())],
            'deviations' => ['nullable', 'array'],
            'deviations.*' => ['nullable', 'array'],
            'deviations.*.location' => ['nullable', 'string', 'max:255'],
            'deviations.*.description' => ['nullable', 'string', 'max:5000'],
            'deviations.*.immediate_action' => ['nullable', 'string', 'max:5000'],
            'deviations.*.informed_person' => ['nullable', 'string', 'max:255'],
            'deviations.*.decision_before_opening' => ['nullable', 'string', 'max:5000'],
            'deviations.*.already_resolved' => ['nullable', 'boolean'],
        ]);

        $check = $this->openingChecks->ensureForToday(auth()->user());
        $complete = ($data['intent'] ?? 'save') === 'complete';

        $this->openingChecks->save(
            $check,
            auth()->user(),
            $data['items'] ?? [],
            $data['visitor_opens_at'] ?? null,
            $request->boolean('confirmed'),
            $complete,
            $data['deviations'] ?? [],
        );

        LogService::log(
            OpeningCheck::class,
            $check->id,
            $complete ? 'completed' : 'updated',
            null,
            null,
            $complete
                ? 'Slutförde dagens öppningskontroll'
                : 'Sparade utkast för dagens öppningskontroll'
        );

        return redirect()
            ->route('guide.opening-checks.edit')
            ->with('success', $complete
                ? 'Öppningskontrollen är genomförd och signerad.'
                : 'Utkastet är sparat.');
    }

    public function storeDeviation(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'immediate_action' => ['nullable', 'string', 'max:5000'],
            'informed_person' => ['nullable', 'string', 'max:255'],
            'decision_before_opening' => ['nullable', 'string', 'max:5000'],
            'already_resolved' => ['nullable', 'boolean'],
        ], [
            'description.required' => 'Beskriv avvikelsen.',
        ]);

        $data['already_resolved'] = $request->boolean('already_resolved');

        $check = $this->openingChecks->ensureForToday(auth()->user());
        $deviation = $this->openingChecks->addDeviation($check, auth()->user(), $data);

        LogService::log(
            OpeningCheck::class,
            $check->id,
            'deviation_created',
            null,
            ['deviation_id' => $deviation->id],
            'Rapporterade avvikelse vid öppningskontroll'
        );

        return redirect()
            ->route('guide.opening-checks.edit')
            ->with('success', 'Avvikelsen är registrerad och mejlad.');
    }
}
