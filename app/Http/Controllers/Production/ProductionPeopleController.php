<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionPerson;
use App\Services\ProductionPresenceService;
use App\Support\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ProductionPeopleController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function index(Request $request): View
    {
        $production = $this->requireActiveProduction();

        $people = $production->people()
            ->with('user')
            ->orderBy('kind')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('berg.people.index', [
            'production' => $production,
            'people' => $people,
            'kindLabels' => ProductionPerson::kindLabels(),
            'actor' => $this->presence->personForUser($production, $request->user()),
            'canManagePeople' => true,
            'insideCount' => $people->where('is_inside', true)->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $production = $this->requireActiveProduction();
        $data = $this->validatedPerson($request);

        try {
            $this->presence->addPerson($production, $data, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['name' => $exception->getMessage()]);
        }

        return redirect()
            ->route('berg.people.index')
            ->with('success', $data['name'].' är tillagd.');
    }

    public function edit(Request $request, ProductionPerson $person): View
    {
        $production = $this->requireActiveProduction();
        $this->ensurePersonInProduction($person, $production);
        $person->load('user');

        return view('berg.people.edit', [
            'production' => $production,
            'person' => $person,
            'kindLabels' => ProductionPerson::kindLabels(),
            'actor' => $this->presence->personForUser($production, $request->user()),
            'canManagePeople' => true,
            'insideCount' => $production->people()->inside()->count(),
        ]);
    }

    public function update(Request $request, ProductionPerson $person): RedirectResponse
    {
        $production = $this->requireActiveProduction();
        $this->ensurePersonInProduction($person, $production);

        $data = $this->validatedPerson($request, $person);

        try {
            $this->presence->updatePerson($person, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['name' => $exception->getMessage()]);
        }

        return redirect()
            ->route('berg.people.index')
            ->with('success', $data['name'].' är uppdaterad.');
    }

    public function import(Request $request): RedirectResponse
    {
        $production = $this->requireActiveProduction();
        $data = $request->validate([
            'csv' => ['required', 'string'],
        ]);

        $rows = $this->presence->parseCsv((string) $data['csv']);
        $result = $this->presence->importPeople($production, $rows, $request->user());

        return redirect()
            ->route('berg.people.index')
            ->with('success', "Import klar: {$result['created']} tillagda, {$result['skipped']} överhoppade.");
    }

    public function destroy(Request $request, ProductionPerson $person): RedirectResponse
    {
        $production = $this->requireActiveProduction();
        $this->ensurePersonInProduction($person, $production);

        try {
            $this->presence->removePerson($person, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['name' => $exception->getMessage()]);
        }

        return redirect()
            ->route('berg.people.index')
            ->with('success', $person->name.' är borttagen.');
    }

    /**
     * @return array{kind: string, name: string, email?: string|null, password?: string|null}
     */
    private function validatedPerson(Request $request, ?ProductionPerson $person = null): array
    {
        $loginKinds = [ProductionPerson::KIND_ADMIN, ProductionPerson::KIND_STAFF];
        $needsLogin = in_array($request->input('kind'), $loginKinds, true);
        $needsNewPassword = $needsLogin && ($person === null || $person->user_id === null);

        return $request->validate([
            'kind' => ['required', Rule::in([
                ProductionPerson::KIND_ADMIN,
                ProductionPerson::KIND_STAFF,
                ProductionPerson::KIND_PARTICIPANT,
            ])],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                Rule::requiredIf($needsLogin),
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($person?->user_id),
            ],
            'password' => [
                Rule::requiredIf($needsNewPassword),
                'nullable',
                'string',
                'min:8',
            ],
        ]);
    }

    private function requireActiveProduction(): Production
    {
        if (session('active_role') !== Roles::PRODUKTION_ADMIN) {
            abort(403, 'Bara produktionsadmin kan hantera personer.');
        }

        $production = $this->presence->currentProduction();

        if ($production === null) {
            abort(404, 'Ingen aktiv produktion just nu.');
        }

        return $production;
    }

    private function ensurePersonInProduction(ProductionPerson $person, Production $production): void
    {
        if ((int) $person->production_id !== (int) $production->id) {
            abort(404);
        }
    }
}
