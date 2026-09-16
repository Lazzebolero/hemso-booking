<?php

namespace App\Support;

class VisitorDogCareFlags
{
    public const FOOD_ALLERGIES = 'food_allergies';

    public const NEEDS_ALONE = 'needs_alone';

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
     * @param  array<string, mixed>|null  $input  e.g. request care_flags[food_allergies]=1
     * @return array<string, bool>
     */
    public static function normalize(?array $input): array
    {
        $normalized = [];

        foreach (self::keys() as $key) {
            $normalized[$key] = (bool) ($input[$key] ?? false);
        }

        return $normalized;
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
            'care_flags' => ['nullable', 'array'],
            'care_flags.*' => ['nullable', 'boolean'],
        ];
    }
}
