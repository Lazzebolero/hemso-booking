<?php

namespace App\Services;

use App\Models\PostalCodeCollectionDay;
use App\Models\PostalCodeEntry;
use App\Models\PostalCodeLookup;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class PostalCodeCollectionStoreService
{
    /**
     * Ersätter dagens poster med den inmatade listan. Tom lista raderar dagen.
     *
     * @return array{day: ?PostalCodeCollectionDay, saved: int, people: int, invalid: list<string>, unmatched: int}
     */
    public function syncForDate(CarbonInterface $date, string $rawList, ?int $userId = null): array
    {
        $parsed = $this->parsePostalCodes($rawList);
        $invalid = $parsed['invalid'];
        $entries = $parsed['entries'];

        return DB::transaction(function () use ($date, $entries, $invalid, $userId) {
            $day = PostalCodeCollectionDay::query()
                ->whereDate('collection_date', $date->toDateString())
                ->first();

            if ($entries === []) {
                if ($day !== null) {
                    $day->delete();
                }

                return [
                    'day' => null,
                    'saved' => 0,
                    'people' => 0,
                    'invalid' => $invalid,
                    'unmatched' => 0,
                ];
            }

            if ($day === null) {
                $day = new PostalCodeCollectionDay([
                    'collection_date' => $date->toDateString(),
                    'created_by' => $userId,
                ]);
            }

            $day->updated_by = $userId;
            $day->save();

            $day->entries()->delete();

            $codes = array_values(array_unique(array_column($entries, 'postal_code')));
            $lookups = PostalCodeLookup::query()
                ->whereIn('postal_code', $codes)
                ->get()
                ->keyBy('postal_code');

            $unmatched = 0;
            $peopleTotal = 0;
            $rows = [];

            foreach ($entries as $entry) {
                $lookup = $lookups->get($entry['postal_code']);
                $matched = $lookup !== null;

                if (! $matched) {
                    $unmatched++;
                }

                $peopleTotal += $entry['people_count'];

                $rows[] = [
                    'postal_code_collection_day_id' => $day->id,
                    'postal_code' => $entry['postal_code'],
                    'people_count' => $entry['people_count'],
                    'locality' => $lookup?->locality,
                    'municipality_name' => $lookup?->municipality_name,
                    'county_code' => $lookup?->county_code,
                    'county_name' => $lookup?->county_name,
                    'lookup_matched' => $matched,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            PostalCodeEntry::query()->insert($rows);

            return [
                'day' => $day->fresh('entries'),
                'saved' => count($entries),
                'people' => $peopleTotal,
                'invalid' => $invalid,
                'unmatched' => $unmatched,
            ];
        });
    }

    /**
     * @return array{entries: list<array{postal_code: string, people_count: int}>, invalid: list<string>}
     */
    public function parsePostalCodes(string $rawList): array
    {
        $entries = [];
        $invalid = [];

        foreach (preg_split('/\R+/', $rawList) ?: [] as $line) {
            $line = trim((string) $line);

            if ($line === '') {
                continue;
            }

            $parsed = $this->parseLine($line);

            if ($parsed === null) {
                $invalid[] = $line;

                continue;
            }

            $entries[] = $parsed;
        }

        return [
            'entries' => $entries,
            'invalid' => $invalid,
        ];
    }

    /**
     * @return array{postal_code: string, people_count: int}|null
     */
    public function parseLine(string $line): ?array
    {
        // "87140 3", "871 40 3", "87140,3"
        if (preg_match('/^(\d{3}\s*\d{2})\s*[,;]\s*(\d{1,3})$/u', $line, $matches)
            || preg_match('/^(\d{3}\s*\d{2})\s+(\d{1,3})$/u', $line, $matches)
        ) {
            $code = PostalCodeLookup::normalize($matches[1]);
            $people = (int) $matches[2];

            if ($code !== null && $people >= 1) {
                return [
                    'postal_code' => $code,
                    'people_count' => $people,
                ];
            }

            return null;
        }

        $code = PostalCodeLookup::normalize($line);

        if ($code === null) {
            return null;
        }

        return [
            'postal_code' => $code,
            'people_count' => 1,
        ];
    }
}
