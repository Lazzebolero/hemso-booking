<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Production;
use App\Models\ProductionDepartureLog;
use App\Models\ProductionPerson;
use App\Services\ProductionPresenceService;
use App\Support\ProductionSites;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ProductionController extends Controller
{
    public function __construct(
        private ProductionPresenceService $presence,
    ) {}

    public function index(): View
    {
        $productions = Production::query()
            ->withCount('people')
            ->orderByDesc('starts_on')
            ->get();

        $current = Production::current();

        return view('admin.productions.index', [
            'productions' => $productions,
            'current' => $current,
            'siteLabels' => ProductionSites::labels(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validatedStore($request);

        $production = DB::transaction(function () use ($request, $payload): Production {
            $production = Production::query()->create([
                ...$payload['details'],
                'is_active' => true,
                'created_by' => $request->user()->id,
            ]);

            $this->presence->addPerson($production, [
                'kind' => ProductionPerson::KIND_ADMIN,
                'name' => $payload['admin']['admin_name'],
                'email' => $payload['admin']['admin_email'],
                'password' => $payload['admin']['admin_password'],
            ], $request->user());

            return $production;
        });

        return redirect()
            ->route('admin.productions.show', $production)
            ->with('success', 'Produktionen är skapad. Produktionsadmin kan logga in på /berget.');
    }

    public function show(Production $production): View
    {
        $people = $production->people()
            ->with('user')
            ->orderBy('kind')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $production->load('phoneNumbers');

        $presenceLogs = $production->presenceLogs()
            ->with(['person', 'recorder'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50);

        $departureLogs = $this->departureLogs($production);
        $latestPresence = $this->latestPresenceTimes($production);

        return view('admin.productions.show', [
            'production' => $production,
            'people' => $people,
            'presenceLogs' => $presenceLogs,
            'departureLogs' => $departureLogs,
            'latestInByPerson' => $latestPresence['in'],
            'latestOutByPerson' => $latestPresence['out'],
            'latestDepartureByPerson' => $departureLogs
                ->where('action', ProductionDepartureLog::ACTION_DEPARTED)
                ->unique('production_person_id')
                ->keyBy('production_person_id'),
            'insideCount' => $people->where('is_inside', true)->count(),
            'siteLabels' => ProductionSites::labels(),
        ]);
    }

    /**
     * @return Collection<int, ProductionDepartureLog>
     */
    private function departureLogs(Production $production): Collection
    {
        if (! Schema::hasTable('production_departure_logs')) {
            return collect();
        }

        return $production->departureLogs()
            ->with(['person', 'recorder'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();
    }

    /**
     * @return array{in: Collection<int, Carbon>, out: Collection<int, Carbon>}
     */
    private function latestPresenceTimes(Production $production): array
    {
        $empty = [
            'in' => collect(),
            'out' => collect(),
        ];

        if (! Schema::hasTable('production_presence_logs')) {
            return $empty;
        }

        $grouped = $production->presenceLogs()
            ->whereIn('direction', [ProductionPerson::DIRECTION_IN, ProductionPerson::DIRECTION_OUT])
            ->select('production_person_id', 'direction')
            ->selectRaw('max(occurred_at) as last_at')
            ->groupBy('production_person_id', 'direction')
            ->get()
            ->groupBy('direction');

        return [
            'in' => $this->presenceTimesForDirection($grouped, ProductionPerson::DIRECTION_IN),
            'out' => $this->presenceTimesForDirection($grouped, ProductionPerson::DIRECTION_OUT),
        ];
    }

    /**
     * @param  Collection<string, Collection<int, mixed>>  $grouped
     * @return Collection<int, Carbon>
     */
    private function presenceTimesForDirection(Collection $grouped, string $direction): Collection
    {
        return $grouped->get($direction, collect())->mapWithKeys(function (object $row): array {
            return [(int) $row->production_person_id => Carbon::parse($row->last_at)];
        });
    }

    public function update(Request $request, Production $production): RedirectResponse
    {
        $production->update($this->validatedDetails($request));

        return redirect()
            ->route('admin.productions.show', $production)
            ->with('success', 'Uppgifterna är uppdaterade.');
    }

    public function storePerson(Request $request, Production $production): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in([
                ProductionPerson::KIND_ADMIN,
                ProductionPerson::KIND_STAFF,
                ProductionPerson::KIND_PARTICIPANT,
            ])],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                Rule::requiredIf(in_array($request->input('kind'), [
                    ProductionPerson::KIND_ADMIN,
                    ProductionPerson::KIND_STAFF,
                ], true)),
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'password' => [
                Rule::requiredIf(in_array($request->input('kind'), [
                    ProductionPerson::KIND_ADMIN,
                    ProductionPerson::KIND_STAFF,
                ], true)),
                'nullable',
                'string',
                'min:8',
            ],
        ]);

        try {
            $this->presence->addPerson($production, $data, $request->user());
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['name' => $exception->getMessage()]);
        }

        return back()->with('success', $data['name'].' är tillagd.');
    }

    public function import(Request $request, Production $production): RedirectResponse
    {
        $data = $request->validate([
            'csv' => ['required', 'string'],
        ]);

        $rows = $this->parseCsv((string) $data['csv']);
        $result = $this->presence->importPeople($production, $rows, $request->user());

        return back()->with(
            'success',
            "Import klar: {$result['created']} tillagda, {$result['skipped']} överhoppade."
        );
    }

    public function destroyPerson(Production $production, ProductionPerson $person): RedirectResponse
    {
        if ((int) $person->production_id !== (int) $production->id) {
            abort(404);
        }

        $name = $person->name;
        $person->delete();

        return back()->with('success', $name.' är borttagen från listan.');
    }

    /**
     * @return array{
     *     name: string,
     *     starts_on: string,
     *     ends_on: string,
     *     sites: list<string>,
     *     company: ?string,
     *     client: ?string,
     *     client_contact: ?string,
     *     client_phone: ?string,
     *     client_email: ?string,
     *     notes: ?string
     * }
     */
    private function validatedDetails(Request $request): array
    {
        $data = $request->validate($this->detailsRules(), [
            'sites.required' => 'Välj minst en anläggning.',
            'sites.min' => 'Välj minst en anläggning.',
        ]);

        $data['sites'] = array_values(array_unique($data['sites'] ?? []));

        return $data;
    }

    /**
     * @return array{
     *     details: array{
     *         name: string,
     *         starts_on: string,
     *         ends_on: string,
     *         sites: list<string>,
     *         company: ?string,
     *         client: ?string,
     *         client_contact: ?string,
     *         client_phone: ?string,
     *         client_email: ?string,
     *         notes: ?string
     *     },
     *     admin: array{admin_name: string, admin_email: string, admin_password: string}
     * }
     */
    private function validatedStore(Request $request): array
    {
        $data = $request->validate([
            ...$this->detailsRules(),
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8'],
        ], [
            'sites.required' => 'Välj minst en anläggning.',
            'sites.min' => 'Välj minst en anläggning.',
            'admin_name.required' => 'Ange namn på produktionsadmin.',
            'admin_email.required' => 'Ange e-post för produktionsadmin.',
            'admin_email.unique' => 'E-postadressen används redan.',
            'admin_password.required' => 'Ange ett lösenord för produktionsadmin.',
            'admin_password.min' => 'Lösenordet måste vara minst 8 tecken.',
        ]);

        $data['sites'] = array_values(array_unique($data['sites'] ?? []));

        return [
            'details' => [
                'name' => $data['name'],
                'starts_on' => $data['starts_on'],
                'ends_on' => $data['ends_on'],
                'sites' => $data['sites'],
                'company' => $data['company'] ?? null,
                'client' => $data['client'] ?? null,
                'client_contact' => $data['client_contact'] ?? null,
                'client_phone' => $data['client_phone'] ?? null,
                'client_email' => $data['client_email'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
            'admin' => [
                'admin_name' => $data['admin_name'],
                'admin_email' => $data['admin_email'],
                'admin_password' => $data['admin_password'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailsRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'sites' => ['required', 'array', 'min:1'],
            'sites.*' => ['required', 'string', Rule::in(ProductionSites::keys())],
            'company' => ['nullable', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'client_contact' => ['nullable', 'string', 'max:255'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return list<array{kind: string, name: string, email?: string|null, password?: string|null}>
     */
    private function parseCsv(string $csv): array
    {
        $rows = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = array_map('trim', str_getcsv($line, ','));

            if ($index === 0 && $this->looksLikeHeader($parts)) {
                continue;
            }

            $kind = $this->normalizeKind($parts[0] ?? '');
            $name = $parts[1] ?? '';

            if ($kind === null || $name === '') {
                continue;
            }

            $rows[] = [
                'kind' => $kind,
                'name' => $name,
                'email' => $parts[2] ?? null,
                'password' => $parts[3] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<string>  $parts
     */
    private function looksLikeHeader(array $parts): bool
    {
        $first = strtolower($parts[0] ?? '');

        return in_array($first, ['kind', 'niva', 'nivå', 'roll', 'typ'], true);
    }

    private function normalizeKind(string $value): ?string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            'admin', 'produktion admin', 'produktion_admin' => ProductionPerson::KIND_ADMIN,
            'staff', 'personal', 'produktion personal', 'produktion_personal' => ProductionPerson::KIND_STAFF,
            'participant', 'deltagare' => ProductionPerson::KIND_PARTICIPANT,
            default => null,
        };
    }
}
