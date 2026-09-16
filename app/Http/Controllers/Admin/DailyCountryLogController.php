<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\DailyCountryLog;
use App\Services\CountryProposalService;
use App\Support\ActiveRole;
use App\Support\CountryCatalog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DailyCountryLogController extends Controller
{
    public function __construct(
        private CountryProposalService $countryProposalService,
    ) {}

    public function edit(Request $request)
    {
        $this->ensureTablesExist();

        $date = $this->resolveDate($request->query('date'));

        $log = DailyCountryLog::query()
            ->with(['countries', 'updatedBy'])
            ->whereDate('log_date', $date->toDateString())
            ->first();

        $quickPickCountries = Country::query()
            ->active()
            ->quickPick()
            ->orderBy('name')
            ->take(CountryCatalog::maxQuickPicks())
            ->get();

        $otherCountries = Country::query()
            ->active()
            ->when(
                $quickPickCountries->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $quickPickCountries->modelKeys())
            )
            ->orderBy('name')
            ->get();

        $selectedCountryIds = $log
            ? $log->countries->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        return view('admin.daily-countries.edit', [
            'date' => $date,
            'log' => $log,
            'quickPickCountries' => $quickPickCountries,
            'otherCountries' => $otherCountries,
            'selectedCountryIds' => $selectedCountryIds,
        ]);
    }

    public function update(Request $request)
    {
        $this->ensureTablesExist();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer', 'exists:countries,id'],
            'country_proposed_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $date = $this->resolveDate($data['date']);
        $countryIds = collect($data['country_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $proposedName = trim((string) ($data['country_proposed_name'] ?? ''));
        if ($proposedName !== '') {
            $proposedId = $this->countryProposalService->resolveProposedName($proposedName)->id;
            $countryIds[] = $proposedId;
            $countryIds = array_values(array_unique($countryIds));
        }

        $log = DailyCountryLog::query()->firstOrNew([
            'log_date' => $date->toDateString(),
        ]);

        $log->notes = $data['notes'] ?? null;
        $log->updated_by = auth()->id();
        $log->save();

        $log->countries()->sync($countryIds);

        return redirect()
            ->route(ActiveRole::routePrefix().'.daily-countries.edit', [
                'date' => $date->toDateString(),
            ])
            ->with('success', 'Dagens länder sparade.');
    }

    private function resolveDate(mixed $date): Carbon
    {
        try {
            return Carbon::parse((string) ($date ?: now()->toDateString()))->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }

    private function ensureTablesExist(): void
    {
        if (! Schema::hasTable('daily_country_logs') || ! Schema::hasTable('daily_country_log_country')) {
            throw ValidationException::withMessages([
                'date' => 'Tabellen för dagliga länder saknas. Kör php artisan migrate.',
            ]);
        }
    }
}
