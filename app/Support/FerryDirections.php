<?php

namespace App\Support;

class FerryDirections
{
    public const TO_ISLAND = 'to_island';

    public const TO_MAINLAND = 'to_mainland';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::TO_ISLAND => 'Till jobbet',
            self::TO_MAINLAND => 'Hem',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function harborLabels(): array
    {
        return [
            self::TO_ISLAND => 'Strinningen',
            self::TO_MAINLAND => 'Hemsön',
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_keys(self::labels());
    }
}
