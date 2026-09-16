<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Setting;
use App\Support\CountryCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CountryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');

        $countries = Country::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%');
                });
            })
            ->when($filter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($filter === 'quick', fn ($query) => $query->where('is_quick_pick', true))
            ->when($filter === 'proposed', fn ($query) => $query->where('is_proposed', true))
            ->when($filter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $quickPickCountries = Country::query()
            ->quickPick()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->take(CountryCatalog::maxQuickPicks())
            ->get();

        $maxQuickPicks = CountryCatalog::maxQuickPicks();

        return view('admin.settings.countries', compact(
            'countries',
            'quickPickCountries',
            'search',
            'filter',
            'maxQuickPicks',
        ));
    }

    public function store(Request $request)
    {
        $request->merge([
            'code' => strtolower(trim((string) $request->input('code', ''))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:countries,code'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_quick_pick' => ['nullable', 'boolean'],
        ], $this->validationMessages());

        $payload = [
            'name' => $data['name'],
            'code' => $data['code'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'is_quick_pick' => $request->boolean('is_quick_pick'),
            'is_proposed' => false,
        ];

        if ($payload['is_quick_pick']) {
            $this->ensureQuickPickLimit();
        }

        Country::create($payload);

        return back()->with('success', 'Land tillagt.');
    }

    public function update(Request $request, Country $country)
    {
        $request->merge([
            'code' => strtolower(trim((string) $request->input('code', ''))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('countries', 'code')->ignore($country->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_quick_pick' => ['nullable', 'boolean'],
            'approve' => ['nullable', 'boolean'],
        ], $this->validationMessages());

        $payload = [
            'name' => $data['name'],
            'code' => $data['code'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'is_quick_pick' => $request->boolean('is_quick_pick'),
            'is_proposed' => $country->is_proposed,
        ];

        if ($request->boolean('approve') || $payload['is_quick_pick']) {
            $payload['is_proposed'] = false;
        }

        if ($payload['is_quick_pick'] && ! $country->is_quick_pick) {
            $this->ensureQuickPickLimit();
        }

        $country->update($payload);

        return back()->with('success', 'Land uppdaterat.');
    }

    public function destroy(Country $country)
    {
        if ($country->bookings()->exists()) {
            throw ValidationException::withMessages([
                'country' => 'Landet kan inte tas bort eftersom det används i bokningar.',
            ]);
        }

        $country->delete();

        return back()->with('success', 'Land borttaget.');
    }

    public function updateQuickPickLimit(Request $request)
    {
        $data = $request->validate([
            'country_quick_pick_max' => [
                'required',
                'integer',
                'min:1',
                'max:'.CountryCatalog::ABSOLUTE_MAX_QUICK_PICKS,
            ],
        ]);

        Setting::updateOrCreate(
            ['key' => 'country_quick_pick_max'],
            ['value' => (string) $data['country_quick_pick_max']]
        );

        return back()->with('success', 'Max antal snabbval uppdaterat.');
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'name.required' => 'Ange landsnamn.',
            'code.required' => 'Ange landskod.',
            'code.unique' => 'Landskoden används redan av ett annat land.',
            'code.max' => 'Landskoden får vara högst 10 tecken.',
        ];
    }

    private function ensureQuickPickLimit(): void
    {
        $count = Country::query()->where('is_quick_pick', true)->count();

        if ($count >= CountryCatalog::maxQuickPicks()) {
            throw ValidationException::withMessages([
                'is_quick_pick' => 'Max '.CountryCatalog::maxQuickPicks().' länder kan vara snabbval.',
            ]);
        }
    }
}
