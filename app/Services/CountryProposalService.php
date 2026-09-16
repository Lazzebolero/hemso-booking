<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Str;

class CountryProposalService
{
    public function resolve(?int $countryId, ?string $proposedName): ?int
    {
        $proposedName = trim((string) $proposedName);

        if ($proposedName !== '') {
            return $this->resolveProposedName($proposedName)->id;
        }

        if ($countryId === null) {
            return null;
        }

        $country = Country::query()
            ->whereKey($countryId)
            ->where('is_active', true)
            ->first();

        return $country?->id;
    }

    public function resolveProposedName(string $proposedName): Country
    {
        $normalizedName = $this->normalizeName($proposedName);

        $existing = Country::query()
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return Country::query()->create([
            'name' => $this->displayName($proposedName),
            'code' => $this->uniqueCode($proposedName),
            'is_active' => true,
            'is_quick_pick' => false,
            'is_proposed' => true,
            'sort_order' => 900,
        ]);
    }

    private function normalizeName(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    private function displayName(string $name): string
    {
        $trimmed = trim($name);

        return mb_strtoupper(mb_substr($trimmed, 0, 1)).mb_substr($trimmed, 1);
    }

    private function uniqueCode(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? substr($base, 0, 8) : 'land';
        $candidate = $base;
        $suffix = 1;

        while (Country::query()->where('code', $candidate)->exists()) {
            $candidate = substr($base, 0, max(1, 8 - strlen((string) $suffix))).$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
