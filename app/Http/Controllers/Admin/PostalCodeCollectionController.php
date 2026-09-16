<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PostalCodeCollectionDay;
use App\Models\PostalCodeLookup;
use App\Services\PostalCodeCollectionStoreService;
use App\Support\ActiveRole;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PostalCodeCollectionController extends Controller
{
    public function __construct(
        private PostalCodeCollectionStoreService $storeService,
    ) {}

    public function edit(Request $request): View
    {
        $this->ensureTablesExist();

        $date = $this->resolveDate($request->query('date'));

        $day = PostalCodeCollectionDay::query()
            ->with(['entries', 'updatedBy'])
            ->whereDate('collection_date', $date->toDateString())
            ->first();

        $postalCodesText = old('postal_codes', $day
            ? $day->entries
                ->map(fn ($entry) => $entry->people_count > 1
                    ? $entry->postal_code.' '.$entry->people_count
                    : $entry->postal_code)
                ->implode("\n")
            : '');

        $lookupCount = PostalCodeLookup::query()->count();

        return view('admin.postal-codes.edit', [
            'date' => $date,
            'day' => $day,
            'postalCodesText' => $postalCodesText,
            'lookupCount' => $lookupCount,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureTablesExist();

        $data = $request->validate([
            'date' => ['required', 'date'],
            'postal_codes' => ['nullable', 'string', 'max:20000'],
        ]);

        $date = $this->resolveDate($data['date']);
        $result = $this->storeService->syncForDate(
            $date,
            (string) ($data['postal_codes'] ?? ''),
            auth()->id(),
        );

        $message = $result['saved'] === 0
            ? 'Inga postnummer sparade för dagen (listan var tom).'
            : "Sparade {$result['saved']} rader ({$result['people']} personer).";

        if ($result['unmatched'] > 0) {
            $lookupCount = PostalCodeLookup::query()->count();
            if ($lookupCount < 100) {
                $message .= ' Postnummerregistret är ofullständigt — kör php artisan postal-codes:import-lookups.';
            } else {
                $message .= " {$result['unmatched']} kunde inte kopplas till ort/län.";
            }
        }

        if ($result['invalid'] !== []) {
            $message .= ' Ogiltiga rader hoppades över: '.implode(', ', array_slice($result['invalid'], 0, 8));
            if (count($result['invalid']) > 8) {
                $message .= ' …';
            }
        }

        return redirect()
            ->route(ActiveRole::routePrefix().'.postal-codes.edit', [
                'date' => $date->toDateString(),
            ])
            ->with('success', $message);
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
        if (
            ! Schema::hasTable('postal_code_lookups')
            || ! Schema::hasTable('postal_code_collection_days')
            || ! Schema::hasTable('postal_code_entries')
        ) {
            throw ValidationException::withMessages([
                'date' => 'Tabeller för postnummer saknas. Kör php artisan migrate.',
            ]);
        }
    }
}
