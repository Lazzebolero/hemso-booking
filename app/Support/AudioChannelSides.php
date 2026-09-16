<?php

namespace App\Support;

class AudioChannelSides
{
    public const LEFT = 'left';

    public const RIGHT = 'right';

    /** @return array<string, string> */
    public static function options(): array
    {
        return [
            self::LEFT => 'Vänster',
            self::RIGHT => 'Höger',
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_keys(self::options());
    }

    public static function label(string $side): string
    {
        return self::options()[$side] ?? ucfirst($side);
    }

    /**
     * @param  list<string>  $selected
     * @return list<string>
     */
    public static function normalize(array $selected): array
    {
        return collect($selected)
            ->filter(fn (string $side) => array_key_exists($side, self::options()))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $usedSides
     * @return array<string, string>
     */
    public static function availableOptions(array $usedSides): array
    {
        return collect(self::options())
            ->reject(fn (string $label, string $side) => in_array($side, $usedSides, true))
            ->all();
    }
}
