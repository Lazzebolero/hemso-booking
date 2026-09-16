<?php

namespace App\Services;

use App\Models\PostalCodeCollectionDay;
use App\Models\PostalCodeEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class PostalCodeReportService
{
    /**
     * @return array{
     *     year: int,
     *     available_years: list<int>,
     *     summary: array{entries: int, people: int, unique_codes: int, matched_entries: int, unmatched_entries: int, collection_days: int},
     *     counties: list<array{name: string, entries: int, people: int, unique_codes: int}>,
     *     localities: list<array{name: string, county_name: ?string, entries: int, people: int, unique_codes: int}>,
     *     unmatched: list<array{postal_code: string, people: int, entries: int}>
     * }
     */
    public function build(?int $year = null): array
    {
        $availableYears = $this->availableYears();
        $year = $year ?? ($availableYears[0] ?? (int) now()->year);

        if (! Schema::hasTable('postal_code_entries') || ! Schema::hasTable('postal_code_collection_days')) {
            return $this->emptyPayload($year, $availableYears);
        }

        $entries = PostalCodeEntry::query()
            ->whereHas('collectionDay', function ($query) use ($year) {
                $query->whereYear('collection_date', $year);
            })
            ->get([
                'id',
                'postal_code',
                'people_count',
                'locality',
                'county_name',
                'lookup_matched',
                'postal_code_collection_day_id',
            ]);

        $collectionDays = (int) $entries
            ->pluck('postal_code_collection_day_id')
            ->unique()
            ->count();

        $matched = $entries->where('lookup_matched', true);
        $unmatchedEntries = $entries->where('lookup_matched', false);

        return [
            'year' => $year,
            'available_years' => $availableYears,
            'summary' => [
                'entries' => $entries->count(),
                'people' => (int) $entries->sum('people_count'),
                'unique_codes' => $entries->pluck('postal_code')->unique()->count(),
                'matched_entries' => $matched->count(),
                'unmatched_entries' => $unmatchedEntries->count(),
                'collection_days' => $collectionDays,
            ],
            'counties' => $this->aggregateCounties($matched),
            'localities' => $this->aggregateLocalities($matched),
            'unmatched' => $this->aggregateUnmatched($unmatchedEntries),
        ];
    }

    /**
     * @return list<int>
     */
    public function availableYears(): array
    {
        if (! Schema::hasTable('postal_code_collection_days')) {
            return [(int) now()->year];
        }

        $years = PostalCodeCollectionDay::query()
            ->orderByDesc('collection_date')
            ->pluck('collection_date')
            ->map(fn ($date) => (int) Carbon::parse($date)->year)
            ->unique()
            ->values()
            ->all();

        if ($years === []) {
            return [(int) now()->year];
        }

        return $years;
    }

    /**
     * @param  Collection<int, PostalCodeEntry>  $entries
     * @return list<array{name: string, map_name: ?string, entries: int, people: int, unique_codes: int, localities: list<array{name: string, entries: int, people: int, unique_codes: int}>}>
     */
    private function aggregateCounties(Collection $entries): array
    {
        return $entries
            ->groupBy(fn (PostalCodeEntry $entry) => $this->normalizeCountyName($entry->county_name))
            ->map(function (Collection $group, string $name) {
                return [
                    'name' => $name,
                    'map_name' => $this->countyMapName($name),
                    'entries' => $group->count(),
                    'people' => (int) $group->sum('people_count'),
                    'unique_codes' => $group->pluck('postal_code')->unique()->count(),
                    'localities' => $this->aggregateLocalitiesForCounty($group),
                ];
            })
            ->sortByDesc('people')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PostalCodeEntry>  $entries
     * @return list<array{name: string, entries: int, people: int, unique_codes: int}>
     */
    private function aggregateLocalitiesForCounty(Collection $entries): array
    {
        return $entries
            ->filter(fn (PostalCodeEntry $entry) => filled($entry->locality))
            ->groupBy(fn (PostalCodeEntry $entry) => mb_strtolower(trim((string) $entry->locality)))
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'name' => (string) $first->locality,
                    'entries' => $group->count(),
                    'people' => (int) $group->sum('people_count'),
                    'unique_codes' => $group->pluck('postal_code')->unique()->count(),
                ];
            })
            ->sortByDesc('people')
            ->values()
            ->all();
    }

    /**
     * Namn som matchar public/maps/sweden-lan.geojson (properties.name).
     */
    public function countyMapName(?string $name): ?string
    {
        $geoNames = [
            'Blekinge',
            'Dalarna',
            'Gotland',
            'Gävleborg',
            'Halland',
            'Jämtland',
            'Jönköping',
            'Kalmar',
            'Kronoberg',
            'Norrbotten',
            'Skåne',
            'Stockholm',
            'Södermanland',
            'Uppsala',
            'Värmland',
            'Västerbotten',
            'Västernorrland',
            'Västmanland',
            'Västra Götaland',
            'Örebro',
            'Östergötland',
        ];

        $base = trim((string) preg_replace('/\s+län$/iu', '', (string) $name));

        if ($base === '' || mb_strtolower($base) === 'okänt') {
            return null;
        }

        foreach ($geoNames as $geoName) {
            if (mb_strtolower($base) === mb_strtolower($geoName)) {
                return $geoName;
            }

            if (mb_strtolower($base) === mb_strtolower($geoName.'s')) {
                return $geoName;
            }
        }

        return null;
    }

    /**
     * @param  Collection<int, PostalCodeEntry>  $entries
     * @return list<array{name: string, county_name: ?string, entries: int, people: int, unique_codes: int}>
     */
    private function aggregateLocalities(Collection $entries): array
    {
        return $entries
            ->filter(fn (PostalCodeEntry $entry) => filled($entry->locality))
            ->groupBy(fn (PostalCodeEntry $entry) => mb_strtolower(trim((string) $entry->locality)))
            ->map(function (Collection $group) {
                $first = $group->first();

                return [
                    'name' => (string) $first->locality,
                    'county_name' => $this->normalizeCountyName($first->county_name),
                    'entries' => $group->count(),
                    'people' => (int) $group->sum('people_count'),
                    'unique_codes' => $group->pluck('postal_code')->unique()->count(),
                ];
            })
            ->sortByDesc('people')
            ->take(40)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, PostalCodeEntry>  $entries
     * @return list<array{postal_code: string, people: int, entries: int}>
     */
    private function aggregateUnmatched(Collection $entries): array
    {
        return $entries
            ->groupBy('postal_code')
            ->map(function (Collection $group, string $code) {
                return [
                    'postal_code' => $code,
                    'people' => (int) $group->sum('people_count'),
                    'entries' => $group->count(),
                ];
            })
            ->sortByDesc('people')
            ->values()
            ->all();
    }

    private function normalizeCountyName(?string $name): string
    {
        $trimmed = trim((string) $name);

        return $trimmed !== '' ? $trimmed : 'Okänt län';
    }

    /**
     * @param  list<int>  $availableYears
     * @return array{
     *     year: int,
     *     available_years: list<int>,
     *     summary: array{entries: int, people: int, unique_codes: int, matched_entries: int, unmatched_entries: int, collection_days: int},
     *     counties: list<array{name: string, entries: int, people: int, unique_codes: int}>,
     *     localities: list<array{name: string, county_name: ?string, entries: int, people: int, unique_codes: int}>,
     *     unmatched: list<array{postal_code: string, people: int, entries: int}>
     * }
     */
    private function emptyPayload(int $year, array $availableYears): array
    {
        return [
            'year' => $year,
            'available_years' => $availableYears,
            'summary' => [
                'entries' => 0,
                'people' => 0,
                'unique_codes' => 0,
                'matched_entries' => 0,
                'unmatched_entries' => 0,
                'collection_days' => 0,
            ],
            'counties' => [],
            'localities' => [],
            'unmatched' => [],
        ];
    }
}
