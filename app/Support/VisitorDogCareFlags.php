<?php

namespace App\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class VisitorDogCareFlags
{
    public const FOOD_ALLERGIES = 'food_allergies';

    public const NEEDS_ALONE = 'needs_alone';

    public const SUBMITTED_FIELD = 'care_flags_submitted';

    /**
     * Known care flags. Add new keys here to expose more checkboxes.
     *
     * @return array<string, string> key => Swedish label
     */
    public static function definitions(): array
    {
        return [
            self::FOOD_ALLERGIES => 'Matallergier',
            self::NEEDS_ALONE => 'Bör vara ensam',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    /**
     * True when the form included the care-flags section (so missing checkboxes mean false).
     */
    public static function wasSubmitted(Request $request): bool
    {
        return $request->boolean(self::SUBMITTED_FIELD);
    }

    /**
     * @param  array<string, mixed>|null  $input  e.g. request care_flags[food_allergies]=1
     * @return array<string, bool>
     */
    public static function normalize(?array $input): array
    {
        $normalized = [];

        foreach (self::keys() as $key) {
            $normalized[$key] = self::toBool($input[$key] ?? false);
        }

        return $normalized;
    }

    /**
     * Normalize care_flags only when the form submitted the care-flags section.
     */
    public static function mergeNormalizedIntoRequest(FormRequest $request): void
    {
        if (! self::wasSubmitted($request)) {
            return;
        }

        $flags = $request->input('care_flags', []);
        if (! is_array($flags)) {
            $flags = [];
        }

        $request->merge([
            'care_flags' => self::normalize($flags),
        ]);
    }

    private static function toBool(mixed $value): bool
    {
        // Hidden+checkbox with the same name can arrive as ['0', '1'].
        if (is_array($value)) {
            foreach ($value as $item) {
                if (filter_var($item, FILTER_VALIDATE_BOOLEAN)) {
                    return true;
                }
            }

            return false;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  array<string, mixed>|null  $flags
     */
    public static function hasAny(?array $flags): bool
    {
        return self::selectedLabels($flags) !== [];
    }

    /**
     * @param  array<string, mixed>|null  $flags
     * @return list<string>
     */
    public static function selectedLabels(?array $flags): array
    {
        $labels = [];

        foreach (self::definitions() as $key => $label) {
            if ((bool) ($flags[$key] ?? false)) {
                $labels[] = $label;
            }
        }

        return $labels;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function validationRules(): array
    {
        return [
            self::SUBMITTED_FIELD => ['sometimes', 'boolean'],
            'care_flags' => ['nullable', 'array'],
            'care_flags.*' => ['nullable', 'boolean'],
        ];
    }
}
